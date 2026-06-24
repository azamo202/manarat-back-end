<?php

namespace App\Http\Controllers\Quiz\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\StoreQuestionRequest;
use App\Http\Resources\Quiz\QuestionResource;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\QuestionOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    /**
     * GET /admin/questions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Question::with(['options', 'media'])
            ->withCount('quizzes')
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('search')) {
            $query->where('content', 'like', '%' . $request->search . '%');
        }

        return response()->json(QuestionResource::collection($query->paginate(20)));
    }

    /**
     * POST /admin/questions
     */
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $question = Question::create([
                'type'        => $validated['type'],
                'content'     => $validated['content'],
                'explanation' => $validated['explanation'] ?? null,
                'hint'        => $validated['hint'] ?? null,
                'difficulty'  => $validated['difficulty'] ?? 'medium',
                'points'      => $validated['points'],
                'created_by'  => $request->user()->id,
            ]);

            // Create options
            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $index => $optionData) {
                    QuestionOption::create([
                        'question_id'  => $question->id,
                        'content'      => $optionData['content'],
                        'is_correct'   => $optionData['is_correct'],
                        'order_number' => $optionData['order_number'] ?? $index,
                        'match_target' => $optionData['match_target'] ?? null,
                    ]);
                }
            }

            // Upload media files
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $mime = $file->getMimeType();
                    $type = str_starts_with($mime, 'image') ? 'image'
                          : (str_starts_with($mime, 'audio') ? 'audio' : 'video');

                    $path = $file->store("quiz-media/{$question->id}", 'public');

                    QuestionMedia::create([
                        'question_id' => $question->id,
                        'type'        => $type,
                        'file_path'   => $path,
                        'mime_type'   => $mime,
                        'file_size'   => $file->getSize(),
                    ]);
                }
            }

            $question->load(['options', 'media']);

            return response()->json([
                'message'  => 'تم إضافة السؤال بنجاح.',
                'question' => new QuestionResource($question),
            ], 201);
        });
    }

    /**
     * PUT /admin/questions/{question}
     */
    public function update(StoreQuestionRequest $request, int $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $question  = Question::findOrFail($id);
            $validated = $request->validated();

            $question->update([
                'type'        => $validated['type'],
                'content'     => $validated['content'],
                'explanation' => $validated['explanation'] ?? null,
                'hint'        => $validated['hint'] ?? null,
                'difficulty'  => $validated['difficulty'] ?? 'medium',
                'points'      => $validated['points'],
            ]);

            // Replace options
            if (isset($validated['options'])) {
                $question->options()->delete();
                foreach ($validated['options'] as $index => $optionData) {
                    QuestionOption::create([
                        'question_id'  => $question->id,
                        'content'      => $optionData['content'],
                        'is_correct'   => $optionData['is_correct'],
                        'order_number' => $optionData['order_number'] ?? $index,
                        'match_target' => $optionData['match_target'] ?? null,
                    ]);
                }
            }

            $question->load(['options', 'media']);

            return response()->json([
                'message'  => 'تم تحديث السؤال بنجاح.',
                'question' => new QuestionResource($question),
            ]);
        });
    }

    /**
     * DELETE /admin/questions/{question}
     */
    public function destroy(int $id): JsonResponse
    {
        $question = Question::findOrFail($id);

        // Delete media files from storage
        foreach ($question->media as $media) {
            Storage::disk('public')->delete($media->file_path);
        }

        $question->delete();

        return response()->json(['message' => 'تم حذف السؤال بنجاح.']);
    }
}
