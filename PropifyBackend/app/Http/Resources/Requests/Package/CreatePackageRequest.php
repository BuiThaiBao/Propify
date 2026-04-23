<?php

namespace App\Http\Resources\Requests\Package;

use App\Enums\PackageType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === UserRole::Admin;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:packages,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'priority' => ['required', 'integer', 'min:1'],
            'multiplier' => ['required', 'numeric', 'min:1'],
            'daily_quota' => ['required', 'integer', 'min:0'],
            'decay_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'badge' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
        ];
    }
}
