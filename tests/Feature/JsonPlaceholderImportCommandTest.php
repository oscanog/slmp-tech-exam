<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Comment;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JsonPlaceholderImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_all_jsonplaceholder_resources(): void
    {
        Http::fake($this->fakeJsonPlaceholderResponses());

        Artisan::call('slmp:import-jsonplaceholder');

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseCount('comments', 2);
        $this->assertDatabaseCount('albums', 1);
        $this->assertDatabaseCount('photos', 1);
        $this->assertDatabaseCount('todos', 1);

        $post = Post::query()->where('source_id', 10)->firstOrFail();
        $comment = Comment::query()->where('source_id', 100)->firstOrFail();
        $photo = Photo::query()->where('source_id', 300)->firstOrFail();
        $todo = Todo::query()->where('source_id', 400)->firstOrFail();

        $this->assertSame(User::query()->where('source_id', 1)->value('id'), $post->user_id);
        $this->assertSame($post->id, $comment->post_id);
        $this->assertSame(Album::query()->where('source_id', 200)->value('id'), $photo->album_id);
        $this->assertSame(User::query()->where('source_id', 2)->value('id'), $todo->user_id);
    }

    public function test_it_upserts_existing_imported_rows_and_only_adds_new_upstream_rows(): void
    {
        $initialPayload = $this->fakeJsonPlaceholderPayload();
        $updatedPayload = $this->fakeJsonPlaceholderPayload([
            'posts' => [
                [
                    'id' => 10,
                    'userId' => 1,
                    'title' => 'Updated title',
                    'body' => 'Updated body',
                ],
                [
                    'id' => 12,
                    'userId' => 2,
                    'title' => 'Brand new post',
                    'body' => 'New body',
                ],
            ],
        ]);

        Http::fake([
            'https://jsonplaceholder.typicode.com/users' => Http::sequence()
                ->push($initialPayload['users'])
                ->push($updatedPayload['users']),
            'https://jsonplaceholder.typicode.com/posts' => Http::sequence()
                ->push($initialPayload['posts'])
                ->push($updatedPayload['posts']),
            'https://jsonplaceholder.typicode.com/comments' => Http::sequence()
                ->push($initialPayload['comments'])
                ->push($updatedPayload['comments']),
            'https://jsonplaceholder.typicode.com/albums' => Http::sequence()
                ->push($initialPayload['albums'])
                ->push($updatedPayload['albums']),
            'https://jsonplaceholder.typicode.com/photos' => Http::sequence()
                ->push($initialPayload['photos'])
                ->push($updatedPayload['photos']),
            'https://jsonplaceholder.typicode.com/todos' => Http::sequence()
                ->push($initialPayload['todos'])
                ->push($updatedPayload['todos']),
        ]);

        Artisan::call('slmp:import-jsonplaceholder');
        Artisan::call('slmp:import-jsonplaceholder');

        $this->assertDatabaseCount('posts', 3);
        $this->assertDatabaseHas('posts', [
            'source_id' => 10,
            'title' => 'Updated title',
        ]);
        $this->assertDatabaseHas('posts', [
            'source_id' => 11,
            'title' => 'Second post',
        ]);
        $this->assertDatabaseHas('posts', [
            'source_id' => 12,
            'title' => 'Brand new post',
        ]);
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $overrides
     * @return array<string, mixed>
     */
    protected function fakeJsonPlaceholderResponses(array $overrides = []): array
    {
        $payload = $this->fakeJsonPlaceholderPayload($overrides);

        return [
            'https://jsonplaceholder.typicode.com/users' => Http::response($payload['users']),
            'https://jsonplaceholder.typicode.com/posts' => Http::response($payload['posts']),
            'https://jsonplaceholder.typicode.com/comments' => Http::response($payload['comments']),
            'https://jsonplaceholder.typicode.com/albums' => Http::response($payload['albums']),
            'https://jsonplaceholder.typicode.com/photos' => Http::response($payload['photos']),
            'https://jsonplaceholder.typicode.com/todos' => Http::response($payload['todos']),
        ];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $overrides
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function fakeJsonPlaceholderPayload(array $overrides = []): array
    {
        return array_replace([
            'users' => [
                [
                    'id' => 1,
                    'name' => 'Leanne Graham',
                    'username' => 'Bret',
                    'email' => 'leanne@example.com',
                    'address' => ['city' => 'Gwenborough'],
                    'phone' => '1-770-736-8031',
                    'website' => 'hildegard.org',
                    'company' => ['name' => 'Romaguera-Crona'],
                ],
                [
                    'id' => 2,
                    'name' => 'Ervin Howell',
                    'username' => 'Antonette',
                    'email' => 'ervin@example.com',
                    'address' => ['city' => 'Wisokyburgh'],
                    'phone' => '010-692-6593',
                    'website' => 'anastasia.net',
                    'company' => ['name' => 'Deckow-Crist'],
                ],
            ],
            'posts' => [
                [
                    'id' => 10,
                    'userId' => 1,
                    'title' => 'First post',
                    'body' => 'Body one',
                ],
                [
                    'id' => 11,
                    'userId' => 2,
                    'title' => 'Second post',
                    'body' => 'Body two',
                ],
            ],
            'comments' => [
                [
                    'id' => 100,
                    'postId' => 10,
                    'name' => 'Commenter One',
                    'email' => 'commenter1@example.com',
                    'body' => 'Comment one',
                ],
                [
                    'id' => 101,
                    'postId' => 11,
                    'name' => 'Commenter Two',
                    'email' => 'commenter2@example.com',
                    'body' => 'Comment two',
                ],
            ],
            'albums' => [
                [
                    'id' => 200,
                    'userId' => 1,
                    'title' => 'Album title',
                ],
            ],
            'photos' => [
                [
                    'id' => 300,
                    'albumId' => 200,
                    'title' => 'Photo title',
                    'url' => 'https://example.com/photo.jpg',
                    'thumbnailUrl' => 'https://example.com/thumb.jpg',
                ],
            ],
            'todos' => [
                [
                    'id' => 400,
                    'userId' => 2,
                    'title' => 'Todo item',
                    'completed' => true,
                ],
            ],
        ], $overrides);
    }
}
