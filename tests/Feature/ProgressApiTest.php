<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\LessonProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Course $course;
    private Lesson $lesson;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'full_name' => 'John Doe',
            'phone_number' => '0501112223',
            'city' => 'Riyadh',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->course = Course::create([
            'title' => 'Main Course',
            'is_active' => true,
        ]);

        $this->lesson = Lesson::create([
            'course_id' => $this->course->id,
            'title' => 'Specific Lesson',
            'youtube_video_id' => 'yt123',
            'duration_in_seconds' => 100, // 100 seconds makes percentage calculations simple!
        ]);

        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    /**
     * Test endpoints require authentication.
     */
    public function test_progress_endpoints_require_authentication(): void
    {
        $responseShow = $this->getJson("/api/progress/{$this->lesson->id}");
        $responseShow->assertStatus(401);

        $responsePing = $this->postJson('/api/progress/ping', [
            'lesson_id' => $this->lesson->id,
            'current_second' => 50,
        ]);
        $responsePing->assertStatus(401);
    }

    /**
     * Test initial progress defaults to 0 and false.
     */
    public function test_initial_progress_defaults_to_zero_and_false(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/progress/{$this->lesson->id}");

        $response->assertStatus(200)
            ->assertJson([
                'current_second' => 0,
                'is_completed' => false,
            ]);
    }

    /**
     * Test pinging progress updates the record.
     */
    public function test_ping_updates_progress_successfully(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/progress/ping', [
                'lesson_id' => $this->lesson->id,
                'current_second' => 45,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_completed' => false,
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->user->id,
            'lesson_id' => $this->lesson->id,
            'current_second' => 45,
            'is_completed' => false,
        ]);

        // Verify show endpoint matches
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/progress/{$this->lesson->id}");

        $showResponse->assertJson([
            'current_second' => 45,
            'is_completed' => false,
        ]);
    }

    /**
     * Test completing a lesson when pinging >= 95% of duration.
     */
    public function test_ping_completes_lesson_at_ninety_five_percent(): void
    {
        // 94 seconds out of 100 duration (94%)
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/progress/ping', [
                'lesson_id' => $this->lesson->id,
                'current_second' => 94,
            ]);

        $response1->assertJsonPath('is_completed', false);

        // 95 seconds out of 100 duration (95%)
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/progress/ping', [
                'lesson_id' => $this->lesson->id,
                'current_second' => 95,
            ]);

        $response2->assertJsonPath('is_completed', true);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->user->id,
            'lesson_id' => $this->lesson->id,
            'current_second' => 95,
            'is_completed' => true,
        ]);
    }

    /**
     * Test that completed status is preserved if the user seeks backwards.
     */
    public function test_completed_status_preserved_on_rewind(): void
    {
        // Complete the lesson first
        LessonProgress::create([
            'user_id' => $this->user->id,
            'lesson_id' => $this->lesson->id,
            'current_second' => 96,
            'is_completed' => true,
        ]);

        // Ping a backward progress (e.g. 10 seconds)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/progress/ping', [
                'lesson_id' => $this->lesson->id,
                'current_second' => 10,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_completed', true);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->user->id,
            'lesson_id' => $this->lesson->id,
            'current_second' => 10,
            'is_completed' => true,
        ]);
    }
}
