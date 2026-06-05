<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'url'],
        ];
    }
}
