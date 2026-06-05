<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class AddGroupMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1', 'max:20'],
            'user_ids.*' => ['integer', 'exists:users,id', 'distinct'],
        ];
    }
}
