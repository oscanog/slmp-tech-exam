<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Comment;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Todo;
use App\Models\User;
use App\Services\RuntimeCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class RuntimeCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_runtime_check_command_verifies_imported_counts_and_live_api_steps(): void
    {
        config()->set('services.runtime_check.base_url', 'http://runtime-check.test');
        config()->set('services.runtime_check.expected_counts', [
            'users' => 1,
            'posts' => 1,
            'comments' => 1,
            'albums' => 1,
            'photos' => 1,
            'todos' => 1,
        ]);

        $this->seedImportedResources();

        $registeredEmail = null;
        $meCalls = 0;

        Http::fake(function (Request $request) use (&$registeredEmail, &$meCalls) {
            $url = $request->url();

            if ($url === 'http://runtime-check.test/api/health') {
                return Http::response([
                    'status' => 'ok',
                    'app' => 'slmp_01',
                ]);
            }

            if ($url === 'http://runtime-check.test/api/auth/register') {
                $registeredEmail = (string) $request['email'];

                return Http::response([
                    'message' => 'User registered successfully.',
                    'user' => [
                        'email' => $registeredEmail,
                    ],
                ], 201);
            }

            if ($url === 'http://runtime-check.test/api/auth/login') {
                return Http::response([
                    'access_token' => 'runtime-token',
                    'token_type' => 'Bearer',
                    'user' => [
                        'email' => $registeredEmail,
                    ],
                ]);
            }

            if ($url === 'http://runtime-check.test/api/auth/me') {
                $meCalls++;

                if ($meCalls === 1) {
                    return Http::response([
                        'email' => $registeredEmail,
                    ]);
                }

                return Http::response([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            if ($url === 'http://runtime-check.test/api/posts?per_page=1') {
                return Http::response([
                    'data' => [
                        [
                            'id' => 1,
                            'title' => 'Imported post',
                        ],
                    ],
                ]);
            }

            if ($url === 'http://runtime-check.test/api/auth/logout') {
                return Http::response([
                    'message' => 'Logged out successfully.',
                ]);
            }

            return Http::response([], 500);
        });

        $this->artisan('slmp:runtime-check')
            ->expectsOutputToContain('Imported resource counts verified.')
            ->expectsOutputToContain('API runtime checks passed against http://runtime-check.test.')
            ->expectsOutputToContain('Runtime check completed successfully.')
            ->assertSuccessful();
    }

    public function test_runtime_check_service_fails_when_imported_counts_are_incomplete(): void
    {
        config()->set('services.runtime_check.expected_counts', [
            'users' => 2,
            'posts' => 1,
            'comments' => 1,
            'albums' => 1,
            'photos' => 1,
            'todos' => 1,
        ]);

        $this->seedImportedResources();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Imported users rows are incomplete.');

        app(RuntimeCheckService::class)->run();
    }

    protected function seedImportedResources(): void
    {
        $user = User::query()->create([
            'source_id' => 1,
            'name' => 'Imported User',
            'username' => 'imported-user',
            'email' => 'imported-user@example.com',
            'phone' => '123456789',
            'website' => 'imported.example.com',
            'address' => ['city' => 'Imported City'],
            'company' => ['name' => 'Imported Company'],
            'password' => 'secret12345',
            'email_verified_at' => now(),
        ]);

        $post = Post::query()->create([
            'source_id' => 10,
            'user_id' => $user->id,
            'title' => 'Imported post',
            'body' => 'Imported body',
        ]);

        Comment::query()->create([
            'source_id' => 100,
            'post_id' => $post->id,
            'name' => 'Imported comment',
            'email' => 'imported-comment@example.com',
            'body' => 'Imported comment body',
        ]);

        $album = Album::query()->create([
            'source_id' => 200,
            'user_id' => $user->id,
            'title' => 'Imported album',
        ]);

        Photo::query()->create([
            'source_id' => 300,
            'album_id' => $album->id,
            'title' => 'Imported photo',
            'url' => 'https://example.com/imported-photo.jpg',
            'thumbnail_url' => 'https://example.com/imported-photo-thumb.jpg',
        ]);

        Todo::query()->create([
            'source_id' => 400,
            'user_id' => $user->id,
            'title' => 'Imported todo',
            'completed' => true,
        ]);
    }
}
