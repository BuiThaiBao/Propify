<?php

namespace App\Services\Listing\Reports;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class ReportListingCommand
{
    public function __construct(
        private ListingReportValidationChain $validationChain,
    ) {}

    public function handle(User $reporter, int $listingId, array $payload): Collection
    {
        $listing = Listing::query()->find($listingId);

        if ($listing === null) {
            throw new BusinessException(ErrorCode::ListingNotFound);
        }

        $context = new ListingReportContext(
            listing: $listing,
            reporter: $reporter,
            reasons: array_values(array_unique($payload['reasons'])),
            description: $payload['description'] ?? null,
        );

        $this->validationChain->validate($context);

        $reportGroupId = (string) Str::uuid();

        return collect($context->reasons)
            ->map(fn (string $reason) => ListingReport::query()->create([
                'listing_id' => $listing->id,
                'reporter_id' => $reporter->id,
                'report_group_id' => $reportGroupId,
                'reason' => $reason,
                'description' => $context->description ?: self::reasonDescription($reason),
                'image_urls' => array_values($payload['image_urls'] ?? []),
                'status' => ListingReport::STATUS_WARNING,
            ]));
    }

    private static function reasonDescription(string $reason): string
    {
        return match ($reason) {
            'WRONG_PRICE' => 'Định giá chưa đúng với thực tế',
            'WRONG_ADDRESS' => 'Địa chỉ của BĐS chưa chính xác',
            'SOLD_OR_RENTED' => 'BĐS đã bán/đã thuê/đã sang nhượng',
            'WRONG_INFORMATION' => 'Thông tin chưa chính xác',
            'UNREACHABLE_OWNER' => 'Không liên lạc được với người đăng tin',
            'DUPLICATE_LISTING' => 'Trùng với tin rao khác',
            default => $reason,
        };
    }
}
