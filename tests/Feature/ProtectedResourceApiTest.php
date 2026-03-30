<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProtectedResourceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/posts')->assertUnauthorized();
    }

    public function test_read_scope_can_access_get_routes(): void
    {
        $user = User::factory()->create();
        Post::query()->create([
            'user_id' => $user->id,
            'title' => 'Visible post',
            'body' => 'Visible body',
        ]);

        Passport::actingAs($user, ['resources:read']);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Visible post');
    }

    public function test_read_scope_cannot_write_resources(): void
    {
        $user = User::factory()->create();

        Passport::actingAs($user, ['resources:read']);

        $this->postJson('/api/posts', [
            'user_id' => $user->id,
            'title' => 'Blocked post',
            'body' => 'Blocked body',
        ])->assertForbidden();
    }

    public function test_write_scope_can_create_resources(): void
    {
        $user = User::factory()->create();

        Passport::actingAs($user, ['resources:write']);

        $this->postJson('/api/posts', [
            'user_id' => $user->id,
            'title' => 'Created post',
            'body' => 'Created body',
        ])->assertCreated();

        $this->assertDatabaseHas('posts', [
            'title' => 'Created post',
        ]);
    }
}
