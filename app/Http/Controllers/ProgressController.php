<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    use AuthorizesRequests;

    /**
     * Retrieve the user's progress for the given lesson.
     */
    public function show(int|string $lessonId): JsonResponse
    {
        $progress = LessonProgress::where('user_id', Auth::id())
            ->where('lesson_id', $lessonId)
            ->first();

        return response()->json([
            'current_second' => $progress ? $progress->current_second : 0,
            'is_completed' => $progress ? (bool) $progress->is_completed : false,
            'personal_notes' => $progress ? $progress->personal_notes : null,
        ]);
    }

    /**
     * Update or create the user's progress for a lesson.
     */
    public function ping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'current_second' => ['required', 'integer', 'min:0'],
        ]);

        $user = Auth::user();
        $lesson = Lesson::findOrFail($validated['lesson_id']);

        // Check if progress record exists or construct a transient one
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if (!$progress) {
            $progress = new LessonProgress([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'current_second' => 0,
                'is_completed' => false,
            ]);
        }

        // Apply Policy authorization
        $this->authorize('update', $progress);

        $isCompleted = $progress->is_completed ||
            ($validated['current_second'] >= (0.95 * $lesson->duration_in_seconds));

        $progress->current_second = $validated['current_second'];
        $progress->is_completed = $isCompleted;
        $progress->save();

        return response()->json([
            'status' => 'success',
            'is_completed' => $isCompleted,
        ]);
    }

    /**
     * Retrieve a list of completed lesson IDs for the authenticated user.
     */
    public function completedLessons(): JsonResponse
    {
        $ids = LessonProgress::where('user_id', Auth::id())
            ->where('is_completed', true)
            ->pluck('lesson_id');

        return response()->json($ids);
    }

    /**
     * Retrieve the user's enrolled courses with lesson count and last watched lesson.
     */
    public function myCourses(): JsonResponse
    {
        $user = Auth::user();
        
        // Eager load lessons (only id and course_id) to avoid N+1 when plucking IDs
        $courses = \App\Models\Course::withCount('lessons')
            ->with('lessons:id,course_id')
            ->whereHas('lessons.lessonProgress', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();

        $allLessonIds = $courses->flatMap->lessons->pluck('id');

        // Fetch all progress for these lessons in ONE query
        $progresses = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $allLessonIds)
            ->with('lesson:id,title')
            ->get();

        foreach ($courses as $course) {
            $courseLessonIds = $course->lessons->pluck('id');
            
            // Filter progress in-memory for this course
            $courseProgresses = $progresses->whereIn('lesson_id', $courseLessonIds);
            
            $lastProgress = $courseProgresses->sortByDesc('updated_at')->first();
                
            $course->last_lesson = $lastProgress && $lastProgress->lesson ? $lastProgress->lesson->title : 'لم يبدأ بعد';

            // Calculate completed lessons count in-memory
            $course->completed_lessons_count = $courseProgresses->where('is_completed', true)->count();
            
            // Unset lessons relation to not bloat the JSON response
            unset($course->lessons);
        }

        return response()->json($courses);
    }

    /**
     * Sync play progress from frontend.
     */
    public function syncProgress(Request $request, int|string $lessonId): JsonResponse
    {
        $validated = $request->validate([
            'seconds' => ['required', 'integer', 'min:0'],
        ]);

        $user = Auth::user();
        $lesson = Lesson::findOrFail($lessonId);

        $progress = LessonProgress::firstOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['current_second' => 0, 'is_completed' => false]
        );

        $isCompleted = $progress->is_completed || 
            ($validated['seconds'] >= (0.95 * $lesson->duration_in_seconds));

        $progress->current_second = $validated['seconds'];
        if ($isCompleted) {
            $progress->is_completed = true;
        }
        $progress->save();

        return response()->json([
            'status' => 'success',
            'is_completed' => (bool)$progress->is_completed,
        ]);
    }

    /**
     * Save the user's personal notes for a lesson.
     */
    public function saveNotes(Request $request, int|string $lessonId): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $user = Auth::user();
        $lesson = Lesson::findOrFail($lessonId);

        $progress = LessonProgress::firstOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['current_second' => 0, 'is_completed' => false]
        );

        $progress->personal_notes = $validated['notes'] ?? null;
        $progress->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ الملاحظات بنجاح.',
        ]);
    }
}
