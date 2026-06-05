<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class UserTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:PENDING,SUCCESS,FAILED,EXPIRED',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $query = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->with(['listing:id,title', 'package:id,name'])
            ->latest('transaction_date');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = (int) ($validated['per_page'] ?? 10);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách lịch sử giao dịch thành công.',
            'data' => TransactionResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 200);
    }
}
