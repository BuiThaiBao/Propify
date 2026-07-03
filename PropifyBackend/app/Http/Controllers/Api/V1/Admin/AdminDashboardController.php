<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Models\AuditLog;
use App\Models\Listing;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AdminDashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:month,quarter,year,custom',
            'from_date' => 'nullable|required_if:period,custom|date',
            'to_date' => 'nullable|required_if:period,custom|date|after_or_equal:from_date',
        ]);

        $period = $validated['period'] ?? 'year';
        [$fromDate, $toDate] = $this->resolveDateRange($validated);
        $previousDays = $fromDate->diffInDays($toDate) + 1;
        $previousToDate = $fromDate->copy()->subDay();
        $previousFromDate = $previousToDate->copy()->subDays($previousDays - 1);

        $listingCounts = Listing::query()
            ->whereBetween('created_at', [$fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay()])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalListings = (int) $listingCounts->sum();
        $approvedListings = (int) ($listingCounts['ACTIVE'] ?? 0);
        $rejectedListings = (int) ($listingCounts['REJECTED'] ?? 0);
        $pendingListings = (int) ($listingCounts['PENDING'] ?? 0);
        $lockedListings = (int) ($listingCounts['LOCKED'] ?? 0);

        $totalUsers = User::where('role', '!=', 'ADMIN')
            ->whereBetween('created_at', [$fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay()])
            ->count();

        $currentRevenue = (float) Transaction::where('status', 'SUCCESS')
            ->whereBetween('transaction_date', [$fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay()])
            ->sum('amount');
            
        $previousRevenue = (float) Transaction::where('status', 'SUCCESS')
            ->whereBetween('transaction_date', [$previousFromDate->copy()->startOfDay(), $previousToDate->copy()->endOfDay()])
            ->sum('amount');

        $currentListings = Listing::whereBetween('created_at', [$fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay()])->count();
        $previousListings = Listing::whereBetween('created_at', [$previousFromDate->copy()->startOfDay(), $previousToDate->copy()->endOfDay()])->count();

        $revenueChart = [];
        $monthlyRevenueRows = Transaction::where('status', 'SUCCESS')
            ->whereBetween('transaction_date', [$fromDate->copy()->startOfDay(), $toDate->copy()->endOfDay()])
            ->selectRaw('YEAR(transaction_date) as year, MONTH(transaction_date) as month, SUM(amount) as revenue')
            ->groupByRaw('YEAR(transaction_date), MONTH(transaction_date)')
            ->get()
            ->keyBy(fn ($row) => $row->year.'-'.$row->month);
            
        $cursor = $fromDate->copy()->startOfMonth();
        $lastMonth = $toDate->copy()->startOfMonth();
        while ($cursor <= $lastMonth) {
            $key = $cursor->year.'-'.$cursor->month;
            $row = $monthlyRevenueRows->get($key);
            $revenueChart[] = [
                'month' => $cursor->year === $toDate->year ? 'T'.$cursor->month : 'T'.$cursor->month.'/'.$cursor->year,
                'revenue' => (float) ($row?->revenue ?? 0)
            ];
            $cursor->addMonth();
        }

        $recentActivities = AuditLog::with('actor:id,full_name,email')
            ->latest('created_at')->limit(10)->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id, 'action' => $log->action,
                'auditable_type' => $log->auditable_type, 'auditable_id' => $log->auditable_id,
                'actor' => $log->actor ? ['id' => $log->actor->id, 'full_name' => $log->actor->full_name] : null,
                'changes' => $log->changes ?? [], 'metadata' => $log->metadata ?? [],
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success(data: [
            'period' => $period,
            'label' => $this->revenuePeriodLabel($period, $fromDate, $toDate),
            'listings' => ['total' => $totalListings, 'approved' => $approvedListings, 'rejected' => $rejectedListings, 'pending' => $pendingListings, 'locked' => $lockedListings],
            'users' => ['total' => $totalUsers],
            'revenue' => ['total' => $currentRevenue, 'current_month' => $currentRevenue, 'last_month' => $previousRevenue], // Kept current_month/last_month for backwards compatibility
            'listings_change' => ['current_month' => $currentListings, 'last_month' => $previousListings],
            'revenue_chart' => $revenueChart,
            'recent_activities' => $recentActivities,
        ], message: 'Lay thong ke dashboard thanh cong.');
    }
    
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(array $validated): array
    {
        $period = $validated['period'] ?? 'year';
        $now = Carbon::now();

        if ($period === 'custom') {
            return [
                Carbon::parse($validated['from_date'])->startOfDay(),
                Carbon::parse($validated['to_date'])->endOfDay(),
            ];
        }

        $fromDate = match ($period) {
            'month' => $now->copy()->startOfMonth(),
            'quarter' => $now->copy()->startOfQuarter(),
            default => $now->copy()->startOfYear(),
        };

        return [$fromDate->startOfDay(), $now->copy()->endOfDay()];
    }

    private function revenuePeriodLabel(string $period, Carbon $fromDate, Carbon $toDate): string
    {
        return match ($period) {
            'month' => 'Tháng này',
            'quarter' => 'Quý này',
            'custom' => $fromDate->format('d/m/Y').' - '.$toDate->format('d/m/Y'),
            default => 'Năm nay',
        };
    }
}
