<?php

namespace App\DTOs\Listing;

final readonly class CreateListingDto
{
    /**
     * @param UploadedFile[] $images
     * @param int[] $attributeIds
     * @param UploadedFile[] $legalDocuments
     */
    public function __construct(
        public string $demandType,
        public string $title,
        public string $description,
        public string $propertyType,
        public string $provinceCode,
        public ?string $wardCode,
        public ?string $streetCode,
        public ?string $projectName,
        public ?string $addressDetail,
        public float $area,
        public float $price,
        public bool $isNegotiable,
        public int $bedrooms,
        public int $bathrooms,
        public ?int $floors,
        public ?int $floorNumber,
        public ?int $balconies,
        public ?float $facadeWidth,
        public ?float $depth,
        public ?float $roadWidth,
        public ?string $directionCode,
        public ?string $balconyDirectionCode,
        public ?string $furnitureStatus,
        public string $contactName,
        public string $contactPhone,
        public ?string $contactEmail,
        public string $posterType,
        public ?float $lat,
        public ?float $lng,
        public array $images,
        public ?string $video,
        public array $attributeIds,
        public array $amenities,
        public array $legalPaperTypes,
        public bool $publicInfoAgreed,
        public bool $requestVerification,
        public ?string $identityCardFront,
        public ?string $identityCardBack,
        public array $legalDocuments,
        public ?string $appointmentAt,
        public array $appointmentDays,
        public ?string $appointmentTimeSlot,
        public ?string $rentMinTerm,
        public ?string $rentPaymentInterval,
        public ?string $rentDeposit,
        public ?string $appointmentContactName,
        public ?string $appointmentContactPhone,
        public ?string $appointmentContactEmail,
        public ?string $appointmentNote,
        public ?int $packageId,
        public bool $saveAsDraft = false,
    ) {
    }
}
