<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Models\AuditLog;
use App\Models\Listing;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $listingCounts = Listing::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalListings = (int) $listingCounts->sum();
        $approvedListings = (int) ($listingCounts['ACTIVE'] ?? 0);
        $rejectedListings = (int) ($listingCounts['REJECTED'] ?? 0);
        $pendingListings = (int) ($listingCounts['PENDING'] ?? 0);
        $lockedListings = (int) ($listingCounts['LOCKED'] ?? 0);

        $totalUsers = User::where('role', '!=', 'ADMIN')->count();
        $totalRevenue = (float) Transaction::where('status', 'SUCCESS')->sum('amount');

        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $currentMonthRevenue = (float) Transaction::where('status', 'SUCCESS')
            ->whereYear('transaction_date', $currentMonth->year)
            ->whereMonth('transaction_date', $currentMonth->month)
            ->sum('amount');

        $lastMonthRevenue = (float) Transaction::where('status', 'SUCCESS')
            ->whereYear('transaction_date', $lastMonth->year)
            ->whereMonth('transaction_date', $lastMonth->month)
            ->sum('amount');

        $currentMonthListings = Listing::whereYear('created_at', $currentMonth->year)
            ->whereMonth('created_at', $currentMonth->month)->count();
        $lastMonthListings = Listing::whereYear('created_at', $lastMonth->year)
            ->whereMonth('created_at', $lastMonth->month)->count();

        $currentYear = Carbon::now()->year;
        $monthlyRevenue = Transaction::where('status', 'SUCCESS')
            ->whereYear('transaction_date', $currentYear)
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as revenue')
            ->groupByRaw('MONTH(transaction_date)')
            ->pluck('revenue', 'month');

        $revenueChart = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenueChart[] = ['month' => 'T'.$m, 'revenue' => (float) ($monthlyRevenue[$m] ?? 0)];
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
            'listings' => ['total' => $totalListings, 'approved' => $approvedListings, 'rejected' => $rejectedListings, 'pending' => $pendingListings, 'locked' => $lockedListings],
            'users' => ['total' => $totalUsers],
            'revenue' => ['total' => $totalRevenue, 'current_month' => $currentMonthRevenue, 'last_month' => $lastMonthRevenue],
            'listings_change' => ['current_month' => $currentMonthListings, 'last_month' => $lastMonthListings],
            'revenue_chart' => $revenueChart,
            'recent_activities' => $recentActivities,
        ], message: 'Lay thong ke dashboard thanh cong.');
    }
}
