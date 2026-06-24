<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class LessonController extends Controller
{
    /**
     * Display the specified lesson details, eager loading its course.
     */
    public function show(int|string $id): JsonResponse
    {
        $lesson = Cache::remember("lesson.{$id}", 3600, function () use ($id) {
            return Lesson::with('course')->findOrFail($id);
        });

        return response()->json($lesson);
    }
}
