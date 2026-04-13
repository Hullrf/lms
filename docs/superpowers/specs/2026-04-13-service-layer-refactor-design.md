# Service Layer Refactor — Design Spec
**Date:** 2026-04-13
**Status:** Approved

## Objetivo

Extraer la lógica de negocio de `ProgressController` y `QuizController` hacia una capa de servicios reutilizable, eliminando la duplicación de código y mejorando la testabilidad del sistema.

---

## Problema actual

- La lógica de recalcular progreso de matrícula está **duplicada** en `ProgressController` (líneas 31–53) y `QuizController` (líneas 48–62).
- El score mínimo para aprobar un quiz está **hardcodeado en 70** en `QuizController`.
- `QuizController` devuelve un redirect con datos en sesión; `ProgressController` devuelve JSON. Comportamiento inconsistente.
- Los controladores son difíciles de testear porque mezclan HTTP y lógica de negocio.

---

## Arquitectura — Propuesta A (Service Layer clásico)

### Archivos nuevos

```
app/Services/ProgressService.php
app/Services/CertificateService.php
app/Services/QuizService.php
database/migrations/xxxx_add_passing_score_to_lessons.php
```

### Archivos modificados

```
app/Http/Controllers/Student/ProgressController.php
app/Http/Controllers/Student/QuizController.php
app/Models/Lesson.php
```

### Flujo de datos

```
ProgressController::update()
    └── ProgressService::completeLesson()
            └── ProgressService::recalculateCourseProgress()
                    └── CertificateService::issueIfCompleted()

QuizController::submit()
    └── QuizService::grade()
    └── (si passed) ProgressService::completeLesson()
                        └── ProgressService::recalculateCourseProgress()
                                └── CertificateService::issueIfCompleted()
```

---

## Base de datos

### Nueva columna: `lessons.passing_score`

```sql
ALTER TABLE lessons ADD COLUMN passing_score TINYINT UNSIGNED NULL;
```

- `null` → fallback a **70** (definido en `QuizService`)
- Rango válido: 0–100
- Ubicada en `lessons` para granularidad por quiz individual

### Helper en `Lesson` model

```php
public function passingScore(): int
{
    return $this->passing_score ?? 70;
}
```

---

## Servicios

### ProgressService

```php
namespace App\Services;

class ProgressService
{
    public function __construct(
        private readonly CertificateService $certificateService
    ) {}

    public function completeLesson(User $user, Lesson $lesson, int $position = 0): LessonProgress;
    public function recalculateCourseProgress(User $user, Course $course): int;
}
```

**`completeLesson(User, Lesson, int): LessonProgress`**
1. `LessonProgress::updateOrCreate` con `completed = true`, `completed_at = now()`, `last_position = $position`
2. Retorna el `LessonProgress` resultante

**`recalculateCourseProgress(User, Course): int`**
1. Obtiene todos los `lesson_id` del curso via `modules()->pluck('id')` + `Lesson::whereIn(...)->pluck('id')`
2. Cuenta los completados por el user en `LessonProgress`
3. Calcula porcentaje: `(int) round($completed / $total * 100)`
4. Actualiza `enrollment->progress` y `enrollment->completed_at` (null si < 100, `now()` si = 100)
5. Si `$percent === 100` → llama `CertificateService::issueIfCompleted($user, $course)`
6. Retorna el porcentaje (`int`)

---

### CertificateService

```php
namespace App\Services;

class CertificateService
{
    public function issueIfCompleted(User $user, Course $course): ?Certificate;
}
```

**`issueIfCompleted(User, Course): ?Certificate`**
1. Verifica que el enrollment tenga `progress === 100`; si no, retorna `null`
2. `Certificate::firstOrCreate(['user_id' => ..., 'course_id' => ...], ['code' => Str::uuid(), 'issued_at' => now()])`
3. Retorna el `Certificate` (nuevo o existente), o `null`
4. Idempotente — múltiples llamadas no generan duplicados

**Relación con `CertificateController`:** el controller de show/download no se modifica. `issueIfCompleted` garantiza que el certificado ya existe en BD cuando el estudiante lo solicita.

---

### QuizService

```php
namespace App\Services;

class QuizService
{
    public function grade(Lesson $lesson, array $answers): array;
}
```

**`grade(Lesson, array): array`**
1. Carga `$lesson->questions()->with('options')->orderBy('sort_order')->get()`
2. Por cada pregunta: evalúa `$answers[$question->id]` contra `option->is_correct`
3. Score: `(int) round($correct / $total * 100)`
4. Passed: `$score >= $lesson->passingScore()`
5. Retorna:

```php
[
    'score'   => int,    // 0–100
    'passed'  => bool,
    'correct' => int,
    'total'   => int,
    'results' => [
        $questionId => [
            'correct'        => bool,
            'selected'       => int|null,
            'correct_option' => int|null,
        ],
    ],
]
```

**Responsabilidad única:** solo califica. No toca progreso ni matrícula.

---

## Controladores refactorizados

### ProgressController

```php
class ProgressController extends Controller
{
    public function __construct(private readonly ProgressService $progress) {}

    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $user    = $request->user();
        $course  = $lesson->module->course;

        $this->progress->completeLesson($user, $lesson, $request->input('position', 0));
        $percent = $this->progress->recalculateCourseProgress($user, $course);
        $next    = $this->resolveNextLesson($user, $course, $lesson);

        return response()->json(['progress' => $percent, 'nextLesson' => $next]);
    }

    private function resolveNextLesson(User $user, Course $course, Lesson $lesson): ?array { ... }
}
```

### QuizController

```php
class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService     $quiz,
        private readonly ProgressService $progress,
    ) {}

    public function submit(Request $request, Lesson $lesson): JsonResponse
    {
        $result = $this->quiz->grade($lesson, $request->input('answers', []));

        if ($result['passed']) {
            $this->progress->completeLesson($request->user(), $lesson);
            $this->progress->recalculateCourseProgress($request->user(), $lesson->module->course);
        }

        return response()->json($result);
    }
}
```

---

## Frontend — cambios necesarios

`QuizController` cambia de redirect a JSON. La vista del quiz (`resources/views/student/lesson/quiz.blade.php` o similar) debe actualizarse:

- **Antes:** `<form method="POST">` → espera redirect con `session('quiz_results')`
- **Después:** `fetch` o `axios.post` con Alpine.js → muestra resultados en la misma página sin recargar

El componente Alpine.js del quiz manejará el estado `results`, `score`, `passed` y renderizará el feedback inline.

---

## Principios aplicados

| Principio | Aplicación |
|---|---|
| **SRP** | Cada servicio tiene una sola razón para cambiar |
| **DRY** | Lógica de recalcular progreso en un único lugar |
| **Testability** | Servicios sin dependencia de `Request` — testeables con unit tests puros |
| **Idempotencia** | `CertificateService::issueIfCompleted` seguro ante llamadas múltiples |
| **Inyección de dependencias** | Via constructor, compatible con Service Container de Laravel |

---

## Optimizaciones futuras (fuera de scope)

- Convertir `IssueCertificate` a un Job encolado cuando el volumen lo justifique
- Evento `LessonCompleted` / `CourseCompleted` para desacoplar notificaciones, badges, etc.
- `default_passing_score` en `courses` como segundo nivel de fallback (actualmente: lesson → 70)
