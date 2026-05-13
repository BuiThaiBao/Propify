<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\UserFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FavoriteController
{
    public function index(Request $request): JsonResponse
    {
        $favorites = UserFavorite::query()
            ->with([
                'listing.property.attributes.group',
                'listing.images',
                'listing.videos',
                'listing.verificationDocuments',
                'listing.appointmentSlots',
                'listing.appointments',
                'listing.owner',
                'listing.package',
            ])
            ->where('user_id', $request->user()->id)
            ->where('type', 'FAVORITE')
            ->latest()
            ->get();

        $listings = $favorites
            ->pluck('listing')
            ->filter()
            ->values()
            ->each(fn (Listing $listing) => $listing->setAttribute('is_favorited', true));

        return ApiResponse::success(
            data: ListingResource::collection($listings),
            message: 'Lay danh sach tin yeu thich thanh cong.'
        );
    }

    public function ids(Request $request): JsonResponse
    {
        $ids = UserFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('type', 'FAVORITE')
            ->pluck('listing_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return ApiResponse::success(data: $ids);
    }

    public function toggle(Request $request, int $listingId): JsonResponse
    {
        Listing::query()->findOrFail($listingId);

        $favorite = UserFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('listing_id', $listingId)
            ->where('type', 'FAVORITE')
            ->first();

        if ($favorite) {
            $favorite->delete();

            return ApiResponse::success(
                data: ['is_favorited' => false],
                message: 'Da bo yeu thich tin dang.'
            );
        }

        UserFavorite::query()->create([
            'user_id' => $request->user()->id,
            'listing_id' => $listingId,
            'type' => 'FAVORITE',
        ]);

        return ApiResponse::success(
            data: ['is_favorited' => true],
            message: 'Da them tin dang vao danh sach yeu thich.'
        );
    }
}
