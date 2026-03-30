<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class ResourceController extends Controller
{
    /**
     * @var class-string<Model>
     */
    protected string $modelClass;

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(?Model $record = null): array;

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        return $this->query()->paginate($perPage);
    }

    public function show(int $id): Model
    {
        return $this->findRecord($id);
    }

    public function store(Request $request): JsonResponse
    {
        $modelClass = $this->modelClass;
        $record = $modelClass::create($this->mutateValidatedData($request->validate($this->rules())));

        return response()->json($record, Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id): Model
    {
        $record = $this->findRecord($id);
        $record->fill($this->mutateValidatedData($request->validate($this->rules($record)), $record));

        if ($record->isDirty()) {
            $record->save();
        }

        return $record->refresh();
    }

    public function destroy(int $id)
    {
        $record = $this->findRecord($id);
        $record->delete();

        return response()->noContent();
    }

    protected function mutateValidatedData(array $validated, ?Model $record = null): array
    {
        return $validated;
    }

    protected function query(): Builder
    {
        $modelClass = $this->modelClass;

        return $modelClass::query()->orderBy('id');
    }

    protected function findRecord(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }
}
