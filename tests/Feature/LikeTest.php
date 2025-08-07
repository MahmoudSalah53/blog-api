<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/like/{$post->id}");

        $response->assertStatus(201);
        $this->assertEquals('"Post successfully liked"', $response->getContent());

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_unlike_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $like = Like::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/like/{$post->id}");

        $response->assertStatus(201);
        $this->assertEquals('"Post has been successfully unliked."', $response->getContent());

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_like_toggle_works_correctly()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        // First like
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/like/{$post->id}");
            
        $response1->assertStatus(201);
        $this->assertEquals('"Post successfully liked"', $response1->getContent());

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        // Then unlike
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/posts/like/{$post->id}");
            
        $response2->assertStatus(201);
        $this->assertEquals('"Post has been successfully unliked."', $response2->getContent());

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }
}