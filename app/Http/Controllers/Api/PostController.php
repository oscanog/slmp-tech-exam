<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;

class PostController extends ResourceController
{
    protected string $modelClass = Post::class;

    protected function rules(?Model $record = null): array
    {
        return [
            'source_id' => ['prohibited'],
            'user_id' => [$record ? 'sometimes' : 'required', 'integer', 'exists:users,id'],
            'title' => [$record ? 'sometimes' : 'required', 'string', 'max:255'],
            'body' => [$record ? 'sometimes' : 'required', 'string'],
        ];
    }
}
