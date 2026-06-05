<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\DTOs\Chat\CreateGroupDto;
use App\DTOs\Chat\GroupMemberDto;
use App\DTOs\Chat\UpdateGroupDto;
use App\Helpers\ApiResponse;
use App\Http\Requests\Chat\AddGroupMembersRequest;
use App\Http\Requests\Chat\CreateGroupRequest;
use App\Http\Requests\Chat\UpdateGroupRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\GroupMemberResource;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;

final class GroupChatController
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function create(CreateGroupRequest $request): JsonResponse
    {
        $conversation = $this->chatService->createGroup(
            CreateGroupDto::fromRequest($request, auth()->id()),
        );

        return ApiResponse::created(
            data: new ConversationResource($conversation),
            message: 'Tạo nhóm chat thành công',
        );
    }

    public function update(UpdateGroupRequest $request, int $conversationId): JsonResponse
    {
        $conversation = $this->chatService->updateGroup(
            UpdateGroupDto::fromRequest($request, $conversationId, auth()->id()),
        );

        return ApiResponse::success(
            data: new ConversationResource($conversation),
            message: 'Cập nhật nhóm chat thành công',
        );
    }

    public function addMembers(AddGroupMembersRequest $request, int $conversationId): JsonResponse
    {
        $conversation = $this->chatService->addGroupMembers(
            GroupMemberDto::fromRequest($request, $conversationId, auth()->id()),
        );

        return ApiResponse::success(
            data: new ConversationResource($conversation),
            message: 'Đã thêm thành viên vào nhóm',
        );
    }

    public function removeMember(int $conversationId, int $userId): JsonResponse
    {
        $conversation = $this->chatService->removeGroupMember($conversationId, auth()->id(), $userId);

        return ApiResponse::success(
            data: new ConversationResource($conversation),
            message: 'Đã xóa thành viên khỏi nhóm',
        );
    }

    public function transferAdmin(int $conversationId, int $userId): JsonResponse
    {
        $conversation = $this->chatService->transferGroupAdmin($conversationId, auth()->id(), $userId);

        return ApiResponse::success(
            data: new ConversationResource($conversation),
            message: 'Đã chuyển quyền admin nhóm',
        );
    }

    public function leave(int $conversationId): JsonResponse
    {
        $this->chatService->leaveGroup($conversationId, auth()->id());

        return ApiResponse::success(message: 'Đã rời nhóm');
    }

    public function getMembers(int $conversationId): JsonResponse
    {
        $members = $this->chatService->getGroupMembers($conversationId, auth()->id());

        return ApiResponse::success(
            data: GroupMemberResource::collection($members),
            message: 'Danh sách thành viên nhóm',
        );
    }
}
