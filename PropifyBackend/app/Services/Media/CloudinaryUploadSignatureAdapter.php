<?php

namespace App\Services\Media;

use App\Services\Cloudinary\CloudinaryService;

final class CloudinaryUploadSignatureAdapter implements UploadSignatureAdapter
{
    public function __construct(
        private readonly CloudinaryService $cloudinaryService,
    ) {}

    public function generateSignature(string $folder, string $uploadType): array
    {
        return $this->cloudinaryService->generateSignature($folder, $uploadType);
    }
}
