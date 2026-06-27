<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chat;

use App\Helpers\ApiResponse;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'chat/%s/%s.%s',
            $userId,
            (string) Str::uuid(),
            $extension,
        );

        // Upload lên R2
        $disk = Storage::disk('r2');
        $disk->put($fileKey, file_get_contents($file->getRealPath()), [
            'ContentType' => $file->getMimeType() ?: 'application/octet-stream',
        ]);

        // Tạo Presigned GET URL (7 ngày)
        $client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => config('filesystems.disks.r2.endpoint'),
            'credentials' => [
                'key' => config('filesystems.disks.r2.key'),
                'secret' => config('filesystems.disks.r2.secret'),
            ],
            'use_path_style_endpoint' => true,
            'signature_version' => 'v4',
        ]);

        $getCommand = $client->getCommand('GetObject', [
            'Bucket' => config('filesystems.disks.r2.bucket'),
            'Key' => $fileKey,
        ]);
        $presignedGetRequest = $client->createPresignedRequest($getCommand, '+7 days');
        $publicUrl = (string) $presignedGetRequest->getUri();

        return ApiResponse::success([
            'public_url' => $publicUrl,
            'file_key' => $fileKey,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
}
