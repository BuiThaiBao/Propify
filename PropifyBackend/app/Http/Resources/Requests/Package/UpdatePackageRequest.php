<?php

namespace App\Http\Resources\Requests\Package;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === UserRole::Admin;
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0'],
            'priority' => ['required', 'integer', 'min:1'],
            'multiplier' => ['required', 'numeric', 'min:1'],
            'daily_quota' => ['required', 'integer', 'min:0'],
            'decay_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'badge' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
