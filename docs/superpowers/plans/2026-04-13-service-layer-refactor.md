# Service Layer Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extraer lógica de negocio de `ProgressController` y `QuizController` hacia `ProgressService`, `CertificateService` y `QuizService`, eliminando código duplicado y haciendo el sistema testeable.

**Architecture:** Service Layer clásico — tres clases de servicio inyectadas via constructor en los controladores. `ProgressService` orquesta `CertificateService`. `QuizService` solo califica. `QuizController` pasa de redirect a JSON; el frontend del quiz se convierte de form POST a fetch con Alpine.js.

**Tech Stack:** PHP 8.2 · Laravel 12 · Eloquent ORM · Blade · Alpine.js · PHPUnit · MySQL

---

## File Map

| Acción | Archivo | Responsabilidad |
|---|---|---|
| Crear | `database/migrations/2026_04_13_120000_add_passing_score_to_lessons.php` | Columna `passing_score` nullable en `lessons` |
| Modificar | `app/Models/Lesson.php` | Añadir `passing_score` a `$fillable` + método `passingScore()` |
| Crear | `app/Services/CertificateService.php` | Emisión idempotente de certificados |
| Crear | `app/Services/ProgressService.php` | Completar lecciones, recalcular progreso, resolver siguiente lección |
| Crear | `app/Services/QuizService.php` | Calificar quizzes con score dinámico |
| Modificar | `app/Http/Controllers/Student/ProgressController.php` | Thin controller — delegar a ProgressService |
| Modificar | `app/Http/Controllers/Student/QuizController.php` | Thin controller — JSON response, delegar a QuizService + ProgressService |
| Modificar | `app/Http/Controllers/Student/LessonController.php` | Eliminar `quiz_results` de sesión |
| Modificar | `resources/views/courses/learn.blade.php` | Quiz: form POST → Alpine.js + fetch |
| Crear | `tests/Unit/Models/LessonTest.php` | Unit test para `passingScore()` |
| Crear | `tests/Unit/Services/CertificateServiceTest.php` | Unit tests para `CertificateService` |
| Crear | `tests/Unit/Services/ProgressServiceTest.php` | Unit tests para `ProgressService` |
| Crear | `tests/Unit/Services/QuizServiceTest.php` | Unit tests para `QuizService` |
| Crear | `tests/Feature/Student/ProgressControllerTest.php` | Feature test endpoint `/progress/{lesson}` |
| Crear | `tests/Feature/Student/QuizControllerTest.php` | Feature test endpoint `/quiz/{lesson}` |

---

## Task 1: Migración — `passing_score` en lessons

**Files:**
- Create: `database/migrations/2026_04_13_120000_add_passing_score_to_lessons.php`

- [ ] **Step 1: Crear la migración**

Crear el archivo `database/migrations/2026_04_13_120000_add_passing_score_to_lessons.php` con:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->tinyInteger('passing_score')->unsigned()->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('passing_score');
        });
    }
};
```

- [ ] **Step 2: Ejecutar la migración**

```bash
php artisan migrate
```

Expected output:
```
  INFO  Running migrations.
  2026_04_13_120000_add_passing_score_to_lessons ................. DONE
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_13_120000_add_passing_score_to_lessons.php
git commit -m "feat: add passing_score column to lessons table"
```

---

## Task 2: Lesson model — `passingScore()` helper

**Files:**
- Modify: `app/Models/Lesson.php`
- Create: `tests/Unit/Models/LessonTest.php`

- [ ] **Step 1: Escribir el test fallido**

Crear `tests/Unit/Models/LessonTest.php`:

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    private function makeLesson(array $attributes = []): Lesson
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course-' . uniqid(),
            'is_free'       => true,
        ]);
        $module = Module::create([
            'course_id'  => $course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);

        return Lesson::create(array_merge([
            'module_id'  => $module->id,
            'title'      => 'Lesson 1',
            'slug'       => 'lesson-1-' . uniqid(),
            'type'       => 'video',
            'sort_order' => 1,
        ], $attributes));
    }

    public function test_passing_score_returns_70_when_null(): void
    {
        $lesson = $this->makeLesson(['passing_score' => null]);
        $this->assertSame(70, $lesson->passingScore());
    }

    public function test_passing_score_returns_custom_value(): void
    {
        $lesson = $this->makeLesson(['passing_score' => 85]);
        $this->assertSame(85, $lesson->passingScore());
    }

    public function test_passing_score_returns_zero_when_set_to_zero(): void
    {
        $lesson = $this->makeLesson(['passing_score' => 0]);
        $this->assertSame(0, $lesson->passingScore());
    }
}
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
php artisan test tests/Unit/Models/LessonTest.php
```

Expected: FAIL — `Call to undefined method App\Models\Lesson::passingScore()`

- [ ] **Step 3: Actualizar `app/Models/Lesson.php`**

Añadir `passing_score` a `$fillable` y agregar el método `passingScore()`:

```php
protected $fillable = [
    'module_id', 'title', 'slug', 'content',
    'video_url', 'video_duration', 'type',
    'is_preview', 'sort_order', 'passing_score',
];
```

Y agregar el método antes del cierre de la clase:

```php
public function passingScore(): int
{
    return $this->passing_score ?? 70;
}
```

- [ ] **Step 4: Ejecutar el test para verificar que pasa**

```bash
php artisan test tests/Unit/Models/LessonTest.php
```

Expected: PASS (3 tests, 3 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Models/Lesson.php tests/Unit/Models/LessonTest.php
git commit -m "feat: add passingScore() method to Lesson model"
```

---

## Task 3: CertificateService

**Files:**
- Create: `app/Services/CertificateService.php`
- Create: `tests/Unit/Services/CertificateServiceTest.php`

- [ ] **Step 1: Escribir el test fallido**

Crear `tests/Unit/Services/CertificateServiceTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateServiceTest extends TestCase
{
    use RefreshDatabase;

    private CertificateService $service;
    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CertificateService();

        $instructor = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'is_free'       => true,
        ]);
    }

    private function enroll(int $progress): Enrollment
    {
        return Enrollment::create([
            'user_id'     => $this->student->id,
            'course_id'   => $this->course->id,
            'progress'    => $progress,
            'enrolled_at' => now(),
        ]);
    }

    public function test_issues_certificate_when_progress_is_100(): void
    {
        $this->enroll(100);

        $certificate = $this->service->issueIfCompleted($this->student, $this->course);

        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertDatabaseHas('certificates', [
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_returns_null_when_progress_is_below_100(): void
    {
        $this->enroll(80);

        $result = $this->service->issueIfCompleted($this->student, $this->course);

        $this->assertNull($result);
        $this->assertDatabaseMissing('certificates', [
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_returns_null_when_not_enrolled(): void
    {
        $result = $this->service->issueIfCompleted($this->student, $this->course);

        $this->assertNull($result);
    }

    public function test_does_not_duplicate_certificate_on_repeated_calls(): void
    {
        $this->enroll(100);

        $cert1 = $this->service->issueIfCompleted($this->student, $this->course);
        $cert2 = $this->service->issueIfCompleted($this->student, $this->course);

        $this->assertEquals($cert1->id, $cert2->id);
        $this->assertDatabaseCount('certificates', 1);
    }
}
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
php artisan test tests/Unit/Services/CertificateServiceTest.php
```

Expected: FAIL — `Class "App\Services\CertificateService" not found`

- [ ] **Step 3: Implementar `CertificateService`**

Crear `app/Services/CertificateService.php`:

```php
<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Str;

class CertificateService
{
    public function issueIfCompleted(User $user, Course $course): ?Certificate
    {
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment || $enrollment->progress !== 100) {
            return null;
        }

        return Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['code' => Str::uuid(), 'issued_at' => now()]
        );
    }
}
```

- [ ] **Step 4: Ejecutar el test para verificar que pasa**

```bash
php artisan test tests/Unit/Services/CertificateServiceTest.php
```

Expected: PASS (4 tests, 5 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/CertificateService.php tests/Unit/Services/CertificateServiceTest.php
git commit -m "feat: add CertificateService with idempotent issueIfCompleted()"
```

---

## Task 4: ProgressService

**Files:**
- Create: `app/Services/ProgressService.php`
- Create: `tests/Unit/Services/ProgressServiceTest.php`

- [ ] **Step 1: Escribir los tests fallidos**

Crear `tests/Unit/Services/ProgressServiceTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $service;
    private User $student;
    private Course $course;
    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProgressService(new CertificateService());

        $instructor    = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course  = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'is_free'       => true,
        ]);
        $this->module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);
        Enrollment::create([
            'user_id'     => $this->student->id,
            'course_id'   => $this->course->id,
            'progress'    => 0,
            'enrolled_at' => now(),
        ]);
    }

    private function makeLesson(int $sortOrder = 1): Lesson
    {
        return Lesson::create([
            'module_id'  => $this->module->id,
            'title'      => "Lesson $sortOrder",
            'slug'       => "lesson-$sortOrder-" . uniqid(),
            'type'       => 'video',
            'sort_order' => $sortOrder,
        ]);
    }

    public function test_complete_lesson_creates_lesson_progress(): void
    {
        $lesson = $this->makeLesson();

        $progress = $this->service->completeLesson($this->student, $lesson, 30);

        $this->assertInstanceOf(LessonProgress::class, $progress);
        $this->assertTrue($progress->completed);
        $this->assertEquals(30, $progress->last_position);
        $this->assertDatabaseHas('lesson_progress', [
            'user_id'   => $this->student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
        ]);
    }

    public function test_complete_lesson_is_idempotent(): void
    {
        $lesson = $this->makeLesson();

        $this->service->completeLesson($this->student, $lesson);
        $this->service->completeLesson($this->student, $lesson);

        $this->assertDatabaseCount('lesson_progress', 1);
    }

    public function test_recalculate_returns_correct_percentage(): void
    {
        $lesson1 = $this->makeLesson(1);
        $lesson2 = $this->makeLesson(2);

        $this->service->completeLesson($this->student, $lesson1);
        $percent = $this->service->recalculateCourseProgress($this->student, $this->course);

        $this->assertEquals(50, $percent);
        $this->assertDatabaseHas('enrollments', [
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
            'progress'  => 50,
        ]);
    }

    public function test_recalculate_sets_completed_at_when_100_percent(): void
    {
        $lesson = $this->makeLesson(1);

        $this->service->completeLesson($this->student, $lesson);
        $percent = $this->service->recalculateCourseProgress($this->student, $this->course);

        $this->assertEquals(100, $percent);
        $enrollment = $this->student->enrollments()->where('course_id', $this->course->id)->first();
        $this->assertNotNull($enrollment->completed_at);
    }

    public function test_recalculate_issues_certificate_when_100_percent(): void
    {
        $lesson = $this->makeLesson(1);

        $this->service->completeLesson($this->student, $lesson);
        $this->service->recalculateCourseProgress($this->student, $this->course);

        $this->assertDatabaseHas('certificates', [
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_get_next_lesson_returns_next_uncompleted_lesson(): void
    {
        $lesson1 = $this->makeLesson(1);
        $lesson2 = $this->makeLesson(2);

        $this->service->completeLesson($this->student, $lesson1);

        $next = $this->service->getNextLesson($this->student, $this->course, $lesson1);

        $this->assertNotNull($next);
        $this->assertEquals($lesson2->id, $next['id']);
    }

    public function test_get_next_lesson_returns_null_when_already_completed(): void
    {
        $lesson1 = $this->makeLesson(1);
        $lesson2 = $this->makeLesson(2);

        $this->service->completeLesson($this->student, $lesson1);
        $this->service->completeLesson($this->student, $lesson2);

        $next = $this->service->getNextLesson($this->student, $this->course, $lesson1);

        $this->assertNull($next);
    }

    public function test_get_next_lesson_returns_null_at_last_lesson(): void
    {
        $lesson = $this->makeLesson(1);

        $next = $this->service->getNextLesson($this->student, $this->course, $lesson);

        $this->assertNull($next);
    }
}
```

- [ ] **Step 2: Ejecutar los tests para verificar que fallan**

```bash
php artisan test tests/Unit/Services/ProgressServiceTest.php
```

Expected: FAIL — `Class "App\Services\ProgressService" not found`

- [ ] **Step 3: Implementar `ProgressService`**

Crear `app/Services/ProgressService.php`:

```php
<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class ProgressService
{
    public function __construct(
        private readonly CertificateService $certificateService
    ) {}

    public function completeLesson(User $user, Lesson $lesson, int $position = 0): LessonProgress
    {
        return LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'completed'     => true,
                'completed_at'  => now(),
                'last_position' => $position,
            ]
        );
    }

    public function recalculateCourseProgress(User $user, Course $course): int
    {
        $lessonIds = Lesson::whereIn(
            'module_id',
            $course->modules()->pluck('id')
        )->pluck('id');

        $totalLessons = $lessonIds->count();

        if ($totalLessons === 0) {
            return 0;
        }

        $completed = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('completed', true)
            ->count();

        $percent = (int) round($completed / $totalLessons * 100);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment) {
            $enrollment->progress     = $percent;
            $enrollment->completed_at = $percent === 100 ? now() : null;
            $enrollment->save();
        }

        if ($percent === 100) {
            $this->certificateService->issueIfCompleted($user, $course);
        }

        return $percent;
    }

    public function getNextLesson(User $user, Course $course, Lesson $currentLesson): ?array
    {
        $allLessons = $course->modules()
            ->orderBy('sort_order')
            ->with(['lessons' => fn($q) => $q->orderBy('sort_order')])
            ->get()
            ->flatMap(fn($m) => $m->lessons);

        $currentIndex = $allLessons->search(fn($l) => $l->id === $currentLesson->id);
        $nextLesson   = $currentIndex !== false ? $allLessons->get($currentIndex + 1) : null;

        if (!$nextLesson || $nextLesson->isCompletedBy($user)) {
            return null;
        }

        return [
            'id'    => $nextLesson->id,
            'title' => $nextLesson->title,
            'type'  => $nextLesson->type,
            'url'   => route('lesson.show', [$course->slug, $nextLesson->slug]),
        ];
    }
}
```

- [ ] **Step 4: Ejecutar los tests para verificar que pasan**

```bash
php artisan test tests/Unit/Services/ProgressServiceTest.php
```

Expected: PASS (8 tests, 12 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/ProgressService.php tests/Unit/Services/ProgressServiceTest.php
git commit -m "feat: add ProgressService with completeLesson, recalculate, getNextLesson"
```

---

## Task 5: QuizService

**Files:**
- Create: `app/Services/QuizService.php`
- Create: `tests/Unit/Services/QuizServiceTest.php`

- [ ] **Step 1: Escribir los tests fallidos**

Crear `tests/Unit/Services/QuizServiceTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuizService $service;
    private Lesson $lesson;
    private QuizQuestion $question1;
    private QuizQuestion $question2;
    private QuizOption $correct1;
    private QuizOption $correct2;
    private QuizOption $wrong1;
    private QuizOption $wrong2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuizService();

        $instructor = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'is_free'       => true,
        ]);
        $module = Module::create([
            'course_id'  => $course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);
        $this->lesson = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Quiz Lesson',
            'slug'       => 'quiz-lesson',
            'type'       => 'quiz',
            'sort_order' => 1,
        ]);

        // Question 1 with options
        $this->question1 = QuizQuestion::create([
            'lesson_id'  => $this->lesson->id,
            'question'   => 'Question 1',
            'sort_order' => 1,
        ]);
        $this->correct1 = QuizOption::create([
            'question_id' => $this->question1->id,
            'text'        => 'Correct 1',
            'is_correct'  => true,
        ]);
        $this->wrong1 = QuizOption::create([
            'question_id' => $this->question1->id,
            'text'        => 'Wrong 1',
            'is_correct'  => false,
        ]);

        // Question 2 with options
        $this->question2 = QuizQuestion::create([
            'lesson_id'  => $this->lesson->id,
            'question'   => 'Question 2',
            'sort_order' => 2,
        ]);
        $this->correct2 = QuizOption::create([
            'question_id' => $this->question2->id,
            'text'        => 'Correct 2',
            'is_correct'  => true,
        ]);
        $this->wrong2 = QuizOption::create([
            'question_id' => $this->question2->id,
            'text'        => 'Wrong 2',
            'is_correct'  => false,
        ]);
    }

    public function test_grade_returns_100_when_all_correct(): void
    {
        $answers = [
            $this->question1->id => $this->correct1->id,
            $this->question2->id => $this->correct2->id,
        ];

        $result = $this->service->grade($this->lesson, $answers);

        $this->assertEquals(100, $result['score']);
        $this->assertTrue($result['passed']);
        $this->assertEquals(2, $result['correct']);
        $this->assertEquals(2, $result['total']);
    }

    public function test_grade_returns_0_when_all_wrong(): void
    {
        $answers = [
            $this->question1->id => $this->wrong1->id,
            $this->question2->id => $this->wrong2->id,
        ];

        $result = $this->service->grade($this->lesson, $answers);

        $this->assertEquals(0, $result['score']);
        $this->assertFalse($result['passed']);
        $this->assertEquals(0, $result['correct']);
    }

    public function test_grade_returns_50_with_one_correct(): void
    {
        $answers = [
            $this->question1->id => $this->correct1->id,
            $this->question2->id => $this->wrong2->id,
        ];

        $result = $this->service->grade($this->lesson, $answers);

        $this->assertEquals(50, $result['score']);
        $this->assertFalse($result['passed']); // 50 < 70 (default)
    }

    public function test_grade_respects_custom_passing_score(): void
    {
        $this->lesson->update(['passing_score' => 40]);

        $answers = [
            $this->question1->id => $this->correct1->id,
            $this->question2->id => $this->wrong2->id,
        ];

        $result = $this->service->grade($this->lesson->fresh(), $answers);

        $this->assertEquals(50, $result['score']);
        $this->assertTrue($result['passed']); // 50 >= 40 (custom)
    }

    public function test_grade_marks_correct_and_wrong_per_question(): void
    {
        $answers = [
            $this->question1->id => $this->correct1->id,
            $this->question2->id => $this->wrong2->id,
        ];

        $result = $this->service->grade($this->lesson, $answers);

        $this->assertTrue($result['results'][$this->question1->id]['correct']);
        $this->assertFalse($result['results'][$this->question2->id]['correct']);
        $this->assertEquals($this->correct1->id, $result['results'][$this->question1->id]['correct_option']);
        $this->assertEquals($this->wrong2->id, $result['results'][$this->question2->id]['selected']);
    }

    public function test_grade_handles_unanswered_questions(): void
    {
        $result = $this->service->grade($this->lesson, []);

        $this->assertEquals(0, $result['score']);
        $this->assertFalse($result['passed']);
        $this->assertNull($result['results'][$this->question1->id]['selected']);
    }
}
```

- [ ] **Step 2: Ejecutar los tests para verificar que fallan**

```bash
php artisan test tests/Unit/Services/QuizServiceTest.php
```

Expected: FAIL — `Class "App\Services\QuizService" not found`

- [ ] **Step 3: Implementar `QuizService`**

Crear `app/Services/QuizService.php`:

```php
<?php

namespace App\Services;

use App\Models\Lesson;

class QuizService
{
    public function grade(Lesson $lesson, array $answers): array
    {
        $questions = $lesson->questions()->with('options')->orderBy('sort_order')->get();
        $total     = $questions->count();
        $correct   = 0;
        $results   = [];

        foreach ($questions as $question) {
            $selectedId    = isset($answers[$question->id]) ? (int) $answers[$question->id] : null;
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect     = $selectedId !== null
                && $correctOption !== null
                && $selectedId === $correctOption->id;

            if ($isCorrect) {
                $correct++;
            }

            $results[$question->id] = [
                'correct'        => $isCorrect,
                'selected'       => $selectedId,
                'correct_option' => $correctOption?->id,
            ];
        }

        $score  = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $passed = $score >= $lesson->passingScore();

        return [
            'score'   => $score,
            'passed'  => $passed,
            'correct' => $correct,
            'total'   => $total,
            'results' => $results,
        ];
    }
}
```

- [ ] **Step 4: Ejecutar los tests para verificar que pasan**

```bash
php artisan test tests/Unit/Services/QuizServiceTest.php
```

Expected: PASS (6 tests, 16 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/QuizService.php tests/Unit/Services/QuizServiceTest.php
git commit -m "feat: add QuizService with dynamic passing score via passingScore()"
```

---

## Task 6: Refactor ProgressController

**Files:**
- Modify: `app/Http/Controllers/Student/ProgressController.php`
- Create: `tests/Feature/Student/ProgressControllerTest.php`

- [ ] **Step 1: Escribir el test fallido**

Crear `tests/Feature/Student/ProgressControllerTest.php`:

```php
<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;
    private Lesson $lesson1;
    private Lesson $lesson2;

    protected function setUp(): void
    {
        parent::setUp();

        $instructor    = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course  = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'status'        => 'published',
            'is_free'       => true,
        ]);
        $module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);
        $this->lesson1 = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Lesson 1',
            'slug'       => 'lesson-1',
            'type'       => 'video',
            'sort_order' => 1,
            'is_preview' => true,
        ]);
        $this->lesson2 = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Lesson 2',
            'slug'       => 'lesson-2',
            'type'       => 'video',
            'sort_order' => 2,
        ]);
        Enrollment::create([
            'user_id'     => $this->student->id,
            'course_id'   => $this->course->id,
            'progress'    => 0,
            'enrolled_at' => now(),
        ]);
    }

    public function test_update_returns_json_with_progress(): void
    {
        $this->actingAs($this->student)
            ->postJson("/progress/{$this->lesson1->id}", ['position' => 10])
            ->assertOk()
            ->assertJsonStructure(['progress', 'nextLesson']);
    }

    public function test_update_marks_lesson_as_completed(): void
    {
        $this->actingAs($this->student)
            ->postJson("/progress/{$this->lesson1->id}");

        $this->assertDatabaseHas('lesson_progress', [
            'user_id'   => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
        ]);
    }

    public function test_update_calculates_correct_percentage(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/progress/{$this->lesson1->id}");

        $response->assertJson(['progress' => 50]); // 1 of 2 lessons
    }

    public function test_update_returns_next_lesson_when_available(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/progress/{$this->lesson1->id}");

        $response->assertJsonPath('nextLesson.id', $this->lesson2->id);
    }

    public function test_update_requires_authentication(): void
    {
        $this->postJson("/progress/{$this->lesson1->id}")
            ->assertUnauthorized();
    }
}
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

```bash
php artisan test tests/Feature/Student/ProgressControllerTest.php
```

Expected: FAIL — los tests pasan actualmente con el controlador viejo, pero después del refactor deben seguir pasando. Si ya pasan, pasar al Step 3.

- [ ] **Step 3: Reemplazar `app/Http/Controllers/Student/ProgressController.php`**

```php
<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(private readonly ProgressService $progress) {}

    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $user    = $request->user();
        $course  = $lesson->module->course;

        $this->progress->completeLesson($user, $lesson, $request->input('position', 0));
        $percent    = $this->progress->recalculateCourseProgress($user, $course);
        $nextLesson = $this->progress->getNextLesson($user, $course, $lesson);

        return response()->json([
            'progress'   => $percent,
            'nextLesson' => $nextLesson,
        ]);
    }
}
```

- [ ] **Step 4: Ejecutar los tests para verificar que pasan**

```bash
php artisan test tests/Feature/Student/ProgressControllerTest.php
```

Expected: PASS (5 tests, 7 assertions)

- [ ] **Step 5: Ejecutar todos los tests para verificar no hay regresiones**

```bash
php artisan test
```

Expected: All tests passing.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Student/ProgressController.php tests/Feature/Student/ProgressControllerTest.php
git commit -m "refactor: ProgressController delegates to ProgressService"
```

---

## Task 7: Refactor QuizController + LessonController + frontend del quiz (commit atómico)

> Estos tres cambios son atómicos: si se hace el QuizController sin el frontend, el quiz queda roto en el browser. Se commitean juntos.

**Files:**
- Modify: `app/Http/Controllers/Student/QuizController.php`
- Modify: `app/Http/Controllers/Student/LessonController.php`
- Modify: `resources/views/courses/learn.blade.php`
- Create: `tests/Feature/Student/QuizControllerTest.php`

- [ ] **Step 1: Escribir los tests fallidos**

Crear `tests/Feature/Student/QuizControllerTest.php`:

```php
<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;
    private Lesson $lesson;
    private QuizQuestion $question;
    private QuizOption $correctOption;
    private QuizOption $wrongOption;

    protected function setUp(): void
    {
        parent::setUp();

        $instructor    = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course  = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'is_free'       => true,
        ]);
        $module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);
        $this->lesson = Lesson::create([
            'module_id'   => $module->id,
            'title'       => 'Quiz Lesson',
            'slug'        => 'quiz-lesson',
            'type'        => 'quiz',
            'sort_order'  => 1,
            'is_preview'  => true,
        ]);
        $this->question = QuizQuestion::create([
            'lesson_id'  => $this->lesson->id,
            'question'   => 'What is 2+2?',
            'sort_order' => 1,
        ]);
        $this->correctOption = QuizOption::create([
            'question_id' => $this->question->id,
            'text'        => '4',
            'is_correct'  => true,
        ]);
        $this->wrongOption = QuizOption::create([
            'question_id' => $this->question->id,
            'text'        => '5',
            'is_correct'  => false,
        ]);
        Enrollment::create([
            'user_id'     => $this->student->id,
            'course_id'   => $this->course->id,
            'progress'    => 0,
            'enrolled_at' => now(),
        ]);
    }

    public function test_submit_returns_json_response(): void
    {
        $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->correctOption->id],
            ])
            ->assertOk()
            ->assertJsonStructure(['score', 'passed', 'correct', 'total', 'results']);
    }

    public function test_submit_with_correct_answer_marks_lesson_completed(): void
    {
        $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->correctOption->id],
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id'   => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => true,
        ]);
    }

    public function test_submit_with_wrong_answer_does_not_mark_completed(): void
    {
        $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->wrongOption->id],
            ]);

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id'   => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => true,
        ]);
    }

    public function test_submit_passed_includes_progress_in_response(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->correctOption->id],
            ]);

        $response->assertJsonPath('passed', true);
        $response->assertJsonPath('progress', 100);
    }

    public function test_submit_failed_does_not_include_progress_in_response(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->wrongOption->id],
            ]);

        $response->assertJsonPath('passed', false);
        $response->assertJsonMissingPath('progress');
    }

    public function test_submit_requires_authentication(): void
    {
        $this->postJson("/quiz/{$this->lesson->id}", [])
            ->assertUnauthorized();
    }
}
```

- [ ] **Step 2: Ejecutar los tests para verificar que fallan**

```bash
php artisan test tests/Feature/Student/QuizControllerTest.php
```

Expected: FAIL — QuizController actual hace redirect, no devuelve JSON.

- [ ] **Step 3: Reemplazar `app/Http/Controllers/Student/QuizController.php`**

```php
<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\ProgressService;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService     $quiz,
        private readonly ProgressService $progress,
    ) {}

    public function submit(Request $request, Lesson $lesson): JsonResponse
    {
        $user   = $request->user();
        $result = $this->quiz->grade($lesson, $request->input('answers', []));

        if ($result['passed']) {
            $course               = $lesson->module->course;
            $this->progress->completeLesson($user, $lesson);
            $result['progress']   = $this->progress->recalculateCourseProgress($user, $course);
            $result['nextLesson'] = $this->progress->getNextLesson($user, $course, $lesson);
        }

        return response()->json($result);
    }
}
```

- [ ] **Step 4: Actualizar `app/Http/Controllers/Student/LessonController.php`**

Eliminar la línea `$quizResults = session('quiz_results');` (línea 59) y quitarla del `compact()`:

Cambiar de:
```php
$quizResults = session('quiz_results');

return view('courses.learn', compact('course', 'lesson', 'progress', 'allLessons', 'quizQuestions', 'quizResults'));
```

A:
```php
return view('courses.learn', compact('course', 'lesson', 'progress', 'allLessons', 'quizQuestions'));
```

- [ ] **Step 5: Actualizar la sección quiz de `resources/views/courses/learn.blade.php`**

Reemplazar el bloque del quiz (desde `{{-- Quiz --}}` hasta `{{-- Lección normal --}}`, líneas 166–221) con:

```blade
{{-- Quiz --}}
@if($lesson->type === 'quiz')
    @if($quizQuestions->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700">
            Este quiz aún no tiene preguntas configuradas.
        </div>
    @else
        <div x-data="{
            results: null,
            submitting: false,
            passingScore: {{ $lesson->passingScore() }},
            async submitQuiz(form) {
                this.submitting = true;
                const formData = new FormData(form);
                const answers = {};
                for (const [key, value] of formData.entries()) {
                    const match = key.match(/answers\[(\d+)\]/);
                    if (match) answers[parseInt(match[1])] = parseInt(value);
                }
                try {
                    const res = await fetch('{{ route('quiz.submit', $lesson) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ answers })
                    });
                    const data = await res.json();
                    this.results = data;
                    if (data.passed) {
                        const bar = document.getElementById('progress-bar');
                        const label = document.getElementById('progress-label');
                        if (bar && data.progress !== undefined) bar.style.width = data.progress + '%';
                        if (label && data.progress !== undefined) label.textContent = data.progress + '%';
                        if (data.nextLesson) unlockNextLesson(data.nextLesson);
                    }
                } finally {
                    this.submitting = false;
                }
            }
        }">
            {{-- Banner de resultados --}}
            <div x-show="results !== null" x-cloak
                 class="mb-6 p-5 rounded-xl border"
                 :class="results?.passed ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                <p class="font-semibold text-lg"
                   :class="results?.passed ? 'text-green-700' : 'text-red-700'"
                   x-text="results?.passed
                       ? `¡Aprobaste! 🎉 — ${results?.score}% (${results?.correct}/${results?.total} correctas)`
                       : `No aprobaste esta vez — ${results?.score}% (${results?.correct}/${results?.total} correctas)`">
                </p>
                <p x-show="results !== null && !results?.passed"
                   class="text-sm text-red-600 mt-1"
                   x-text="`Necesitas al menos ${passingScore}% para pasar. Intenta de nuevo.`">
                </p>
            </div>

            <form @submit.prevent="submitQuiz($el)" class="space-y-6">
                @csrf
                @foreach($quizQuestions as $i => $question)
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <p class="font-medium text-gray-800 mb-3">{{ $i + 1 }}. {{ $question->question }}</p>
                    <div class="space-y-2">
                        @foreach($question->options as $option)
                        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                               :class="{
                                   'border-green-400 bg-green-50': results && {{ $option->is_correct ? 'true' : 'false' }},
                                   'border-red-400 bg-red-50': results && !{{ $option->is_correct ? 'true' : 'false' }} && results.results?.[{{ $question->id }}]?.selected === {{ $option->id }}
                               }">
                            <input type="radio"
                                   name="answers[{{ $question->id }}]"
                                   value="{{ $option->id }}"
                                   class="text-indigo-600">
                            <span class="text-sm text-gray-700">{{ $option->text }}</span>
                            <span x-show="results && {{ $option->is_correct ? 'true' : 'false' }}"
                                  class="ml-auto text-xs text-green-600 font-medium">✓ Correcta</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach

                <button type="submit"
                        :disabled="submitting"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white px-6 py-3 rounded-lg font-medium transition"
                        x-text="results ? 'Intentar de nuevo' : (submitting ? 'Enviando...' : 'Enviar respuestas')">
                </button>
            </form>
        </div>
    @endif

{{-- Lección normal: indicador de estado --}}
```

- [ ] **Step 6: Extraer `unlockNextLesson` como función global en el `<script>` del view**

En el bloque `<script>` al final de `learn.blade.php`, reemplazar el bloque `if (data.nextLesson)` dentro de `attachCheckbox` con una llamada a la función, y agregar la función separada. Cambiar de:

```js
if (data.nextLesson) {
    const locked = document.querySelector(`[data-locked-lesson="${data.nextLesson.id}"]`);
    if (locked) {
        const icon = icons[data.nextLesson.type] || '📄';
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 px-3 py-2.5 border-l-4 border-transparent hover:bg-gray-50 transition';
        div.innerHTML = `
            <span class="flex-shrink-0 text-xs">${icon}</span>
            <a href="${data.nextLesson.url}" class="flex-1 text-sm line-clamp-2 text-gray-700">${data.nextLesson.title}</a>
            <input type="checkbox" data-lesson="${data.nextLesson.id}"
                   class="lesson-check flex-shrink-0 w-4 h-4 rounded cursor-pointer accent-indigo-600"
                   title="Marcar como completada">
        `;
        locked.replaceWith(div);
        attachCheckbox(div.querySelector('.lesson-check'));
    }
}
```

A:

```js
if (data.nextLesson) unlockNextLesson(data.nextLesson);
```

Y agregar antes del evento `DOMContentLoaded` (o al comienzo del script):

```js
function unlockNextLesson(nextLesson) {
    const locked = document.querySelector(`[data-locked-lesson="${nextLesson.id}"]`);
    if (!locked) return;
    const icon = icons[nextLesson.type] || '📄';
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 px-3 py-2.5 border-l-4 border-transparent hover:bg-gray-50 transition';
    div.innerHTML = `
        <span class="flex-shrink-0 text-xs">${icon}</span>
        <a href="${nextLesson.url}" class="flex-1 text-sm line-clamp-2 text-gray-700">${nextLesson.title}</a>
        <input type="checkbox" data-lesson="${nextLesson.id}"
               class="lesson-check flex-shrink-0 w-4 h-4 rounded cursor-pointer accent-indigo-600"
               title="Marcar como completada">
    `;
    locked.replaceWith(div);
    attachCheckbox(div.querySelector('.lesson-check'));
}
```

- [ ] **Step 7: Ejecutar los tests para verificar que pasan**

```bash
php artisan test tests/Feature/Student/QuizControllerTest.php
```

Expected: PASS (6 tests, 8 assertions)

- [ ] **Step 8: Ejecutar toda la suite**

```bash
php artisan test
```

Expected: All tests passing.

- [ ] **Step 9: Commit atómico**

```bash
git add app/Http/Controllers/Student/QuizController.php \
        app/Http/Controllers/Student/LessonController.php \
        resources/views/courses/learn.blade.php \
        tests/Feature/Student/QuizControllerTest.php
git commit -m "refactor: QuizController returns JSON, quiz frontend uses Alpine.js fetch"
```

---

## Verificación final

- [ ] **Ejecutar toda la suite de tests**

```bash
php artisan test --verbose
```

Expected: All tests PASS. Sin warnings de deprecación bloqueantes.

- [ ] **Verificar que no quedan referencias a `session('quiz_results')`**

```bash
grep -r "quiz_results" resources/ app/
```

Expected: Sin resultados.

- [ ] **Verificar que `app/Services/` tiene los tres servicios**

```bash
ls app/Services/
```

Expected:
```
CertificateService.php  ProgressService.php  QuizService.php
```
