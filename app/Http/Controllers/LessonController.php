<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /**
     * Display the specified lesson details, eager loading its course.
     */
    public function show(int|string $id): JsonResponse
    {
        $lesson = Lesson::with('course')->findOrFail($id);

        return response()->json($lesson);
    }
}
