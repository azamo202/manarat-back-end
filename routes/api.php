<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ProgressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/courses', [CourseController::class, 'index']);

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

    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::get('/lessons/{id}', [LessonController::class, 'show']);

    Route::get('/progress/{lesson}', [ProgressController::class, 'show']);

    // Admin-only Routes
    Route::middleware('admin')->prefix('admin')->group(function () {
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
    });
});
