# Route Security & Organization — Design Spec
**Date:** 2026-04-13
**Status:** Approved

## Objetivo

Auditar y corregir la organización de rutas de `routes/web.php` para cerrar brechas de seguridad donde rutas que requieren autenticación están expuestas sin middleware, y reorganizar el archivo en grupos semánticos legibles.

---

## Problemas actuales

| Ruta | Problema |
|---|---|
| `GET /profile` | Sin `auth` — accesible sin login |
| `PATCH /profile` | Sin `auth` — accesible sin login |
| `GET /certificates/{course}` | Sin `auth` — accesible sin login |
| `GET /certificates/{course}/download` | Sin `auth` — accesible sin login |
| `GET /checkout/{course}` | Sin `auth` — accesible sin login |
| `POST /checkout/{course}/process` | Sin `auth` — accesible sin login |
| `POST /progress/{lesson}` | Tiene `auth` pero sin `enrolled` — cualquier usuario autenticado puede enviar progreso |
| `POST /quiz/{lesson}` | Tiene `auth` pero sin `enrolled` — cualquier usuario autenticado puede enviar respuestas |

---

## Cambios

### 1. `app/Models/User.php` — Activar verificación de email

Implementar la interfaz `MustVerifyEmail`:

```php
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
```

**Efecto:** Al registrarse, el usuario recibe un email de verificación. El middleware `verified` redirige a `/verify-email` si no ha verificado. Usuarios ya registrados sin verificar son bloqueados en checkout y certificados. Las vistas y el mailer ya están configurados por Breeze.

### 2. `app/Http/Middleware/IsEnrolled.php` — Resolver curso desde lección

El middleware actual requiere `{course}` en la URL. Las rutas `/progress/{lesson}` y `/quiz/{lesson}` solo tienen `{lesson}`. Actualizar para resolver el curso desde la lección cuando `{course}` no esté en la ruta:

```php
$course = $request->route('course');

if (!$course) {
    $lesson = $request->route('lesson');
    $course = $lesson->module->course;
}
```

### 3. `routes/web.php` — Reorganización semántica

Cuatro bloques semánticos:

```
PÚBLICAS           →  sin middleware
AUTH               →  middleware: auth
  ├── ENROLLED     →  middleware: enrolled (anidado en auth)
  └── VERIFIED     →  middleware: verified (anidado en auth)
ADMIN              →  middleware: auth + admin/instructor.or.admin
```

**Los nombres de rutas no cambian** — cero impacto en Blade y controladores existentes.

---

## Estructura final de `routes/web.php`

```php
<?php

use App\Http\Controllers\Student;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\CourseController;
use App\Http\Controllers\Student\LessonController;
use App\Http\Controllers\Student\EnrollmentController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\CertificateController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

// ─── Públicas ────────────────────────────────────────────────────────────────
Route::get('/', [CourseController::class, 'index'])->name('home');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{slug}', [CourseController::class, 'show'])->name('courses.show');

// ─── Autenticadas ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/enroll/{course}', [EnrollmentController::class, 'store'])->name('enroll');

    // Perfil
    Route::get('/profile', [Student\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [Student\ProfileController::class, 'update'])->name('profile.update');

    // Lecciones, progreso y quiz — requieren matrícula
    Route::middleware('enrolled')->group(function () {
        Route::get('/learn/{course}/{lesson}', [LessonController::class, 'show'])
             ->name('lesson.show');
        Route::post('/progress/{lesson}', [ProgressController::class, 'update'])
             ->name('progress.update');
        Route::post('/quiz/{lesson}', [Student\QuizController::class, 'submit'])
             ->name('quiz.submit');
    });

    // Acciones críticas — requieren email verificado
    Route::middleware('verified')->group(function () {
        Route::get('/certificates/{course}', [CertificateController::class, 'show'])
             ->name('certificates.show');
        Route::get('/certificates/{course}/download', [CertificateController::class, 'download'])
             ->name('certificates.download');
        Route::get('/checkout/{course}', [PaymentController::class, 'checkout'])
             ->name('checkout');
        Route::post('/checkout/{course}/process', [PaymentController::class, 'process'])
             ->name('checkout.process');
    });
});

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    Route::middleware('admin')->group(function () {
        Route::resource('users', Admin\UserController::class);
        Route::get('categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::patch('categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::middleware('instructor.or.admin')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('courses', Admin\CourseController::class);
        Route::resource('courses.modules', Admin\ModuleController::class)->shallow();
        Route::resource('modules.lessons', Admin\LessonController::class)->shallow();

        Route::get('lessons/{lesson}/quiz', [Admin\QuizController::class, 'edit'])->name('quiz.edit');
        Route::post('lessons/{lesson}/quiz/questions', [Admin\QuizController::class, 'storeQuestion'])->name('quiz.questions.store');
        Route::delete('quiz-questions/{question}', [Admin\QuizController::class, 'destroyQuestion'])->name('quiz.questions.destroy');
        Route::post('quiz-questions/{question}/options', [Admin\QuizController::class, 'storeOption'])->name('quiz.options.store');
        Route::delete('quiz-options/{option}', [Admin\QuizController::class, 'destroyOption'])->name('quiz.options.destroy');

        Route::post('courses/{course}/collaborators', [Admin\CollaboratorController::class, 'store'])->name('courses.collaborators.store');
        Route::delete('courses/{course}/collaborators/{user}', [Admin\CollaboratorController::class, 'destroy'])->name('courses.collaborators.destroy');

        Route::post('lessons/{lesson}/resources', [Admin\ResourceController::class, 'store'])->name('lessons.resources.store');
        Route::delete('resources/{resource}', [Admin\ResourceController::class, 'destroy'])->name('resources.destroy');
    });
});

require __DIR__ . '/auth.php';
```

---

## Tabla de seguridad final

| Ruta | Middleware aplicado |
|---|---|
| `GET /` | ninguno |
| `GET /courses` | ninguno |
| `GET /courses/{slug}` | ninguno |
| `GET /dashboard` | `auth` |
| `POST /enroll/{course}` | `auth` |
| `GET /profile` | `auth` |
| `PATCH /profile` | `auth` |
| `GET /learn/{course}/{lesson}` | `auth` + `enrolled` |
| `POST /progress/{lesson}` | `auth` + `enrolled` |
| `POST /quiz/{lesson}` | `auth` + `enrolled` |
| `GET /certificates/{course}` | `auth` + `verified` |
| `GET /certificates/{course}/download` | `auth` + `verified` |
| `GET /checkout/{course}` | `auth` + `verified` |
| `POST /checkout/{course}/process` | `auth` + `verified` |
| `GET /admin/*` | `auth` + `admin` o `instructor.or.admin` |

---

## Buenas prácticas aplicadas

- **Defensa en profundidad:** middleware a nivel de ruta, no solo en el controlador
- **Principio de menor privilegio:** cada ruta tiene exactamente los middlewares que necesita
- **Legibilidad:** la indentación comunica el nivel de acceso requerido
- **Sin breaking changes:** nombres de rutas idénticos a los actuales
- **`verified` solo donde importa:** acciones con consecuencias económicas o de datos sensibles
- **`enrolled` donde había brecha:** progress y quiz ahora protegidos a nivel de middleware

---

## Archivos modificados

| Archivo | Cambio |
|---|---|
| `app/Models/User.php` | Implementar `MustVerifyEmail` |
| `app/Http/Middleware/IsEnrolled.php` | Resolver curso desde lección si no hay `{course}` |
| `routes/web.php` | Reorganización completa en grupos semánticos |
