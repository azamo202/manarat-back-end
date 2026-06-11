<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get platform statistics.
     */
    public function stats(): JsonResponse
    {
        $usersCount = User::where('is_admin', false)->count();
        $coursesCount = Course::count();
        $lessonsCount = Lesson::count();
        $totalWatches = LessonProgress::where('is_completed', true)->count();

        return response()->json([
            'users_count' => $usersCount,
            'courses_count' => $coursesCount,
            'lessons_count' => $lessonsCount,
            'total_watches' => $totalWatches,
        ]);
    }

    /**
     * Get list of users with details.
     */
    public function users(): JsonResponse
    {
        $users = User::where('is_admin', false)
            ->withCount('lessonProgress')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * Get list of all courses (active and inactive).
     */
    public function courses(): JsonResponse
    {
        $courses = Course::withCount('lessons')->orderBy('created_at', 'desc')->get();

        return response()->json($courses);
    }

    /**
     * Create a new course.
     */
    public function storeCourse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'teacher' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $course = Course::create($validated);

        return response()->json([
            'message' => 'تم إنشاء الكورس بنجاح.',
            'course' => $course,
        ], 201);
    }

    /**
     * Update an existing course.
     */
    public function updateCourse(Request $request, int|string $id): JsonResponse
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'teacher' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);

        $course->update($validated);

        return response()->json([
            'message' => 'تم تحديث الكورس بنجاح.',
            'course' => $course,
        ]);
    }

    /**
     * Delete an existing course.
     */
    public function deleteCourse(int|string $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json([
            'message' => 'تم حذف الكورس بنجاح.',
        ]);
    }

    /**
     * Store a new lesson in a course.
     */
    public function storeLesson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'youtube_video_id' => ['required', 'string', 'max:255'],
            'duration_in_seconds' => ['required', 'integer', 'min:1'],
            'order_number' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['order_number'] = $validated['order_number'] ?? 0;

        $lesson = Lesson::create($validated);

        return response()->json([
            'message' => 'تم إضافة الدرس بنجاح.',
            'lesson' => $lesson,
        ], 201);
    }

    /**
     * Update an existing lesson.
     */
    public function updateLesson(Request $request, int|string $id): JsonResponse
    {
        $lesson = Lesson::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'youtube_video_id' => ['required', 'string', 'max:255'],
            'duration_in_seconds' => ['required', 'integer', 'min:1'],
            'order_number' => ['required', 'integer', 'min:0'],
        ]);

        $lesson->update($validated);

        return response()->json([
            'message' => 'تم تحديث الدرس بنجاح.',
            'lesson' => $lesson,
        ]);
    }

    /**
     * Delete an existing lesson.
     */
    public function deleteLesson(int|string $id): JsonResponse
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return response()->json([
            'message' => 'تم حذف الدرس بنجاح.',
        ]);
    }
}
