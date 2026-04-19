<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * ChatTestSeeder — Tạo 2 user test + 1 conversation + sample messages.
 *
 * Chạy: php artisan db:seed --class=ChatTestSeeder
 */
class ChatTestSeeder extends Seeder
{
    public function run(): void
    {
        // ── Tạo 2 user test ──────────────────────────────────────────────
        $userA = User::firstOrCreate(
            ['email' => 'chat-user-a@test.com'],
            [
                'full_name' => 'Test User A',
                'password'  => Hash::make('password123'),
                'phone'     => '0901111111',
            ]
        );

        $userB = User::firstOrCreate(
            ['email' => 'chat-user-b@test.com'],
            [
                'full_name' => 'Test User B',
                'password'  => Hash::make('password123'),
                'phone'     => '0902222222',
            ]
        );

        // ── Tạo hoặc lấy conversation (normalized a < b) ─────────────────
        $participantA = min($userA->id, $userB->id);
        $participantB = max($userA->id, $userB->id);

        $conversation = Conversation::firstOrCreate(
            [
                'participant_a_id' => $participantA,
                'participant_b_id' => $participantB,
                'listing_id'       => null,
            ]
        );

        // ── Tạo participants ──────────────────────────────────────────────
        ConversationParticipant::firstOrCreate([
            'conversation_id' => $conversation->id,
            'user_id'         => $userA->id,
        ]);

        ConversationParticipant::firstOrCreate([
            'conversation_id' => $conversation->id,
            'user_id'         => $userB->id,
        ]);

        // ── Tạo sample messages ───────────────────────────────────────────
        $sampleMessages = [
            [$userA->id, 'Xin chào! Tôi muốn hỏi về bất động sản này.'],
            [$userB->id, 'Chào bạn! Mình có thể tư vấn cho bạn nhé.'],
            [$userA->id, 'Căn hộ này diện tích bao nhiêu m2 vậy ạ?'],
            [$userB->id, 'Căn 70m2, 2 phòng ngủ, view đẹp, giá 3.5 tỷ.'],
            [$userA->id, 'Có thể xem thực tế không ạ?'],
            [$userB->id, 'Được chứ! Bạn rảnh cuối tuần nào thì mình arrange nhé 😊'],
        ];

        // Chỉ seed messages nếu chưa có
        if (Message::where('conversation_id', $conversation->id)->doesntExist()) {
            foreach ($sampleMessages as [$senderId, $body]) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => $senderId,
                    'type'            => 'text',
                    'body'            => $body,
                ]);
            }
        }

        // ── Output ────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('✅ Chat Test Data Created:');
        $this->command->table(
            ['Field', 'User A', 'User B'],
            [
                ['Email',    $userA->email,       $userB->email],
                ['Password', 'password123',       'password123'],
                ['ID',       $userA->id,          $userB->id],
            ]
        );
        $this->command->info("📦 Conversation ID: {$conversation->id}");
        $this->command->info('');
        $this->command->comment('👉 Mở 2 tab trình duyệt, đăng nhập mỗi tab 1 user để test real-time.');
        $this->command->comment('👉 Hoặc dùng FloatingChat → conversation đã có sẵn.');
    }
}
