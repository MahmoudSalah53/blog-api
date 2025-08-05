<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    #[Test]
    public function can_create_tag_successfully()
    {
        $this->authenticate();

        $response = $this->postJson('/api/tags/add', ['name' => 'Tech']);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Tag created successfully.'])
            ->assertJsonStructure([
                'message',
                'tag' => ['id','name']
            ]);

        $this->assertDatabaseHas('tags', ['name' => 'Tech']);
    }

    #[Test]
    public function cannot_create_tag_with_invalid_name()
    {
        $this->authenticate();

        $this->postJson('/api/tags/add', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);

        $this->postJson('/api/tags/add', ['name' => 'A'])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);

        $this->postJson('/api/tags/add', ['name' => str_repeat('a', 21)])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    #[Test]
    public function cannot_create_tag_with_duplicate_name()
    {
        $this->authenticate();

        Tag::factory()->create(['name' => 'News']);

        $this->postJson('/api/tags/add', ['name' => 'News'])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    #[Test]
    public function can_delete_existing_tag()
    {
        $this->authenticate();

        $tag = Tag::factory()->create(['name' => 'Sports']);

        $response = $this->deleteJson('/api/tags/delete/'.$tag->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Tag Deleted successfully.']);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    #[Test]
    public function delete_non_existing_tag_returns_404()
    {
        $this->authenticate();

        $response = $this->deleteJson('/api/tags/delete/999999');

        $response->assertStatus(404)
            ->assertJsonFragment(['error' => 'Tag not found.']);
    }
}