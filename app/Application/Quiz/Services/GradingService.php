<?php

namespace App\Application\Quiz\Services;

use App\Domain\Quiz\Enums\QuestionType;
use App\Domain\Quiz\DTOs\QuizResultDTO;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\Question;

class GradingService
{
    /**
     * Grade all answers in an attempt and return a result DTO.
     */
    public function grade(QuizAttempt $attempt): QuizResultDTO
    {
        $attempt->load(['quiz.questions', 'answers.question.options']);

        $quiz      = $attempt->quiz;
        $answers   = $attempt->answers->keyBy('question_id');
        $maxScore  = 0;
        $rawScore  = 0;

        foreach ($quiz->questions as $question) {
            $pointsAvailable = $question->pivot->points_override ?? $question->points;
            $maxScore       += $pointsAvailable;

            $answer = $answers->get($question->id);

            if (!$answer) {
                // Unanswered — zero points, skip count tracked in analytics
                $this->markAnswer(null, $question, false, 0);
                continue;
            }

            [$isCorrect, $pointsEarned] = $this->gradeAnswer($question, $answer, $pointsAvailable);

            $answer->update([
                'is_correct'    => $isCorrect,
                'points_earned' => $pointsEarned,
                'graded_at'     => now(),
            ]);

            $rawScore += $pointsEarned;
        }

        $percentage = $maxScore > 0
            ? round(($rawScore / $maxScore) * 100, 2)
            : 0.00;

        $passed = $percentage >= $quiz->passing_score;

        $durationSeconds = $attempt->started_at->diffInSeconds(now());

        $attemptNumber = QuizAttempt::where('quiz_id', $quiz->id)
                                    ->where('user_id', $attempt->user_id)
                                    ->whereIn('status', ['submitted', 'timed_out'])
                                    ->count() + 1;

        return new QuizResultDTO(
            quizId:              $quiz->id,
            attemptId:           $attempt->id,
            userId:              $attempt->user_id,
            rawScore:            $rawScore,
            maxScore:            $maxScore,
            percentage:          $percentage,
            durationSeconds:     $durationSeconds,
            passed:              $passed,
            certificateEligible: $passed && $percentage >= $quiz->passing_score,
            attemptNumber:       $attemptNumber,
        );
    }

    /**
     * Grade a single answer and return [isCorrect, pointsEarned].
     */
    private function gradeAnswer(Question $question, QuizAnswer $answer, int $pointsAvailable): array
    {
        $value = $answer->answer_value;

        return match ($question->type) {
            QuestionType::MultipleChoice,
            QuestionType::TrueFalse,
            QuestionType::ImageBased,
            QuestionType::AudioBased,
            QuestionType::VideoBased  => $this->gradeMultipleChoice($question, $value, $pointsAvailable),

            QuestionType::MultipleSelect => $this->gradeMultipleSelect($question, $value, $pointsAvailable),
            QuestionType::Matching       => $this->gradeMatching($question, $value, $pointsAvailable),
            QuestionType::Ordering       => $this->gradeOrdering($question, $value, $pointsAvailable),
            QuestionType::FillInBlank    => $this->gradeFillInBlank($question, $value, $pointsAvailable),

            // Open-ended types — not auto-graded
            QuestionType::ShortText,
            QuestionType::LongText => [null, 0],
        };
    }

    private function gradeMultipleChoice(Question $question, ?array $value, int $points): array
    {
        if (empty($value['selected_option_id'])) {
            return [false, 0];
        }

        $correctIds = $question->getCorrectOptionIds();
        $isCorrect  = in_array($value['selected_option_id'], $correctIds);

        return [$isCorrect, $isCorrect ? $points : 0];
    }

    private function gradeMultipleSelect(Question $question, ?array $value, int $points): array
    {
        if (empty($value['selected_option_ids'])) {
            return [false, 0];
        }

        $correctIds  = $question->getCorrectOptionIds();
        $selectedIds = $value['selected_option_ids'];

        sort($correctIds);
        sort($selectedIds);

        $isCorrect = $correctIds === $selectedIds;

        return [$isCorrect, $isCorrect ? $points : 0];
    }

    private function gradeMatching(Question $question, ?array $value, int $points): array
    {
        if (empty($value['pairs'])) {
            return [false, 0];
        }

        $options = $question->options->keyBy('id');
        $allCorrect = true;
        $correctCount = 0;
        $totalPairs   = count($value['pairs']);

        foreach ($value['pairs'] as $pair) {
            $leftOption = $options->get($pair['left_id'] ?? null);
            if ($leftOption && $leftOption->match_target === ($pair['right_value'] ?? null)) {
                $correctCount++;
            } else {
                $allCorrect = false;
            }
        }

        // Partial credit: proportional scoring
        $earned = $totalPairs > 0
            ? (int) round(($correctCount / $totalPairs) * $points)
            : 0;

        return [$allCorrect, $earned];
    }

    private function gradeOrdering(Question $question, ?array $value, int $points): array
    {
        if (empty($value['order'])) {
            return [false, 0];
        }

        $correctOrder = $question->options->sortBy('order_number')->pluck('id')->toArray();
        $isCorrect    = $value['order'] === $correctOrder;

        return [$isCorrect, $isCorrect ? $points : 0];
    }

    private function gradeFillInBlank(Question $question, ?array $value, int $points): array
    {
        if (empty($value['answers'])) {
            return [false, 0];
        }

        $correctOptions = $question->options->sortBy('order_number')->pluck('content')->toArray();
        $studentAnswers = array_map('mb_strtolower', array_map('trim', $value['answers']));
        $correctAnswers = array_map('mb_strtolower', array_map('trim', $correctOptions));

        $correctCount = 0;
        foreach ($correctAnswers as $i => $correct) {
            if (isset($studentAnswers[$i]) && $studentAnswers[$i] === $correct) {
                $correctCount++;
            }
        }

        $total   = count($correctAnswers);
        $isCorrect = $correctCount === $total;
        $earned  = $total > 0 ? (int) round(($correctCount / $total) * $points) : 0;

        return [$isCorrect, $earned];
    }

    private function markAnswer(?QuizAnswer $answer, Question $question, bool $isCorrect, int $points): void
    {
        // Answer may not exist if student never visited the question
        if ($answer) {
            $answer->update([
                'is_correct'    => $isCorrect,
                'points_earned' => $points,
                'graded_at'     => now(),
            ]);
        }
    }
}
