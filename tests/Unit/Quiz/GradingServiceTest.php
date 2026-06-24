<?php

namespace Tests\Unit\Quiz;

use App\Application\Quiz\Services\GradingService;
use App\Domain\Quiz\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingServiceTest extends TestCase
{
    use RefreshDatabase;

    private GradingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GradingService::class);
    }

    public function test_grades_correct_multiple_choice(): void
    {
        $question = $this->makeQuestion(QuestionType::MultipleChoice, 10);
        $correct  = $this->addOption($question, 'الإجابة الصحيحة', true);
        $wrong    = $this->addOption($question, 'إجابة خاطئة', false);

        $answer   = new QuizAnswer([
            'answer_value' => ['selected_option_id' => $correct->id],
            'question_id'  => $question->id,
        ]);

        [$isCorrect, $points] = $this->callGradeAnswer($question, $answer, 10);

        $this->assertTrue($isCorrect);
        $this->assertEquals(10, $points);
    }

    public function test_grades_incorrect_multiple_choice(): void
    {
        $question = $this->makeQuestion(QuestionType::MultipleChoice, 10);
        $correct  = $this->addOption($question, 'صحيح', true);
        $wrong    = $this->addOption($question, 'خاطئ', false);

        $answer = new QuizAnswer([
            'answer_value' => ['selected_option_id' => $wrong->id],
            'question_id'  => $question->id,
        ]);

        [$isCorrect, $points] = $this->callGradeAnswer($question, $answer, 10);

        $this->assertFalse($isCorrect);
        $this->assertEquals(0, $points);
    }

    public function test_grades_correct_multiple_select(): void
    {
        $question = $this->makeQuestion(QuestionType::MultipleSelect, 10);
        $c1 = $this->addOption($question, 'أ', true);
        $c2 = $this->addOption($question, 'ب', true);
        $w1 = $this->addOption($question, 'ج', false);

        $answer = new QuizAnswer([
            'answer_value' => ['selected_option_ids' => [$c1->id, $c2->id]],
            'question_id'  => $question->id,
        ]);

        [$isCorrect, $points] = $this->callGradeAnswer($question, $answer, 10);

        $this->assertTrue($isCorrect);
        $this->assertEquals(10, $points);
    }

    public function test_grades_partial_multiple_select_as_wrong(): void
    {
        $question = $this->makeQuestion(QuestionType::MultipleSelect, 10);
        $c1 = $this->addOption($question, 'أ', true);
        $c2 = $this->addOption($question, 'ب', true);

        $answer = new QuizAnswer([
            'answer_value' => ['selected_option_ids' => [$c1->id]], // missing c2
            'question_id'  => $question->id,
        ]);

        [$isCorrect, $points] = $this->callGradeAnswer($question, $answer, 10);

        $this->assertFalse($isCorrect);
        $this->assertEquals(0, $points);
    }

    public function test_fill_in_blank_case_insensitive(): void
    {
        $question = $this->makeQuestion(QuestionType::FillInBlank, 5);
        $this->addOption($question, 'القرآن', true, 0);

        $answer = new QuizAnswer([
            'answer_value' => ['answers' => ['القرآن']],
            'question_id'  => $question->id,
        ]);

        [$isCorrect, $points] = $this->callGradeAnswer($question, $answer, 5);

        $this->assertTrue($isCorrect);
        $this->assertEquals(5, $points);
    }

    public function test_open_ended_returns_null_correct(): void
    {
        $question = $this->makeQuestion(QuestionType::ShortText, 10);

        $answer = new QuizAnswer([
            'answer_value' => ['text' => 'أي نص'],
            'question_id'  => $question->id,
        ]);

        [$isCorrect, $points] = $this->callGradeAnswer($question, $answer, 10);

        $this->assertNull($isCorrect);
        $this->assertEquals(0, $points);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeQuestion(QuestionType $type, int $points): Question
    {
        return Question::factory()->create([
            'type'       => $type->value,
            'points'     => $points,
            'created_by' => 1,
        ]);
    }

    private function addOption(Question $question, string $content, bool $isCorrect, int $order = 0): QuestionOption
    {
        return QuestionOption::factory()->create([
            'question_id' => $question->id,
            'content'     => $content,
            'is_correct'  => $isCorrect,
            'order_number'=> $order,
        ]);
    }

    private function callGradeAnswer(Question $question, QuizAnswer $answer, int $points): array
    {
        $reflection = new \ReflectionMethod($this->service, 'gradeAnswer');
        $reflection->setAccessible(true);
        return $reflection->invoke($this->service, $question, $answer, $points);
    }
}
