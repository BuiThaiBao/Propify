<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ApiResponse;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\TransactionNote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Payment\Export\TransactionExportStrategyFactory;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

final class AdminTransactionController extends Controller
{
    public function revenueStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:'.(Carbon::now()->year + 1),
        ]);

        $year = (int) ($validated['year'] ?? Carbon::now()->year);
        $previousYear = $year - 1;

        $successfulTransactions = Transaction::query()
            ->where('status', 'SUCCESS')
            ->whereYear('transaction_date', $year);

        $previousSuccessfulTransactions = Transaction::query()
            ->where('status', 'SUCCESS')
            ->whereYear('transaction_date', $previousYear);

        $totalRevenue = (float) (clone $successfulTransactions)->sum('amount');
        $soldPackages = (int) (clone $successfulTransactions)->count();
        $previousRevenue = (float) (clone $previousSuccessfulTransactions)->sum('amount');
        $previousSoldPackages = (int) (clone $previousSuccessfulTransactions)->count();

        $monthlyRows = (clone $successfulTransactions)
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as revenue, COUNT(*) as packages')
            ->groupByRaw('MONTH(transaction_date)')
            ->pluck('revenue', 'month');

        $monthlyPackageRows = (clone $successfulTransactions)
            ->selectRaw('MONTH(transaction_date) as month, COUNT(*) as packages')
            ->groupByRaw('MONTH(transaction_date)')
            ->pluck('packages', 'month');

        $monthlyRevenue = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyRevenue[] = [
                'month' => 'T'.$month,
                'month_number' => $month,
                'revenue' => (float) ($monthlyRows[$month] ?? 0),
                'packages' => (int) ($monthlyPackageRows[$month] ?? 0),
            ];
        }

        $palette = ['#3b82f6', '#f59e0b', '#22c55e', '#94a3b8', '#ef4444', '#8b5cf6'];

        $packageRows = (clone $successfulTransactions)
            ->leftJoin('packages', 'transactions.package_id', '=', 'packages.id')
            ->select([
                'packages.id',
                DB::raw("COALESCE(packages.name, 'Không xác định') as name"),
                'packages.slug',
                'packages.color',
                DB::raw('COUNT(transactions.id) as sold_count'),
                DB::raw('SUM(transactions.amount) as revenue'),
            ])
            ->groupBy('packages.id', 'packages.name', 'packages.slug', 'packages.color')
            ->orderByDesc('sold_count')
            ->get();

        $totalSoldByPackage = (int) $packageRows->sum('sold_count');
        $packageDistribution = $packageRows->values()->map(function ($row, int $index) use ($totalSoldByPackage, $palette) {
            $soldCount = (int) $row->sold_count;

            return [
                'id' => $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'color' => $row->color ?: $palette[$index % count($palette)],
                'count' => $soldCount,
                'revenue' => (float) $row->revenue,
                'percentage' => $totalSoldByPackage > 0
                    ? round(($soldCount / $totalSoldByPackage) * 100, 1)
                    : 0,
            ];
        });

        return ApiResponse::success(data: [
            'year' => $year,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'sold_packages' => $soldPackages,
                'average_monthly_revenue' => round($totalRevenue / 12, 2),
                'revenue_change_percent' => $this->percentageChange($totalRevenue, $previousRevenue),
                'sold_packages_change_percent' => $this->percentageChange($soldPackages, $previousSoldPackages),
            ],
            'monthly_revenue' => $monthlyRevenue,
            'package_distribution' => $packageDistribution,
        ], message: 'Lấy thống kê doanh thu thành công.');
    }

    public function __construct(
        private readonly TransactionExportStrategyFactory $exportFactory
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:PENDING,SUCCESS,FAILED,EXPIRED',
            'payment_method' => 'nullable|string|max:50',
            'package_id' => 'nullable|integer|exists:packages,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $query = Transaction::query()
            ->with(['user:id,full_name,email,phone', 'listing:id,title', 'package:id,name']);

        // Áp dụng bộ lọc cho danh sách kết quả phân trang
        $query = $this->applyFilters($query, $validated);

        // Tính thống kê động (revenue & counts) thỏa mãn các filter khác nhưng BỎ QUA bộ lọc status
        $statisticsQuery = Transaction::query();
        $filtersWithoutStatus = $validated;
        unset($filtersWithoutStatus['status']);
        $statisticsQuery = $this->applyFilters($statisticsQuery, $filtersWithoutStatus);

        // Tổng doanh thu (chỉ tính SUCCESS)
        $totalRevenue = (float) (clone $statisticsQuery)->where('status', 'SUCCESS')->sum('amount');

        // Đếm số lượng theo từng trạng thái giao dịch
        $statusCounts = (clone $statisticsQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $successCount = $statusCounts['SUCCESS'] ?? 0;
        $pendingCount = $statusCounts['PENDING'] ?? 0;
        $failedCount = ($statusCounts['FAILED'] ?? 0) + ($statusCounts['EXPIRED'] ?? 0);

        // Sắp xếp và phân trang
        $perPage = (int) ($validated['per_page'] ?? 10);
        $paginator = $query->latest('transaction_date')->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách lịch sử giao dịch thành công.',
            'data' => TransactionResource::collection($paginator->items()),
            'summary' => [
                'total_revenue' => number_format($totalRevenue, 2, '.', ''),
                'counts' => [
                    'SUCCESS' => $successCount,
                    'PENDING' => $pendingCount,
                    'FAILED' => $failedCount,
                ],
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with([
            'user:id,full_name,email,phone',
            'listing:id,title',
            'package:id,name',
            'notes.admin:id,full_name',
        ])->findOrFail($id);

        return ApiResponse::success(
            data: new TransactionResource($transaction),
            message: 'Lấy chi tiết giao dịch thành công.'
        );
    }

    public function storeNote(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $transaction = Transaction::findOrFail($id);

        $note = TransactionNote::create([
            'transaction_id' => $transaction->id,
            'admin_id' => $request->user()->id,
            'note' => $validated['note'],
        ]);

        $note->load('admin:id,full_name');

        return ApiResponse::created([
            'id' => $note->id,
            'note' => $note->note,
            'created_at' => $note->created_at?->toIso8601String(),
            'admin' => [
                'id' => $note->admin->id,
                'full_name' => $note->admin->full_name,
            ],
        ], 'Thêm ghi chú giao dịch thành công.');
    }

    public function export(Request $request): Response
    {
        $validated = $request->validate([
            'format' => 'required|string|in:excel,pdf,xlsx,xls',
            'status' => 'nullable|string|in:PENDING,SUCCESS,FAILED,EXPIRED',
            'payment_method' => 'nullable|string|max:50',
            'package_id' => 'nullable|integer|exists:packages,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Transaction::query()
            ->with(['user', 'listing', 'package', 'notes.admin'])
            ->latest('transaction_date');

        $query = $this->applyFilters($query, $validated);

        $strategy = $this->exportFactory->make($validated['format']);

        return $strategy->export($query);
    }

    private function applyFilters($query, array $filters)
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                }
                $q->orWhere('vnp_txn_ref', 'like', "%{$search}%")
                    ->orWhere('vnp_transaction_no', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function percentageChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
