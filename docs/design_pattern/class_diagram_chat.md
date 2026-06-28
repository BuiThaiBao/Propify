# Sơ đồ Lớp Phân hệ: Tin nhắn Realtime (Chat Subsystem Class Diagram)

Sơ đồ lớp chi tiết của phân hệ Nhắn tin trò chuyện realtime và WebSocket Broadcasting (Facade & Observer Pattern).

---

## Sơ đồ Lớp (PlantUML)

```plantuml
@startuml
title Phân hệ Tin nhắn Realtime — Chat Class Diagram

skinparam linetype ortho
skinparam classAttributeIconSize 0

class ChatController {
  -ChatService chatService
  +sendMessage(SendMessageRequest request): JsonResponse
  +getMessages(int conversationId): JsonResponse
}

interface ChatService {
  +getOrCreateConversation(GetOrCreateConversationDto dto): Conversation
  +getConversations(int userId): Collection
  +getMessages(int conversationId, int userId, ?string cursor): CursorPaginator
  +sendMessage(SendMessageDto dto): Message
}

class ChatServiceImpl {
  -ChatRepository chatRepository
  +sendMessage(SendMessageDto dto): Message
  -assertParticipant(int conversationId, int userId): void
}

class ChatRepository {
  +findConversation(int currentUserId, int otherUserId, int listingId): ?Conversation
  +createConversation(int currentUserId, int otherUserId, int listingId): Conversation
  +createMessage(array data): Message
  +getMessages(int conversationId, ?string cursor): CursorPaginator
}

class MessageSent {
  <<Event>>
  +Message message
  +broadcastOn(): Channel
}

class LaravelBroadcasting {
  <<Broadcast Engine>>
  +broadcast(MessageSent event): void
}

ChatController --> ChatService : "calls Facade"
ChatService <|.. ChatServiceImpl : "implements"
ChatServiceImpl --> ChatRepository : "orchestrates DB operations"
ChatServiceImpl ..> MessageSent : "dispatches event"
MessageSent --> LaravelBroadcasting : "broadcasts via WebSockets"
@enduml
```
