<?php

namespace App\Services\Media;

interface UploadSignatureAdapter
{
    public function generateSignature(string $folder, string $uploadType): array;
}
