<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class UserController extends ResourceController
{
    protected string $modelClass = User::class;

    protected function rules(?Model $record = null): array
    {
        $passwordRules = $record
            ? ['sometimes', 'nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return [
            'source_id' => ['prohibited'],
            'name' => [$record ? 'sometimes' : 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => [
                $record ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($record?->getKey()),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'array'],
            'company' => ['sometimes', 'nullable', 'array'],
            'email_verified_at' => ['sometimes', 'nullable', 'date'],
            'password' => $passwordRules,
        ];
    }

    protected function mutateValidatedData(array $validated, ?Model $record = null): array
    {
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        return $validated;
    }
}
