<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/comments/add/{$post->id}", [
                'comment' => 'This is a test comment',
            ]);

        $response->assertJsonFragment(['message' => 'Comment Created successfully.']);

        $this->assertDatabaseHas('comments', [
            'comment' => 'This is a test comment',
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_delete_own_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/comments/delete/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['message' => 'Comment Deleted successfully.']]);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_comment()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $post = Post::factory()->create();

        $comment = Comment::factory()->create([
            'user_id' => $anotherUser->id,
            'post_id' => $post->id,
        ]);

        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/comments/delete/{$comment->id}");

        $response->assertStatus(403)
            ->assertJson(['error' => ['message' => 'Unauthorized.']]);
    }
}