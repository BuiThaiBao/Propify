<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chat;

use App\Helpers\ApiResponse;
use App\Services\Media\FileStorageAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Upload file chat: backend nhận file, upload lên R2, trả về URL.
 *
 * POST /v1/chat/upload
 * Body: multipart/form-data { file, type }
 */
final class FileUploadController
{
    public function __construct(
        private readonly FileStorageAdapter $storage,
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:30720'], // max 30MB
            'type' => ['required', 'string', Rule::in(['image', 'file'])],
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $userId = $request->user()?->id ?? 'anonymous';
        $fileKey = sprintf(
            'chat/%s/%s-%s.%s',
            $userId,
            now()->format('Y/m/d'),
            (string) Str::uuid(),
            $extension,
        );

        // Upload lên R2 qua Adapter
        $this->storage->upload(
            path: $fileKey,
            contents: file_get_contents($file->getRealPath()),
            mimeType: $file->getMimeType() ?: 'application/octet-stream',
        );
        $publicUrl = $this->storage->getPublicUrl($fileKey);

        return ApiResponse::success([
            'public_url' => $publicUrl,
            'file_key' => $fileKey,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
}
