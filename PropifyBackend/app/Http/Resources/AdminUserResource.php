<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdminUserResource extends JsonResource
{
    private const AVATAR_COLORS = [
        ['bg' => '#eff6ff', 'text' => '#2563eb'],
        ['bg' => '#fef3c7', 'text' => '#d97706'],
        ['bg' => '#d1fae5', 'text' => '#059669'],
        ['bg' => '#ede9fe', 'text' => '#7c3aed'],
        ['bg' => '#fce7f3', 'text' => '#db2777'],
        ['bg' => '#f1f5f9', 'text' => '#475569'],
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 1. Determine role: 'agent' if they have posted BROKER listings, otherwise 'user'
        $isAgent = (bool) ($this->has_broker_listing ?? $this->has_broker_listing_exists ?? false);
        $role = $isAgent ? 'agent' : 'user';

        // 2. Map status
        $isBanned = $this->status?->value === 'BAN';
        $status = $isBanned ? 'locked' : 'active';

        // 3. Compute initial
        $initial = '';
        if (! empty($this->full_name)) {
            $initial = mb_strtoupper(mb_substr(trim((string) $this->full_name), 0, 1));
        }

        // 4. Select colors deterministically
        $colorPair = self::AVATAR_COLORS[$this->id % count(self::AVATAR_COLORS)];

        $isGoogle = ! empty($this->google_id);

        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'joinDate' => 'Tham gia: '.($this->created_at ? $this->created_at->format('d/m/Y') : ''),
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $role,
            'roleLabel' => $role === 'agent' ? 'Môi giới' : 'Người dùng',
            'posts' => (int) ($this->posts_count ?? 0),
            'status' => $status,
            'statusLabel' => $status === 'locked' ? 'Đã khóa' : 'Hoạt động',
            'initial' => $initial,
            'avatarBg' => $colorPair['bg'],
            'avatarColor' => $colorPair['text'],
            'isGoogleAccount' => $isGoogle,
            'avatarUrl' => $this->avatar_url,
            'authType' => $isGoogle ? 'google' : 'email',
        ];
    }
}
