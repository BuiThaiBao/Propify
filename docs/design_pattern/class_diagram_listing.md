# Sơ đồ Lớp Phân hệ: Tin đăng & Kiểm duyệt (Listing & Moderation Class Diagram)

Sơ đồ lớp chi tiết của phân hệ Tạo tin đăng, cập nhật và Kiểm duyệt tin đăng bởi Admin (Template Method & State Pattern).

---

## Sơ đồ Lớp (PlantUML)

```plantuml
@startuml
title Phân hệ Tin đăng & Kiểm duyệt — Listing Class Diagram

skinparam linetype ortho
skinparam classAttributeIconSize 0

class ListingController {
  -CreateListingCommand createCommand
  +store(CreateListingRequest request): JsonResponse
}

class CreateListingCommand {
  -ListingRepository listingRepository
  -ListingStatusStateFactory statusStateFactory
  -ListingSubmissionValidationPipeline validationPipeline
  +handle(User user, CreateListingDto dto): Listing
}

class Listing {
  +int id
  +int property_id
  +int owner_id
  +string status
  +string title
  +datetime published_at
  +int approved_by
}

interface ListingStatusState {
  +value(): string
  +canTransitionTo(string nextStatus): bool
}

abstract class AbstractListingStatusState {
  +canTransitionTo(string nextStatus): bool
  {abstract} #allowedTransitions(): array
}

class DraftListingState {
  #allowedTransitions(): array
}
class PendingListingState {
  #allowedTransitions(): array
}
class ActiveListingState {
  #allowedTransitions(): array
}
class LockedListingState {
  #allowedTransitions(): array
}
class RejectedListingState {
  #allowedTransitions(): array
}
class UnlistedListingState {
  #allowedTransitions(): array
}

class ListingStatusStateFactory {
  +make(string status): ListingStatusState
  +initialForSave(bool saveAsDraft): ListingStatusState
  +assertCanTransition(string current, string next): void
}

abstract class AbstractListingModerationCommand {
  -ListingStatusStateFactory statusStateFactory
  +execute(int listingId, ModerationContext ctx): Listing
  {abstract} #targetStatus(): string
  {abstract} #mutate(Listing listing, ModerationContext ctx): void
  #validate(ModerationContext ctx): void
}

class ApproveListingCommand {
  #targetStatus(): string
  #mutate(Listing listing, ModerationContext ctx): void
}

class RejectListingCommand {
  #targetStatus(): string
  #mutate(Listing listing, ModerationContext ctx): void
  #validate(ModerationContext ctx): void
}

class LockListingCommand {
  #targetStatus(): string
  #mutate(Listing listing, ModerationContext ctx): void
}

ListingController --> CreateListingCommand
CreateListingCommand --> Listing : "creates"
CreateListingCommand --> ListingStatusStateFactory : "gets initial state"

ListingStatusState <|.. AbstractListingStatusState
AbstractListingStatusState <|-- DraftListingState
AbstractListingStatusState <|-- PendingListingState
AbstractListingStatusState <|-- ActiveListingState
AbstractListingStatusState <|-- LockedListingState
AbstractListingStatusState <|-- RejectedListingState
AbstractListingStatusState <|-- UnlistedListingState

ListingStatusStateFactory ..> ListingStatusState : "instantiates"

AbstractListingModerationCommand --> ListingStatusStateFactory : "asserts transition"
AbstractListingModerationCommand --> Listing : "loads, mutates & saves"
AbstractListingModerationCommand <|-- ApproveListingCommand
AbstractListingModerationCommand <|-- RejectListingCommand
AbstractListingModerationCommand <|-- LockListingCommand
@enduml
```
