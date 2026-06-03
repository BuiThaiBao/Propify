<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null,
            'listing' => $this->listing ? [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
            ] : null,
            'package' => $this->package ? [
                'id' => $this->package->id,
                'name' => $this->package->name,
            ] : null,
            'amount' => $this->amount,
            'duration_days' => $this->duration_days,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'transaction_date' => $this->transaction_date?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Các trường đối soát VNPay
            'vnp_txn_ref' => $this->vnp_txn_ref,
            'vnp_transaction_no' => $this->vnp_transaction_no,
            'vnp_bank_code' => $this->vnp_bank_code,
            'vnp_response_code' => $this->vnp_response_code,
            'vnp_pay_date' => $this->vnp_pay_date,

            // Lịch sử ghi chú (Audit Trail)
            'notes' => $this->relationLoaded('notes') ? $this->notes->map(fn ($note) => [
                'id' => $note->id,
                'note' => $note->note,
                'created_at' => $note->created_at?->toIso8601String(),
                'admin' => $note->admin ? [
                    'id' => $note->admin->id,
                    'full_name' => $note->admin->full_name,
                ] : null,
            ]) : [],
        ];
    }
}
