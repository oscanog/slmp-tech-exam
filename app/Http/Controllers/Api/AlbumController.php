<?php

namespace App\Http\Controllers\Api;

use App\Models\Album;
use Illuminate\Database\Eloquent\Model;

class AlbumController extends ResourceController
{
    protected string $modelClass = Album::class;

    protected function rules(?Model $record = null): array
    {
        return [
            'source_id' => ['prohibited'],
            'user_id' => [$record ? 'sometimes' : 'required', 'integer', 'exists:users,id'],
            'title' => [$record ? 'sometimes' : 'required', 'string', 'max:255'],
        ];
    }
}
