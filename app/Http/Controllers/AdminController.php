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
    public function courses(Request $request): JsonResponse
    {
        $query = Course::withCount('lessons')->orderBy('created_at', 'desc');
        
        if ($request->has('section')) {
            $query->where('section', $request->query('section'));
        }
        
        return response()->json($query->get());
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
            'section' => ['nullable', 'string', 'in:homepage,general'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'plan_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $validated['section'] = $request->input('section', 'homepage');

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('courses', 'public');
            $validated['cover_image'] = $path;
        }

        if ($request->hasFile('plan_file')) {
            $path = $request->file('plan_file')->store('courses/plans', 'public');
            $validated['plan_file'] = $path;
        }

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
            'section' => ['nullable', 'string', 'in:homepage,general'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'delete_cover_image' => ['nullable', 'boolean'],
            'plan_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'delete_plan_file' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if ($request->boolean('delete_cover_image')) {
            if ($course->cover_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($course->cover_image);
            }
            $validated['cover_image'] = null;
        } elseif ($request->hasFile('cover_image')) {
            if ($course->cover_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($course->cover_image);
            }
            $path = $request->file('cover_image')->store('courses', 'public');
            $validated['cover_image'] = $path;
        }

        if ($request->boolean('delete_plan_file')) {
            if ($course->plan_file) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($course->plan_file);
            }
            $validated['plan_file'] = null;
        } elseif ($request->hasFile('plan_file')) {
            if ($course->plan_file) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($course->plan_file);
            }
            $path = $request->file('plan_file')->store('courses/plans', 'public');
            $validated['plan_file'] = $path;
        }

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
     * Store a new lesson group in a course.
     */
    public function storeLessonGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'integer', 'min:0'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $validated['order_number'] = $validated['order_number'] ?? 0;

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('lesson_groups/pdfs', 'public');
            $validated['pdf_file'] = $path;
        }

        $lessonGroup = \App\Models\LessonGroup::create($validated);

        return response()->json([
            'message' => 'تم إضافة مجموعة الدروس بنجاح.',
            'lesson_group' => $lessonGroup,
        ], 201);
    }

    /**
     * Update an existing lesson group.
     */
    public function updateLessonGroup(Request $request, int|string $id): JsonResponse
    {
        $lessonGroup = \App\Models\LessonGroup::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'order_number' => ['required', 'integer', 'min:0'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'delete_pdf_file' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('delete_pdf_file')) {
            if ($lessonGroup->pdf_file) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($lessonGroup->pdf_file);
            }
            $validated['pdf_file'] = null;
        } elseif ($request->hasFile('pdf_file')) {
            if ($lessonGroup->pdf_file) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($lessonGroup->pdf_file);
            }
            $path = $request->file('pdf_file')->store('lesson_groups/pdfs', 'public');
            $validated['pdf_file'] = $path;
        }

        $lessonGroup->update($validated);

        return response()->json([
            'message' => 'تم تحديث مجموعة الدروس بنجاح.',
            'lesson_group' => $lessonGroup,
        ]);
    }

    /**
     * Delete an existing lesson group.
     */
    public function deleteLessonGroup(int|string $id): JsonResponse
    {
        $lessonGroup = \App\Models\LessonGroup::findOrFail($id);
        
        if ($lessonGroup->pdf_file) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($lessonGroup->pdf_file);
        }
        
        $lessonGroup->delete();

        return response()->json([
            'message' => 'تم حذف مجموعة الدروس بنجاح.',
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
            'lesson_group_id' => ['nullable', 'exists:lesson_groups,id'],
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
            'lesson_group_id' => ['nullable', 'exists:lesson_groups,id'],
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
