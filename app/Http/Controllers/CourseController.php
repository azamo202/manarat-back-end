<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of active courses.
     */
    public function index(): JsonResponse
    {
        $courses = Course::where('is_active', true)
                         ->where('section', 'homepage')
                         ->get();

        return response()->json($courses);
    }

    /**
     * Display the specified course with its lessons ordered by order_number.
     */
    public function show(int|string $id): JsonResponse
    {
        $course = Course::with([
            'lessonGroups' => function ($query) {
                $query->orderBy('order_number', 'asc')->with(['lessons' => function ($query) {
                    $query->orderBy('order_number', 'asc');
                }]);
            },
            'lessons' => function ($query) {
                $query->whereNull('lesson_group_id')->orderBy('order_number', 'asc');
            }
        ])->findOrFail($id);

        return response()->json($course);
    }
}
