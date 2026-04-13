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
