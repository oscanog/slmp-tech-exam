<?php

namespace App\Http\Controllers\Api;

use App\Models\Todo;
use Illuminate\Database\Eloquent\Model;

class TodoController extends ResourceController
{
    protected string $modelClass = Todo::class;

    protected function rules(?Model $record = null): array
    {
        return [
            'source_id' => ['prohibited'],
            'user_id' => [$record ? 'sometimes' : 'required', 'integer', 'exists:users,id'],
            'title' => [$record ? 'sometimes' : 'required', 'string', 'max:255'],
            'completed' => [$record ? 'sometimes' : 'required', 'boolean'],
        ];
    }
}
