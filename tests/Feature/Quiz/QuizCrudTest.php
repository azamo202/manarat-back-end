<?php

namespace Tests\Feature\Quiz;

use App\Domain\Quiz\Enums\QuizStatus;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['is_admin' => true]);
        $this->student = User::factory()->create(['is_admin' => false]);
    }

    public function test_admin_can_create_quiz(): void
    {
        $course = Course::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/quizzes', [
                'title'        => 'اختبار التجويد',
                'course_id'    => $course->id,
                'passing_score'=> 70,
                'max_attempts' => 3,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('quiz.title', 'اختبار التجويد')
                 ->assertJsonPath('quiz.status', QuizStatus::Draft->value);

        $this->assertDatabaseHas('quizzes', ['title' => 'اختبار التجويد']);
    }

    public function test_student_cannot_create_quiz(): void
    {
        $this->actingAs($this->student)
            ->postJson('/api/admin/quizzes', ['title' => 'test', 'passing_score' => 50, 'max_attempts' => 1])
            ->assertStatus(403);
    }

    public function test_admin_can_publish_quiz_with_questions(): void
    {
        $quiz     = Quiz::factory()->draft()->create(['created_by' => $this->admin->id]);
        $question = \App\Models\Question::factory()->multipleChoice()->create(['created_by' => $this->admin->id]);
        $quiz->questions()->attach($question->id, ['order_number' => 1]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/quizzes/{$quiz->id}/publish");

        $response->assertOk();
        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'status' => 'published']);
    }

    public function test_cannot_publish_quiz_without_questions(): void
    {
        $quiz = Quiz::factory()->draft()->create(['created_by' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/quizzes/{$quiz->id}/publish")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('questions');
    }

    public function test_admin_can_duplicate_quiz(): void
    {
        $quiz = Quiz::factory()->published()->create(['created_by' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/quizzes/{$quiz->id}/duplicate")
            ->assertStatus(201)
            ->assertJsonPath('quiz.status', 'draft');

        $this->assertEquals(2, Quiz::count());
    }

    public function test_admin_can_delete_quiz(): void
    {
        $quiz = Quiz::factory()->create(['created_by' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/quizzes/{$quiz->id}")
            ->assertOk();

        $this->assertSoftDeleted('quizzes', ['id' => $quiz->id]);
    }
}
