<?php

namespace App\Http\Resources\Requests\Package;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === UserRole::Admin;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('packages', 'slug')->ignore($this->route('id')),
            ],
            'price' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['required', 'integer', 'min:1'],
            'multiplier' => ['required', 'numeric', 'min:1'],
            'daily_quota' => ['required', 'integer', 'min:0'],
            'decay_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'badge' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'active_durations' => ['nullable', 'array'],
            'active_durations.*' => ['integer', 'min:1', 'max:3650', 'distinct'],
        ];
    }
}
