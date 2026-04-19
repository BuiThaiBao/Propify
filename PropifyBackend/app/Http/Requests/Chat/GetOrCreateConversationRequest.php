<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class GetOrCreateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'other_user_id' => ['required', 'integer', 'exists:users,id'],
            'listing_id'    => ['nullable', 'integer', 'exists:listings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'other_user_id.required' => 'Vui lòng chỉ định người dùng muốn liên hệ.',
            'other_user_id.exists'   => 'Người dùng không tồn tại.',
            'listing_id.exists'      => 'Bài đăng không tồn tại.',
        ];
    }
}
