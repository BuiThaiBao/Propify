<?php

namespace App\Services\Listing\Moderation;

use App\Enums\ErrorCode;
use App\Events\Listing\ListingSaved;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\ListingStatusHistory;
use App\Services\Listing\State\ListingStatusStateFactory;
use Illuminate\Support\Facades\DB;

/**
 * Template Method cho mọi thao tác kiểm duyệt tin đăng của admin.
 *
 * Khung xử lý (cố định, viết một lần) — giữ NGUYÊN luồng gốc của
 * ListingServiceImpl::changeStatusForAdmin:
 *   1. validate() (hook, mặc định rỗng)
 *   2. mở transaction → khóa & tìm listing → State::assertCanTransition →
 *      mutate() (bước con) → save → ghi ListingStatusHistory
 *   3. load quan hệ → phát ListingSaved('admin_status_changed')
 *
 * Mỗi lệnh con chỉ điền targetStatus() + mutate() (+ validate() nếu cần).
 */
abstract class AbstractListingModerationCommand
{
    /** Quan hệ được nạp lại sau khi đổi trạng thái (giữ nguyên danh sách gốc). */
    private const RELATIONS = [
        'property.attributes.group',
        'images',
        'videos',
        'verificationDocuments',
        'appointmentSlots',
        'appointments',
        'owner',
        'approver',
        'package',
    ];

    public function __construct(
        protected readonly ListingStatusStateFactory $statusStateFactory,
    ) {}

    final public function execute(int $listingId, ModerationContext $ctx): Listing
    {
        $this->validate($ctx);

        $listing = DB::transaction(function () use ($listingId, $ctx) {
            $listing = Listing::query()->lockForUpdate()->find($listingId);
            if (! $listing) {
                throw new BusinessException(ErrorCode::ListingNotFound);
            }

            $this->statusStateFactory->assertCanTransition($listing->status, $this->targetStatus());

            $this->mutate($listing, $ctx);
            $listing->save();

            ListingStatusHistory::create([
                'user_id' => $ctx->adminUserId,
                'listing_id' => $listing->id,
                'action' => $this->targetStatus(),
                'reason' => $ctx->reason,
            ]);

            return $listing;
        });

        $loaded = $listing->load(self::RELATIONS);

        ListingSaved::dispatch($loaded, null, 'admin_status_changed');

        return $loaded;
    }

    /** Trạng thái đích của thao tác (ACTIVE / REJECTED / LOCKED). */
    abstract protected function targetStatus(): string;

    /** Phần thay đổi listing khác nhau giữa các lệnh. */
    abstract protected function mutate(Listing $listing, ModerationContext $ctx): void;

    /** Kiểm tra tiền điều kiện trước transaction (mặc định không có). */
    protected function validate(ModerationContext $ctx): void {}
}
