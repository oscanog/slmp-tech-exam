<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;

class CommentController extends ResourceController
{
    protected string $modelClass = Comment::class;

    protected function rules(?Model $record = null): array
    {
        return [
            'source_id' => ['prohibited'],
            'post_id' => [$record ? 'sometimes' : 'required', 'integer', 'exists:posts,id'],
            'name' => [$record ? 'sometimes' : 'required', 'string', 'max:255'],
            'email' => [$record ? 'sometimes' : 'required', 'email', 'max:255'],
            'body' => [$record ? 'sometimes' : 'required', 'string'],
        ];
    }
}
