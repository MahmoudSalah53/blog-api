<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_new_post()
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/posts/add', [
            'title' => 'Test Post',
            'content' => 'This is a test post.'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'post' => [
                    'id',
                    'title',
                    'content',
                    'author'
                ]
            ]);


        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post'
        ]);
    }

    public function test_user_can_edit_their_post()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson("/api/posts/edit/{$post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content.'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Post updated successfully.']);

        $this->assertDatabaseHas('posts', ['title' => 'Updated Title']);
    }

    public function test_user_cannot_edit_others_post()
    {
        Sanctum::actingAs(User::factory()->create());
        $otherPost = Post::factory()->create();

        $response = $this->postJson("/api/posts/edit/{$otherPost->id}", [
            'title' => 'Hack',
            'content' => 'Attempt to hack.'
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_get_all_posts()
    {
        Post::factory()->count(5)->create();

        $response = $this->getJson('/api/posts/all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts'
            ]);
    }

    public function test_user_can_get_single_post()
    {
        $post = Post::factory()->create();

        $response = $this->getJson("/api/posts/show/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'post' => [
                    'id',
                    'title',
                    'content'
                ]
            ]);
    }

    public function test_user_can_delete_own_post()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/posts/delete/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Post Deleted successfully.']);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_user_cannot_delete_others_post()
    {
        Sanctum::actingAs(User::factory()->create());
        $otherPost = Post::factory()->create();

        $response = $this->deleteJson("/api/posts/delete/{$otherPost->id}");

        $response->assertStatus(403);
    }
}
