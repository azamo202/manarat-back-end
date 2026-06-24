<?php

namespace Tests\Feature\Quiz;

use App\Application\Quiz\Jobs\ProcessQuizSubmission;
use App\Domain\Quiz\Enums\AttemptStatus;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->student = User::factory()->create(['is_admin' => false]);
        $lesson        = Lesson::factory()->create();

        $this->quiz = Quiz::factory()->published()->create([
            'lesson_id'    => $lesson->id,
            'max_attempts' => 2,
            'created_by'   => User::factory()->create(['is_admin' => true])->id,
        ]);

        // Enroll student
        LessonProgress::factory()->create([
            'user_id'   => $this->student->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_enrolled_student_can_start_attempt(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/api/quizzes/{$this->quiz->id}/attempts");

        $response->assertStatus(201)
                 ->assertJsonPath('attempt.status', 'in_progress');

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'status'  => 'in_progress',
        ]);
    }

    public function test_unenrolled_student_cannot_start_attempt(): void
    {
        $otherStudent = User::factory()->create(['is_admin' => false]);

        $this->actingAs($otherStudent)
            ->postJson("/api/quizzes/{$this->quiz->id}/attempts")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('enrollment');
    }

    public function test_student_cannot_start_second_active_attempt(): void
    {
        // Start first attempt
        $this->actingAs($this->student)
            ->postJson("/api/quizzes/{$this->quiz->id}/attempts")
            ->assertStatus(201);

        // Try starting another
        $this->actingAs($this->student)
            ->postJson("/api/quizzes/{$this->quiz->id}/attempts")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('attempt');
    }

    public function test_student_cannot_exceed_max_attempts(): void
    {
        // Mark two completed attempts
        QuizAttempt::factory()->count(2)->create([
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'status'  => AttemptStatus::Submitted,
        ]);

        $this->actingAs($this->student)
            ->postJson("/api/quizzes/{$this->quiz->id}/attempts")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('attempts');
    }

    public function test_student_can_auto_save_answers(): void
    {
        $question = Question::factory()->multipleChoice()->create(['created_by' => 1]);
        $this->quiz->questions()->attach($question->id, ['order_number' => 1]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'status'  => AttemptStatus::InProgress,
        ]);

        $this->actingAs($this->student)
            ->putJson("/api/quizzes/{$this->quiz->id}/attempts/{$attempt->id}/answers", [
                'answers' => [
                    ['question_id' => $question->id, 'answer_value' => ['selected_option_id' => 1]],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('quiz_answers', [
            'attempt_id'  => $attempt->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_submit_dispatches_grading_job(): void
    {
        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'status'  => AttemptStatus::InProgress,
        ]);

        $this->actingAs($this->student)
            ->postJson("/api/quizzes/{$this->quiz->id}/attempts/{$attempt->id}/submit", [
                'answers' => [],
            ])
            ->assertOk();

        Queue::assertPushed(ProcessQuizSubmission::class, fn($job) =>
            $job->attemptId === $attempt->id
        );

        $this->assertDatabaseHas('quiz_attempts', [
            'id'     => $attempt->id,
            'status' => 'submitted',
        ]);
    }

    public function test_cannot_save_answers_after_submission(): void
    {
        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'status'  => AttemptStatus::Submitted,
        ]);

        $this->actingAs($this->student)
            ->putJson("/api/quizzes/{$this->quiz->id}/attempts/{$attempt->id}/answers", [
                'answers' => [['question_id' => 1, 'answer_value' => []]],
            ])
            ->assertStatus(422);
    }

    public function test_cannot_access_another_users_attempt(): void
    {
        $otherStudent = User::factory()->create(['is_admin' => false]);
        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'status'  => AttemptStatus::InProgress,
        ]);

        $this->actingAs($otherStudent)
            ->getJson("/api/quizzes/{$this->quiz->id}/attempts/{$attempt->id}")
            ->assertStatus(403);
    }
}
