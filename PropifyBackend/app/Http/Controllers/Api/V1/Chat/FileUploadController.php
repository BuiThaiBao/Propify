<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chat;

use App\Helpers\ApiResponse;
use App\Models\ChatUpload;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Upload file chat qua Presigned URL + DB mapping.
 *
 * Flow:
 *   1. POST /v1/chat/presign – tạo Presigned PUT URL, lưu mapping file_key → metadata
 *   2. FE PUT file thẳng lên R2
 *   3. FE gửi message với public_url (presigned GET)
 *   4. GET /v1/chat/file-url – tạo Presigned GET URL khi user mở message
 */
final class FileUploadController
{
    private S3Client $client;
    private string $bucket;

    public function __construct()
    {
        $this->bucket = config('filesystems.disks.r2.bucket');
        $this->client = new S3Client([
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
    }

    /**
     * Bước 1: Tạo Presigned PUT URL + lưu mapping.
     */
    public function presign(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['image', 'file'])],
            'extension' => ['required', 'string', 'max:10'],
            'content_type' => ['required', 'string', 'max:100'],
            'file_name' => ['required', 'string', 'max:255'],
            'file_size' => ['required', 'integer', 'min:1', 'max:31457280'], // 30MB
        ]);

        $userId = $request->user()->id;
        $fileKey = sprintf(
            'chat/%s/%s.%s',
            $userId,
            (string) Str::uuid(),
            $request->input('extension'),
        );

        // Lưu mapping
        ChatUpload::create([
            'user_id' => $userId,
            'file_key' => $fileKey,
            'original_name' => $request->input('file_name'),
            'file_size' => $request->input('file_size'),
            'mime_type' => $request->input('content_type'),
            'type' => $request->input('type'),
        ]);

        // Presigned PUT URL (15 phút)
        $putCommand = $this->client->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key' => $fileKey,
            'ContentType' => $request->input('content_type'),
        ]);
        $presignedPut = $this->client->createPresignedRequest($putCommand, '+15 minutes');
        $presignedUrl = (string) $presignedPut->getUri();

        return ApiResponse::success([
            'presigned_url' => $presignedUrl,
            'file_key' => $fileKey,
        ]);
    }

    /**
     * Bước 2: Lấy Presigned GET URL từ file_key (gọi khi render message).
     */
    public function getFileUrl(Request $request): JsonResponse
    {
        $request->validate([
            'file_key' => ['required', 'string', 'max:500'],
        ]);

        $fileKey = $request->input('file_key');

        $getCommand = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $fileKey,
        ]);
        $presignedGet = $this->client->createPresignedRequest($getCommand, '+7 days');
        $publicUrl = (string) $presignedGet->getUri();

        return ApiResponse::success([
            'public_url' => $publicUrl,
        ]);
    }
}
