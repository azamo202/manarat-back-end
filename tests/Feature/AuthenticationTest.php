<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration.
     */
    public function test_user_can_register(): void
    {
        $userData = [
            'full_name' => 'Test User Registration',
            'phone_number' => '0591112233',
            'city' => 'Riyadh',
            'email' => 'register@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'full_name',
                    'phone_number',
                    'city',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'register@example.com',
            'full_name' => 'Test User Registration',
        ]);
    }

    /**
     * Test successful login.
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::create([
            'full_name' => 'Existing User',
            'phone_number' => '0592223344',
            'city' => 'Jeddah',
            'email' => 'login@example.com',
            'password' => Hash::make('secretpassword'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'secretpassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'full_name',
                    'email',
                ],
                'token',
            ]);
    }

    /**
     * Test login failure with incorrect credentials.
     */
    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        User::create([
            'full_name' => 'Existing User',
            'phone_number' => '0592223344',
            'city' => 'Jeddah',
            'email' => 'login@example.com',
            'password' => Hash::make('secretpassword'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test authenticated user retrieval.
     */
    public function test_authenticated_user_can_be_retrieved(): void
    {
        $user = User::create([
            'full_name' => 'Auth User',
            'phone_number' => '0593334455',
            'city' => 'Dammam',
            'email' => 'auth@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => 'auth@example.com',
            ]);
    }

    /**
     * Test successful logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'full_name' => 'Auth User',
            'phone_number' => '0593334455',
            'city' => 'Dammam',
            'email' => 'auth@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        // Verify that the token is deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        // Forget the authenticated guard to force a re-evaluation of the authorization header
        \Illuminate\Support\Facades\Auth::forgetGuards();

        // Accessing /api/user should now be unauthorized
        $retryResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');

        $retryResponse->assertStatus(401);
    }
}
