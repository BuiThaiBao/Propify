<?php

namespace App\Http\Controllers\Api\V1\Cloudinary;

use App\Helpers\ApiResponse;
use App\Services\Cloudinary\CloudinaryService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cung cấp signed signature để frontend upload ảnh trực tiếp lên Cloudinary (Option B).
 *
 * Flow:
 *   Frontend → POST /v1/cloudinary/sign?type=avatar
 *            ← { signature, api_key, cloud_name, timestamp, folder, upload_preset }
 *   Frontend → Upload file lên Cloudinary bằng signature trên
 *            ← { secure_url, public_id, ... }
 *   Frontend → PUT /v1/user/avatar { avatar_url }
 *            ← User updated
 */
final class CloudinaryController
{
    public function __construct(
        private readonly CloudinaryService $cloudinaryService,
        private readonly AuthFactory $authFactory
    ) {
    }

    /**
     * Tạo upload signature.
     *
     * @param  Request  $request  Query param: type = 'avatar' | 'listing'
     */
    public function sign(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:avatar,listing',
        ]);

        $uploadType = $request->query('type', 'avatar');

        /** @var \App\Models\User $user */
        $user   = $this->authFactory->guard('api')->user();
        $folder = match ($uploadType) {
            'avatar'  => 'propify/avatars',
            'listing' => 'propify/listings',
            default   => 'propify/avatars',
        };

        $signatureData = $this->cloudinaryService->generateSignature($folder, $uploadType);

        return ApiResponse::success(
            data: $signatureData,
            message: 'Signature generated successfully.'
        );
    }
}
