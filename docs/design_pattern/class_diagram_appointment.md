# Sơ đồ Lớp Phân hệ: Đặt lịch hẹn (Appointment Booking Class Diagram)

Sơ đồ lớp chi tiết của phân hệ Đặt lịch hẹn bao gồm quản lý vòng đời trạng thái (State), đóng gói thao tác (Command) và chiến lược tính hạn xác nhận (Strategy).

---

## Sơ đồ Lớp (PlantUML)

```plantuml
@startuml
title Phân hệ Đặt lịch hẹn — Appointment Class Diagram

skinparam linetype ortho
skinparam classAttributeIconSize 0

interface AppointmentBookingService {
  +createBooking(CreateBookingDto dto): AppointmentBooking
  +updateBookingStatus(int bookingId, int posterId, string status, string note): AppointmentBooking
  +cancelBooking(int bookingId, int userId, string reason): AppointmentBooking
}

class AppointmentBookingServiceImpl {
  -AppointmentBookingRepository bookingRepository
  -BookingValidator validator
  -ConfirmBookingCommand confirmCommand
  -RejectBookingCommand rejectCommand
  -CancelBookingCommand cancelCommand
  +createBooking(CreateBookingDto dto): AppointmentBooking
}

class AppointmentBooking {
  +int id
  +int slot_id
  +int viewer_id
  +datetime meet_time
  +string status
  +string note
  +state(): BookingState
}

interface BookingState {
  +confirm(AppointmentBooking booking): void
  +reject(AppointmentBooking booking, string note): void
  +cancel(AppointmentBooking booking, BookingRole by, string reason): void
  +complete(AppointmentBooking booking): void
}

abstract class AbstractBookingState {
  +confirm(AppointmentBooking booking): void
  +reject(AppointmentBooking booking, string note): void
  +cancel(AppointmentBooking booking, BookingRole by, string reason): void
  +complete(AppointmentBooking booking): void
  #guardTwoHourRule(AppointmentBooking booking): void
  #applyCancel(AppointmentBooking booking, BookingRole by, string reason): void
}

class PendingState {
  +confirm(AppointmentBooking booking): void
  +reject(AppointmentBooking booking, string note): void
  +cancel(AppointmentBooking booking, BookingRole by, string reason): void
}

class ApprovedState {
  +cancel(AppointmentBooking booking, BookingRole by, string reason): void
  +complete(AppointmentBooking booking): void
}

class TerminalState

class ConfirmBookingCommand {
  +execute(AppointmentBooking booking): void
}

class RejectBookingCommand {
  +execute(AppointmentBooking booking, string note): void
}

class CancelBookingCommand {
  +execute(AppointmentBooking booking, BookingRole by, string reason): void
}

interface DeadlineStrategy {
  +deadlineFor(BookingContext ctx): CarbonImmutable
}

class DefaultDeadlineStrategy {
  +deadlineFor(BookingContext ctx): CarbonImmutable
}

class UrgentDeadlineStrategy {
  +deadlineFor(BookingContext ctx): CarbonImmutable
}

AppointmentBookingServiceImpl --> AppointmentBooking : "creates & updates"
AppointmentBookingServiceImpl --> ConfirmBookingCommand : "dispatches"
AppointmentBookingServiceImpl --> RejectBookingCommand : "dispatches"
AppointmentBookingServiceImpl --> CancelBookingCommand : "dispatches"
AppointmentBookingServiceImpl ..> DeadlineStrategy : "selects strategy"

ConfirmBookingCommand --> AppointmentBooking : "state() -> save()"
RejectBookingCommand --> AppointmentBooking : "state() -> save()"
CancelBookingCommand --> AppointmentBooking : "state() -> save()"

AppointmentBooking --> BookingState : "creates via state()"
BookingState <|.. AbstractBookingState
AbstractBookingState <|-- PendingState
AbstractBookingState <|-- ApprovedState
AbstractBookingState <|-- TerminalState

DeadlineStrategy <|.. DefaultDeadlineStrategy
DeadlineStrategy <|.. UrgentDeadlineStrategy

AppointmentService <|.. AppointmentBookingServiceImpl : "implements"
@enduml
```
