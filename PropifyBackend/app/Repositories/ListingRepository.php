<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingVerificationDocument;
use App\Models\ListingVideo;
use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ListingRepository
{
    public function createProperty(array $attributes): Property;

    public function createListing(array $attributes): Listing;

    public function createListingImage(array $attributes): ListingImage;

    public function createListingVideo(array $attributes): ListingVideo;

    public function createVerificationDocument(array $attributes): ListingVerificationDocument;

    public function createAppointment(array $attributes): Appointment;

    public function paginateByOwner(int $ownerId, ?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator;
}
