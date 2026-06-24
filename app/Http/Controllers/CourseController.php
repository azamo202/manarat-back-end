<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class CourseController extends Controller
{
    /**
     * Display a listing of active courses.
     */
    public function index(): JsonResponse
    {
        $courses = Cache::remember('courses.active', 3600, function () {
            return Course::where('is_active', true)->get();
        });

        return response()->json($courses);
    }

    /**
     * Display the specified course with its lessons ordered by order_number.
     */
    public function show(int|string $id): JsonResponse
    {
        $course = Cache::remember("course.{$id}", 3600, function () use ($id) {
            return Course::with([
                'lessonGroups' => function ($query) {
                    $query->orderBy('order_number', 'asc')->with(['lessons' => function ($query) {
                        $query->orderBy('order_number', 'asc');
                    }]);
                },
                'lessons' => function ($query) {
                    $query->whereNull('lesson_group_id')->orderBy('order_number', 'asc');
                }
            ])->findOrFail($id);
        });

        return response()->json($course);
    }
}
