<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\Quiz\Admin\QuizAnalyticsController;
use App\Http\Controllers\Quiz\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Quiz\Admin\QuestionController;
use App\Http\Controllers\Quiz\Student\AttemptController;
use App\Http\Controllers\Quiz\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Quiz\Student\ResultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/my-courses', [ProgressController::class, 'myCourses']);
    Route::get('/completed-lessons', [ProgressController::class, 'completedLessons']);
    
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/lessons/{lesson}/progress', [ProgressController::class, 'syncProgress']);
        Route::post('/lessons/{lesson}/notes', [ProgressController::class, 'saveNotes']);
        Route::post('/progress/ping', [ProgressController::class, 'ping']);
    });

    Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show']);

    Route::get('/lessons/{id}', [LessonController::class, 'show']);

    Route::get('/progress/{lesson}', [ProgressController::class, 'show']);

    // ─── Student Quiz Routes ──────────────────────────────────────────────────
    Route::get('/quizzes', [StudentQuizController::class, 'index']);
    Route::get('/quizzes/{quiz}', [StudentQuizController::class, 'show']);

    // My results
    Route::get('/my/quiz-results', [ResultController::class, 'myResults']);

    // Attempt lifecycle
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/quizzes/{quiz}/attempts', [AttemptController::class, 'start']);
    });

    Route::get('/quizzes/{quiz}/attempts/{attempt}', [AttemptController::class, 'show']);

    Route::middleware('throttle:120,1')->group(function () {
        Route::put('/quizzes/{quiz}/attempts/{attempt}/answers', [AttemptController::class, 'saveAnswers']);
    });

    Route::post('/quizzes/{quiz}/attempts/{attempt}/submit', [AttemptController::class, 'submit']);

    // Results
    Route::get('/quizzes/{quiz}/results/{result}', [ResultController::class, 'show']);

    // ─── Admin-only Routes ────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Existing admin routes
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/courses', [AdminController::class, 'courses']);
        Route::post('/courses', [AdminController::class, 'storeCourse']);
        Route::put('/courses/{id}', [AdminController::class, 'updateCourse']);
        Route::delete('/courses/{id}', [AdminController::class, 'deleteCourse']);
        Route::post('/lessons', [AdminController::class, 'storeLesson']);
        Route::put('/lessons/{id}', [AdminController::class, 'updateLesson']);
        Route::delete('/lessons/{id}', [AdminController::class, 'deleteLesson']);

        Route::post('/lesson-groups', [AdminController::class, 'storeLessonGroup']);
        Route::put('/lesson-groups/{id}', [AdminController::class, 'updateLessonGroup']);
        Route::delete('/lesson-groups/{id}', [AdminController::class, 'deleteLessonGroup']);

        // ─── Quiz Management ──────────────────────────────────────────────────
        Route::get('/quizzes', [AdminQuizController::class, 'index']);
        Route::post('/quizzes', [AdminQuizController::class, 'store']);
        Route::get('/quizzes/{id}', [AdminQuizController::class, 'show']);
        Route::put('/quizzes/{id}', [AdminQuizController::class, 'update']);
        Route::delete('/quizzes/{id}', [AdminQuizController::class, 'destroy']);
        Route::post('/quizzes/{id}/publish', [AdminQuizController::class, 'publish']);
        Route::post('/quizzes/{id}/archive', [AdminQuizController::class, 'archive']);
        Route::post('/quizzes/{id}/duplicate', [AdminQuizController::class, 'duplicate']);
        Route::post('/quizzes/{id}/questions/sync', [AdminQuizController::class, 'syncQuestions']);

        // ─── Quiz Results & Attempts (Admin) ──────────────────────────────────
        Route::get('/quizzes/{quiz}/analytics', [QuizAnalyticsController::class, 'show']);

        // ─── Question Bank ────────────────────────────────────────────────────
        Route::get('/questions', [QuestionController::class, 'index']);
        Route::post('/questions', [QuestionController::class, 'store']);
        Route::put('/questions/{id}', [QuestionController::class, 'update']);
        Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);

        // ─── Platform Analytics Overview ─────────────────────────────────────
        Route::get('/analytics/overview', [QuizAnalyticsController::class, 'overview']);
    });
});

