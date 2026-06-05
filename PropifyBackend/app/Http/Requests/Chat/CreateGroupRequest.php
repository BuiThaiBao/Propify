<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'member_ids' => ['required', 'array', 'min:1', 'max:49'],
            'member_ids.*' => ['integer', 'exists:users,id', 'distinct'],
            'avatar_url' => ['nullable', 'string', 'url'],
        ];
    }
}
