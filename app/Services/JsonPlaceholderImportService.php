<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Comment;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class JsonPlaceholderImportService
{
    /**
     * @return array<string, array{inserted:int, updated:int}>
     */
    public function import(): array
    {
        $payload = [
            'users' => $this->fetchResource('users'),
            'posts' => $this->fetchResource('posts'),
            'comments' => $this->fetchResource('comments'),
            'albums' => $this->fetchResource('albums'),
            'photos' => $this->fetchResource('photos'),
            'todos' => $this->fetchResource('todos'),
        ];

        return DB::transaction(function () use ($payload) {
            $summary = [];

            $summary['users'] = $this->importUsers($payload['users']);
            $userMap = User::query()->whereNotNull('source_id')->pluck('id', 'source_id')->all();

            $summary['posts'] = $this->syncRelatedRecords(
                Post::class,
                $payload['posts'],
                fn (array $item): array => [
                    'user_id' => $this->resolveRelationId($userMap, (int) $item['userId'], 'users'),
                    'title' => $item['title'],
                    'body' => $item['body'],
                ],
            );
            $postMap = Post::query()->whereNotNull('source_id')->pluck('id', 'source_id')->all();

            $summary['comments'] = $this->syncRelatedRecords(
                Comment::class,
                $payload['comments'],
                fn (array $item): array => [
                    'post_id' => $this->resolveRelationId($postMap, (int) $item['postId'], 'posts'),
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'body' => $item['body'],
                ],
            );

            $summary['albums'] = $this->syncRelatedRecords(
                Album::class,
                $payload['albums'],
                fn (array $item): array => [
                    'user_id' => $this->resolveRelationId($userMap, (int) $item['userId'], 'users'),
                    'title' => $item['title'],
                ],
            );
            $albumMap = Album::query()->whereNotNull('source_id')->pluck('id', 'source_id')->all();

            $summary['photos'] = $this->syncRelatedRecords(
                Photo::class,
                $payload['photos'],
                fn (array $item): array => [
                    'album_id' => $this->resolveRelationId($albumMap, (int) $item['albumId'], 'albums'),
                    'title' => $item['title'],
                    'url' => $item['url'],
                    'thumbnail_url' => $item['thumbnailUrl'],
                ],
            );

            $summary['todos'] = $this->syncRelatedRecords(
                Todo::class,
                $payload['todos'],
                fn (array $item): array => [
                    'user_id' => $this->resolveRelationId($userMap, (int) $item['userId'], 'users'),
                    'title' => $item['title'],
                    'completed' => (bool) $item['completed'],
                ],
            );

            return $summary;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchResource(string $resource): array
    {
        $response = Http::acceptJson()
            ->baseUrl(config('slmp.jsonplaceholder.base_url'))
            ->retry(
                config('slmp.jsonplaceholder.retry_times'),
                config('slmp.jsonplaceholder.retry_sleep_ms'),
            )
            ->timeout(config('slmp.jsonplaceholder.timeout'))
            ->get($resource)
            ->throw();

        return $response->json();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{inserted:int, updated:int}
     */
    protected function importUsers(array $items): array
    {
        $existingUsers = User::query()
            ->whereNotNull('source_id')
            ->whereIn('source_id', array_map(fn (array $item) => (int) $item['id'], $items))
            ->get()
            ->keyBy('source_id');

        $inserted = 0;
        $updated = 0;

        foreach ($items as $item) {
            $sourceId = (int) $item['id'];
            $attributes = [
                'name' => $item['name'],
                'username' => $item['username'],
                'email' => $item['email'],
                'phone' => $item['phone'],
                'website' => $item['website'],
                'address' => $item['address'],
                'company' => $item['company'],
                'email_verified_at' => now(),
            ];

            $record = $existingUsers->get($sourceId);

            if ($record instanceof User) {
                $record->fill($attributes);

                if ($record->isDirty()) {
                    $record->save();
                    $updated++;
                }

                continue;
            }

            User::create($attributes + [
                'source_id' => $sourceId,
                'password' => Str::password(32),
            ]);

            $inserted++;
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
        ];
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @param array<int, array<string, mixed>> $items
     * @param callable(array<string, mixed>): array<string, mixed> $attributeResolver
     * @return array{inserted:int, updated:int}
     */
    protected function syncRelatedRecords(string $modelClass, array $items, callable $attributeResolver): array
    {
        $existingRecords = $modelClass::query()
            ->whereNotNull('source_id')
            ->whereIn('source_id', array_map(fn (array $item) => (int) $item['id'], $items))
            ->get()
            ->keyBy('source_id');

        $inserted = 0;
        $updated = 0;

        foreach ($items as $item) {
            $sourceId = (int) $item['id'];
            $attributes = $attributeResolver($item);
            $record = $existingRecords->get($sourceId);

            if ($record !== null) {
                $record->fill($attributes);

                if ($record->isDirty()) {
                    $record->save();
                    $updated++;
                }

                continue;
            }

            $modelClass::create($attributes + ['source_id' => $sourceId]);
            $inserted++;
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
        ];
    }

    /**
     * @param array<int, int> $map
     */
    protected function resolveRelationId(array $map, int $sourceId, string $resource): int
    {
        if (! array_key_exists($sourceId, $map)) {
            throw new RuntimeException("Unable to resolve related {$resource} record for source ID {$sourceId}.");
        }

        return (int) $map[$sourceId];
    }
}
