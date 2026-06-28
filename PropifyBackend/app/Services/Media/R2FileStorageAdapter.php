<?php

declare(strict_types=1);

namespace App\Services\Media;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;

final class R2FileStorageAdapter implements FileStorageAdapter
{
    private ?S3Client $s3Client = null;

    public function upload(string $path, string $contents, string $mimeType): bool
    {
        return Storage::disk('r2')->put($path, $contents, [
            'ContentType' => $mimeType,
        ]);
    }

    public function getPublicUrl(string $path): string
    {
        $client = $this->getS3Client();
        $getCommand = $client->getCommand('GetObject', [
            'Bucket' => config('filesystems.disks.r2.bucket'),
            'Key' => $path,
        ]);
        $presignedGetRequest = $client->createPresignedRequest($getCommand, '+7 days');

        return (string) $presignedGetRequest->getUri();
    }

    private function getS3Client(): S3Client
    {
        if ($this->s3Client === null) {
            $this->s3Client = new S3Client([
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

        return $this->s3Client;
    }
}
