<?php

namespace App\Http\Resources\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'     => ['sometimes', 'string', Rule::in(['text', 'image', 'file'])],
            'body'     => ['required_if:type,text', 'nullable', 'string', 'max:5000'],
            'file_url' => ['required_if:type,image,file', 'nullable', 'string', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_if'     => 'Nội dung tin nhắn không được để trống.',
            'body.max'             => 'Tin nhắn không được vượt quá 5000 ký tự.',
            'file_url.required_if' => 'URL file/ảnh không được để trống.',
            'file_url.url'         => 'URL file/ảnh không hợp lệ.',
            'type.in'              => 'Loại tin nhắn phải là: text, image, hoặc file.',
        ];
    }
}
