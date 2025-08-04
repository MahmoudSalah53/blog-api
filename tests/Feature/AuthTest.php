<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register_successfully()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'access_token',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ahmed@example.com',
        ]);
    }

    #[Test]
    public function user_cannot_register_with_invalid_data()
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function user_can_login_successfully()
    {
        $user = User::factory()->create([
            'email' => 'ahmed@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ahmed@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);
    }

    #[Test]
    public function user_can_logout_successfully()
    {
        $user = User::factory()->create();

        $token = $user->createToken('TestToken')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);
    }

}
