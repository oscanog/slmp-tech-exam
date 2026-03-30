<?php

namespace App\Http\Controllers\Api;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Model;

class PhotoController extends ResourceController
{
    protected string $modelClass = Photo::class;

    protected function rules(?Model $record = null): array
    {
        return [
            'source_id' => ['prohibited'],
            'album_id' => [$record ? 'sometimes' : 'required', 'integer', 'exists:albums,id'],
            'title' => [$record ? 'sometimes' : 'required', 'string', 'max:255'],
            'url' => [$record ? 'sometimes' : 'required', 'url', 'max:2048'],
            'thumbnail_url' => [$record ? 'sometimes' : 'required', 'url', 'max:2048'],
        ];
    }
}
