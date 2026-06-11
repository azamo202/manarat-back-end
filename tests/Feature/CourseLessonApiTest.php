<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseLessonApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test public GET /api/courses returns active courses only.
     */
    public function test_public_can_get_only_active_courses(): void
    {
        // Active Course
        Course::create([
            'title' => 'Active Course 1',
            'is_active' => true,
        ]);

        // Inactive Course
        Course::create([
            'title' => 'Inactive Course 2',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/courses');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'title' => 'Active Course 1',
            ])
            ->assertJsonMissing([
                'title' => 'Inactive Course 2',
            ]);
    }

    /**
     * Test Course detail requires authentication.
     */
    public function test_course_detail_requires_authentication(): void
    {
        $course = Course::create([
            'title' => 'Some Course',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/courses/{$course->id}");
        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can view course detail with ordered lessons.
     */
    public function test_authenticated_user_can_view_course_detail_with_ordered_lessons(): void
    {
        $user = User::create([
            'full_name' => 'John Doe',
            'phone_number' => '0501112223',
            'city' => 'Riyadh',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $course = Course::create([
            'title' => 'Full Course',
            'is_active' => true,
        ]);

        $lessonSecond = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Second Lesson',
            'youtube_video_id' => 'video2',
            'duration_in_seconds' => 120,
            'order_number' => 2,
        ]);

        $lessonFirst = Lesson::create([
            'course_id' => $course->id,
            'title' => 'First Lesson',
            'youtube_video_id' => 'video1',
            'duration_in_seconds' => 100,
            'order_number' => 1,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJsonPath('title', 'Full Course')
            ->assertJsonStructure([
                'id',
                'title',
                'lessons' => [
                    '*' => [
                        'id',
                        'title',
                        'order_number',
                    ]
                ]
            ]);

        // Verify lessons are ordered by order_number ascending
        $lessonsData = $response->json('lessons');
        $this->assertCount(2, $lessonsData);
        $this->assertEquals($lessonFirst->id, $lessonsData[0]['id']);
        $this->assertEquals($lessonSecond->id, $lessonsData[1]['id']);
    }

    /**
     * Test Lesson detail requires authentication.
     */
    public function test_lesson_detail_requires_authentication(): void
    {
        $course = Course::create([
            'title' => 'Some Course',
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Some Lesson',
            'youtube_video_id' => 'video',
            'duration_in_seconds' => 100,
        ]);

        $response = $this->getJson("/api/lessons/{$lesson->id}");
        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can view lesson details with course eager loaded.
     */
    public function test_authenticated_user_can_view_lesson_details_with_course_eager_loaded(): void
    {
        $user = User::create([
            'full_name' => 'John Doe',
            'phone_number' => '0501112223',
            'city' => 'Riyadh',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $course = Course::create([
            'title' => 'Main Course',
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Specific Lesson',
            'youtube_video_id' => 'yt123',
            'duration_in_seconds' => 300,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/lessons/{$lesson->id}");

        $response->assertStatus(200)
            ->assertJsonPath('title', 'Specific Lesson')
            ->assertJsonPath('youtube_video_id', 'yt123')
            ->assertJsonStructure([
                'id',
                'title',
                'youtube_video_id',
                'course' => [
                    'id',
                    'title',
                ]
            ]);

        $this->assertEquals($course->id, $response->json('course.id'));
    }

    /**
     * Test non-existent course detail returns 404.
     */
    public function test_non_existent_course_returns_404(): void
    {
        $user = User::create([
            'full_name' => 'John Doe',
            'phone_number' => '0501112223',
            'city' => 'Riyadh',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/courses/999');

        $response->assertStatus(404);
    }

    /**
     * Test non-existent lesson detail returns 404.
     */
    public function test_non_existent_lesson_returns_404(): void
    {
        $user = User::create([
            'full_name' => 'John Doe',
            'phone_number' => '0501112223',
            'city' => 'Riyadh',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/lessons/999');

        $response->assertStatus(404);
    }
}
