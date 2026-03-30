<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Comment;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootstrapsPassport;
use Tests\TestCase;

class ProtectedResourceApiTest extends TestCase
{
    use BootstrapsPassport;
    use RefreshDatabase;

    #[DataProvider('resourceProvider')]
    public function test_read_routes_require_authentication_for_each_resource(string $resource): void
    {
        $graph = $this->seedGraph();
        $recordId = $this->recordIdForResource($resource, $graph);

        $this->getJson("/api/{$resource}?per_page=1")->assertUnauthorized();
        $this->getJson("/api/{$resource}/{$recordId}")->assertUnauthorized();
    }

    #[DataProvider('writeRouteProvider')]
    public function test_write_routes_require_authentication_for_each_resource(string $resource, string $method): void
    {
        $graph = $this->seedGraph();
        [$uri, $payload] = $this->requestFor($resource, $method, $graph);

        $this->json($method, $uri, $payload)->assertUnauthorized();
    }

    #[DataProvider('resourceProvider')]
    public function test_read_scope_can_access_index_and_show_routes_for_each_resource(string $resource): void
    {
        $graph = $this->seedGraph();
        $recordId = $this->recordIdForResource($resource, $graph);

        $this->actingAsPassport(['resources:read']);

        $this->getJson("/api/{$resource}?per_page=1")
            ->assertOk()
            ->assertJsonStructure(['current_page', 'data', 'per_page', 'total'])
            ->assertJsonPath('data.0.id', $recordId);

        $this->getJson("/api/{$resource}/{$recordId}")
            ->assertOk()
            ->assertJsonPath('id', $recordId);
    }

    #[DataProvider('writeRouteProvider')]
    public function test_read_scope_cannot_access_write_routes_for_each_resource(string $resource, string $method): void
    {
        $graph = $this->seedGraph();
        [$uri, $payload] = $this->requestFor($resource, $method, $graph);

        $this->actingAsPassport(['resources:read']);

        $this->json($method, $uri, $payload)->assertForbidden();
    }

    #[DataProvider('resourceProvider')]
    public function test_write_scope_can_perform_full_crud_flow_for_each_resource(string $resource): void
    {
        $graph = $this->seedGraph();
        $actor = $this->actingAsPassport(['resources:read', 'resources:write']);

        $createResponse = $this->postJson("/api/{$resource}", $this->payloadFor($resource, 'create', $graph, $actor));
        $createResponse->assertCreated();

        $createdId = $createResponse->json('id');
        $this->assertResourceState($resource, $createdId, 'create', $createResponse->json());

        $putResponse = $this->putJson("/api/{$resource}/{$createdId}", $this->payloadFor($resource, 'put', $graph, $actor));
        $putResponse->assertOk();
        $this->assertResourceState($resource, $createdId, 'put', $putResponse->json());

        $patchResponse = $this->patchJson("/api/{$resource}/{$createdId}", $this->payloadFor($resource, 'patch', $graph, $actor));
        $patchResponse->assertOk();
        $this->assertResourceState($resource, $createdId, 'patch', $patchResponse->json());

        $this->deleteJson("/api/{$resource}/{$createdId}")->assertNoContent();

        $this->assertDatabaseMissing($resource, [
            'id' => $createdId,
        ]);

        $this->getJson("/api/{$resource}/{$createdId}")->assertNotFound();
    }

    #[DataProvider('requiredFieldProvider')]
    public function test_create_requires_expected_fields_for_each_resource(string $resource, array $expectedErrors): void
    {
        $this->actingAsPassport(['resources:write']);

        $this->postJson("/api/{$resource}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    #[DataProvider('resourceProvider')]
    public function test_create_rejects_prohibited_source_id_for_each_resource(string $resource): void
    {
        $graph = $this->seedGraph();
        $actor = $this->actingAsPassport(['resources:write']);
        $payload = $this->payloadFor($resource, 'create', $graph, $actor);
        $payload['source_id'] = 999;

        $this->postJson("/api/{$resource}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['source_id']);
    }

    #[DataProvider('resourceProvider')]
    public function test_update_rejects_prohibited_source_id_for_each_resource(string $resource): void
    {
        $graph = $this->seedGraph();
        $this->actingAsPassport(['resources:write']);
        $recordId = $this->recordIdForResource($resource, $graph);

        $this->patchJson("/api/{$resource}/{$recordId}", [
            'source_id' => 999,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['source_id']);
    }

    #[DataProvider('foreignKeyProvider')]
    public function test_create_rejects_invalid_foreign_keys_for_dependent_resources(string $resource, string $foreignKey): void
    {
        $graph = $this->seedGraph();
        $actor = $this->actingAsPassport(['resources:write']);
        $payload = $this->payloadFor($resource, 'create', $graph, $actor);
        $payload[$foreignKey] = 999999;

        $this->postJson("/api/{$resource}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$foreignKey]);
    }

    public function test_user_email_must_be_unique_on_create(): void
    {
        $graph = $this->seedGraph();
        $actor = $this->actingAsPassport(['resources:write']);
        $payload = $this->payloadFor('users', 'create', $graph, $actor);
        $payload['email'] = $graph['primaryUser']->email;

        $this->postJson('/api/users', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_email_must_be_unique_on_update(): void
    {
        $graph = $this->seedGraph();
        $this->actingAsPassport(['resources:write']);

        $this->patchJson('/api/users/'.$graph['secondaryUser']->id, [
            'email' => $graph['primaryUser']->email,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    #[DataProvider('invalidUpdateProvider')]
    public function test_update_rejects_invalid_values_for_each_resource(string $resource, array $payload, array $expectedErrors): void
    {
        $graph = $this->seedGraph();
        $this->actingAsPassport(['resources:write']);
        $recordId = $this->recordIdForResource($resource, $graph);

        $this->patchJson("/api/{$resource}/{$recordId}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrors);
    }

    #[DataProvider('resourceProvider')]
    public function test_show_returns_not_found_for_missing_records_for_each_resource(string $resource): void
    {
        $this->actingAsPassport(['resources:read']);

        $this->getJson("/api/{$resource}/999999")->assertNotFound();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function resourceProvider(): array
    {
        return [
            'users' => ['users'],
            'posts' => ['posts'],
            'comments' => ['comments'],
            'albums' => ['albums'],
            'photos' => ['photos'],
            'todos' => ['todos'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function writeRouteProvider(): array
    {
        $scenarios = [];

        foreach (['users', 'posts', 'comments', 'albums', 'photos', 'todos'] as $resource) {
            foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
                $scenarios[strtolower($resource).'-'.strtolower($method)] = [$resource, $method];
            }
        }

        return $scenarios;
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'users' => ['users', ['name', 'email', 'password']],
            'posts' => ['posts', ['user_id', 'title', 'body']],
            'comments' => ['comments', ['post_id', 'name', 'email', 'body']],
            'albums' => ['albums', ['user_id', 'title']],
            'photos' => ['photos', ['album_id', 'title', 'url', 'thumbnail_url']],
            'todos' => ['todos', ['user_id', 'title', 'completed']],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function foreignKeyProvider(): array
    {
        return [
            'posts' => ['posts', 'user_id'],
            'comments' => ['comments', 'post_id'],
            'albums' => ['albums', 'user_id'],
            'photos' => ['photos', 'album_id'],
            'todos' => ['todos', 'user_id'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: array<int, string>}>
     */
    public static function invalidUpdateProvider(): array
    {
        return [
            'users' => ['users', ['email' => 'not-an-email'], ['email']],
            'posts' => ['posts', ['title' => ['not', 'a', 'string']], ['title']],
            'comments' => ['comments', ['email' => 'not-an-email'], ['email']],
            'albums' => ['albums', ['title' => ['not', 'a', 'string']], ['title']],
            'photos' => ['photos', ['url' => 'not-a-url'], ['url']],
            'todos' => ['todos', ['completed' => 'not-a-boolean'], ['completed']],
        ];
    }

    /**
     * @return array{
     *     primaryUser: User,
     *     secondaryUser: User,
     *     post: Post,
     *     secondaryPost: Post,
     *     comment: Comment,
     *     album: Album,
     *     secondaryAlbum: Album,
     *     photo: Photo,
     *     todo: Todo
     * }
     */
    protected function seedGraph(): array
    {
        $primaryUser = User::factory()->create([
            'email' => $this->uniqueEmail('primary-user'),
            'password' => 'secret12345',
        ]);

        $secondaryUser = User::factory()->create([
            'email' => $this->uniqueEmail('secondary-user'),
            'password' => 'secret12345',
        ]);

        $post = Post::query()->create([
            'user_id' => $primaryUser->id,
            'title' => 'Seed post',
            'body' => 'Seed post body',
        ]);

        $secondaryPost = Post::query()->create([
            'user_id' => $secondaryUser->id,
            'title' => 'Secondary seed post',
            'body' => 'Secondary seed post body',
        ]);

        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'name' => 'Seed comment',
            'email' => $this->uniqueEmail('seed-comment'),
            'body' => 'Seed comment body',
        ]);

        $album = Album::query()->create([
            'user_id' => $primaryUser->id,
            'title' => 'Seed album',
        ]);

        $secondaryAlbum = Album::query()->create([
            'user_id' => $secondaryUser->id,
            'title' => 'Secondary seed album',
        ]);

        $photo = Photo::query()->create([
            'album_id' => $album->id,
            'title' => 'Seed photo',
            'url' => 'https://example.com/seed-photo.jpg',
            'thumbnail_url' => 'https://example.com/seed-photo-thumb.jpg',
        ]);

        $todo = Todo::query()->create([
            'user_id' => $secondaryUser->id,
            'title' => 'Seed todo',
            'completed' => false,
        ]);

        return compact(
            'primaryUser',
            'secondaryUser',
            'post',
            'secondaryPost',
            'comment',
            'album',
            'secondaryAlbum',
            'photo',
            'todo',
        );
    }

    /**
     * @param array<string, mixed> $graph
     * @return array{0: string, 1: array<string, mixed>}
     */
    protected function requestFor(string $resource, string $method, array $graph): array
    {
        $uri = "/api/{$resource}";
        $payload = [];

        if ($method === 'POST') {
            $payload = $this->payloadFor($resource, 'create', $graph, $graph['primaryUser']);
        } else {
            $uri .= '/'.$this->recordIdForResource($resource, $graph);

            if ($method === 'PUT') {
                $payload = $this->payloadFor($resource, 'put', $graph, $graph['primaryUser']);
            }

            if ($method === 'PATCH') {
                $payload = $this->payloadFor($resource, 'patch', $graph, $graph['primaryUser']);
            }
        }

        return [$uri, $payload];
    }

    /**
     * @param array<string, mixed> $graph
     * @return array<string, mixed>
     */
    protected function payloadFor(string $resource, string $scenario, array $graph, User $actor): array
    {
        return match ($resource) {
            'users' => $this->userPayloadFor($scenario),
            'posts' => $this->postPayloadFor($scenario, $graph, $actor),
            'comments' => $this->commentPayloadFor($scenario, $graph),
            'albums' => $this->albumPayloadFor($scenario, $graph, $actor),
            'photos' => $this->photoPayloadFor($scenario, $graph),
            'todos' => $this->todoPayloadFor($scenario, $graph, $actor),
            default => throw new \InvalidArgumentException("Unsupported resource [{$resource}]."),
        };
    }

    /**
     * @param array<string, mixed> $graph
     */
    protected function recordIdForResource(string $resource, array $graph): int
    {
        return match ($resource) {
            'users' => $graph['primaryUser']->id,
            'posts' => $graph['post']->id,
            'comments' => $graph['comment']->id,
            'albums' => $graph['album']->id,
            'photos' => $graph['photo']->id,
            'todos' => $graph['todo']->id,
            default => throw new \InvalidArgumentException("Unsupported resource [{$resource}]."),
        };
    }

    /**
     * @param array<string, mixed> $responseJson
     */
    protected function assertResourceState(string $resource, int $id, string $scenario, array $responseJson): void
    {
        $record = $this->resourceModelClass($resource)::query()->findOrFail($id);

        switch ($resource) {
            case 'users':
                $this->assertSame(match ($scenario) {
                    'create' => 'Created User',
                    'put' => 'Put User',
                    'patch' => 'Patched User',
                }, $record->name);
                break;

            case 'posts':
                $this->assertSame(match ($scenario) {
                    'create' => 'Created Post',
                    'put' => 'Put Post',
                    'patch' => 'Patched Post',
                }, $record->title);
                break;

            case 'comments':
                if ($scenario === 'create') {
                    $this->assertSame('Created Comment', $record->name);
                } else {
                    $this->assertSame(match ($scenario) {
                        'put' => 'Put comment body',
                        'patch' => 'Patched comment body',
                    }, $record->body);
                }
                break;

            case 'albums':
                $this->assertSame(match ($scenario) {
                    'create' => 'Created Album',
                    'put' => 'Put Album',
                    'patch' => 'Patched Album',
                }, $record->title);
                break;

            case 'photos':
                $this->assertSame(match ($scenario) {
                    'create' => 'Created Photo',
                    'put' => 'Put Photo',
                    'patch' => 'Patched Photo',
                }, $record->title);
                break;

            case 'todos':
                if ($scenario === 'create') {
                    $this->assertSame('Created Todo', $record->title);
                }

                if ($scenario === 'put') {
                    $this->assertTrue($record->completed);
                }

                if ($scenario === 'patch') {
                    $this->assertFalse($record->completed);
                }
                break;
        }

        $this->assertSame($id, (int) data_get($responseJson, 'id'));
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected function resourceModelClass(string $resource): string
    {
        return match ($resource) {
            'users' => User::class,
            'posts' => Post::class,
            'comments' => Comment::class,
            'albums' => Album::class,
            'photos' => Photo::class,
            'todos' => Todo::class,
            default => throw new \InvalidArgumentException("Unsupported resource [{$resource}]."),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function userPayloadFor(string $scenario): array
    {
        return match ($scenario) {
            'create' => [
                'name' => 'Created User',
                'username' => 'created-user',
                'email' => $this->uniqueEmail('created-user'),
                'phone' => '123456789',
                'website' => 'created.example.com',
                'address' => ['city' => 'Created City'],
                'company' => ['name' => 'Created Company'],
                'password' => 'secret12345',
                'password_confirmation' => 'secret12345',
            ],
            'put' => [
                'name' => 'Put User',
                'username' => 'put-user',
                'email' => $this->uniqueEmail('put-user'),
                'phone' => '987654321',
                'website' => 'put.example.com',
                'address' => ['city' => 'Put City'],
                'company' => ['name' => 'Put Company'],
            ],
            'patch' => [
                'name' => 'Patched User',
            ],
            default => throw new \InvalidArgumentException("Unsupported user payload scenario [{$scenario}]."),
        };
    }

    /**
     * @param array<string, mixed> $graph
     * @return array<string, mixed>
     */
    protected function postPayloadFor(string $scenario, array $graph, User $actor): array
    {
        return match ($scenario) {
            'create' => [
                'user_id' => $actor->id,
                'title' => 'Created Post',
                'body' => 'Created post body',
            ],
            'put' => [
                'user_id' => $graph['secondaryUser']->id,
                'title' => 'Put Post',
                'body' => 'Put post body',
            ],
            'patch' => [
                'title' => 'Patched Post',
            ],
            default => throw new \InvalidArgumentException("Unsupported post payload scenario [{$scenario}]."),
        };
    }

    /**
     * @param array<string, mixed> $graph
     * @return array<string, mixed>
     */
    protected function commentPayloadFor(string $scenario, array $graph): array
    {
        return match ($scenario) {
            'create' => [
                'post_id' => $graph['post']->id,
                'name' => 'Created Comment',
                'email' => $this->uniqueEmail('created-comment'),
                'body' => 'Created comment body',
            ],
            'put' => [
                'post_id' => $graph['secondaryPost']->id,
                'name' => 'Put Comment',
                'email' => $this->uniqueEmail('put-comment'),
                'body' => 'Put comment body',
            ],
            'patch' => [
                'body' => 'Patched comment body',
            ],
            default => throw new \InvalidArgumentException("Unsupported comment payload scenario [{$scenario}]."),
        };
    }

    /**
     * @param array<string, mixed> $graph
     * @return array<string, mixed>
     */
    protected function albumPayloadFor(string $scenario, array $graph, User $actor): array
    {
        return match ($scenario) {
            'create' => [
                'user_id' => $actor->id,
                'title' => 'Created Album',
            ],
            'put' => [
                'user_id' => $graph['secondaryUser']->id,
                'title' => 'Put Album',
            ],
            'patch' => [
                'title' => 'Patched Album',
            ],
            default => throw new \InvalidArgumentException("Unsupported album payload scenario [{$scenario}]."),
        };
    }

    /**
     * @param array<string, mixed> $graph
     * @return array<string, mixed>
     */
    protected function photoPayloadFor(string $scenario, array $graph): array
    {
        return match ($scenario) {
            'create' => [
                'album_id' => $graph['album']->id,
                'title' => 'Created Photo',
                'url' => 'https://example.com/created-photo.jpg',
                'thumbnail_url' => 'https://example.com/created-photo-thumb.jpg',
            ],
            'put' => [
                'album_id' => $graph['secondaryAlbum']->id,
                'title' => 'Put Photo',
                'url' => 'https://example.com/put-photo.jpg',
                'thumbnail_url' => 'https://example.com/put-photo-thumb.jpg',
            ],
            'patch' => [
                'title' => 'Patched Photo',
            ],
            default => throw new \InvalidArgumentException("Unsupported photo payload scenario [{$scenario}]."),
        };
    }

    /**
     * @param array<string, mixed> $graph
     * @return array<string, mixed>
     */
    protected function todoPayloadFor(string $scenario, array $graph, User $actor): array
    {
        return match ($scenario) {
            'create' => [
                'user_id' => $actor->id,
                'title' => 'Created Todo',
                'completed' => false,
            ],
            'put' => [
                'user_id' => $graph['secondaryUser']->id,
                'title' => 'Put Todo',
                'completed' => true,
            ],
            'patch' => [
                'completed' => false,
            ],
            default => throw new \InvalidArgumentException("Unsupported todo payload scenario [{$scenario}]."),
        };
    }

    protected function uniqueEmail(string $prefix): string
    {
        return sprintf('%s-%s@example.com', $prefix, Str::lower((string) Str::ulid()));
    }
}
