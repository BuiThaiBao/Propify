**PHÂN TÍCH ÁP DỤNG DESIGN PATTERN THEO CHỨC NĂNG**

**Đề tài: Website đăng tin bất động sản**

_Trình bày theo từng chức năng: mỗi chức năng áp dụng pattern nào, áp dụng ra sao và lợi ích thiết kế_

Tài liệu này được tổ chức theo hướng dễ đưa vào báo cáo môn Phân tích thiết kế hệ thống: không liệt kê pattern riêng lẻ, mà phân tích trực tiếp từng chức năng của website bất động sản.

**Ký hiệu nhóm pattern:**

- \[C\] Creational Patterns: Factory Method, Abstract Factory, Builder, Prototype, Singleton.
- \[S\] Structural Patterns: Adapter, Facade, Decorator, Proxy, Composite, Bridge, Flyweight.
- \[B\] Behavioral Patterns: Strategy, State, Observer, Command, Chain of Responsibility, Template Method, Mediator, Memento, Iterator, Visitor, Interpreter, Specification.

Lưu ý: Specification không thuộc GoF gốc nhưng rất phổ biến trong Clean Architecture, đặc biệt cho tìm kiếm và lọc dữ liệu.

# 1\. Định hướng kiến trúc tổng thể

Khi áp dụng Clean Architecture, các Design Pattern nên được đặt đúng tầng để tránh controller hoặc model bị phình to:

- Domain Layer: Entity, Value Object, State, Specification, Domain Event.
- Application Layer: Use Case, Command, Strategy, Chain of Responsibility, Observer/Event Publisher.
- Interface Adapter Layer: Controller, Presenter, DTO, Mapper, Facade.
- Infrastructure Layer: Repository implementation, Payment Adapter, Google OAuth Adapter, Mail/SMS/Map Adapter.

Nguyên tắc SOLID quan trọng nhất: Use Case phụ thuộc vào interface, không phụ thuộc trực tiếp vào Google, VNPAY, database hoặc dịch vụ gửi mail.

| **Chức năng**                | **Pattern nên áp dụng**                                                                                                         | **Cách áp dụng trong hệ thống**                                                                                                                                                                                                                                                                    | **Lợi ích khi thiết kế**                                                                                                     |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Đăng ký tài khoản            | \[C\] Builder <br>\[B\] Chain of Responsibility <br>\[B\] Observer <br>\[S\] Adapter <br>\[B\] Command                          | Dùng UserBuilder tạo User từ nhiều trường; chuỗi validate kiểm tra email, mật khẩu, số điện thoại, trùng tài khoản; sau khi đăng ký phát sự kiện UserRegistered để gửi email xác thực/ghi audit log; MailAdapter che giấu dịch vụ gửi email; RegisterUserCommand đóng gói thao tác đăng ký.        | Dễ mở rộng rule validate; dễ thay đổi dịch vụ email; tách nghiệp vụ đăng ký khỏi controller; thuận lợi cho logging và queue. |
| Đăng nhập tài khoản / Google | \[B\] Strategy <br>\[C\] Factory Method <br>\[S\] Adapter <br>\[B\] Chain of Responsibility <br>\[S\] Facade <br>\[B\] Observer | AuthStrategy xử lý đăng nhập bằng email/password, Google, Facebook; AuthProviderFactory chọn provider; GoogleOAuthAdapter chuyển dữ liệu Google về User nội bộ; chuỗi kiểm tra trạng thái tài khoản, mật khẩu, 2FA; AuthFacade cung cấp hàm login đơn giản; phát LoginSucceeded/LoginFailed event. | Thêm phương thức đăng nhập mới không sửa logic cũ; code tuân thủ Open/Closed; dễ test từng provider; controller gọn.         |
| Xem thông tin tài khoản      | \[S\] Proxy <br>\[S\] Facade <br>\[B\] Strategy                                                                                 | AccountAccessProxy kiểm tra người xem có phải chủ tài khoản hoặc admin; AccountFacade gom logic lấy profile, thống kê tin đăng, lịch sử gói; VisibilityStrategy quyết định trường nào được hiển thị cho user thường/admin.                                                                         | Bảo vệ dữ liệu cá nhân; tránh lặp logic phân quyền; dễ thay đổi chính sách hiển thị.                                         |
| Xem chi tiết tài khoản       | \[S\] Proxy <br>\[S\] Facade <br>\[B\] Command                                                                                  | AdminAccountProxy kiểm tra quyền admin trước khi xem chi tiết; AccountDetailFacade gom thông tin user, tin đăng, giao dịch, lịch hẹn; ViewAccountDetailCommand giúp ghi log hành động xem dữ liệu nhạy cảm.                                                                                        | Phù hợp chức năng admin; tăng bảo mật; dễ audit.                                                                             |
| Chỉnh sửa thông tin cá nhân  | \[B\] Command <br>\[B\] Memento <br>\[B\] Chain of Responsibility <br>\[B\] Observer                                            | UpdateProfileCommand đóng gói thao tác sửa; ProfileMemento lưu bản cũ; chuỗi validate kiểm tra dữ liệu; sau khi cập nhật phát ProfileUpdated event.                                                                                                                                                | Có thể hoàn tác hoặc xem lịch sử thay đổi; dễ kiểm soát validate; dễ gửi thông báo khi đổi thông tin quan trọng.             |
| Đổi mật khẩu                 | \[B\] Chain of Responsibility <br>\[B\] Strategy <br>\[B\] Command <br>\[B\] Observer                                           | Chuỗi handler kiểm tra mật khẩu cũ, độ mạnh mật khẩu, xác nhận lại; PasswordHashStrategy cho bcrypt/argon2; ChangePasswordCommand đóng gói thao tác; phát PasswordChanged event để gửi email cảnh báo.                                                                                             | An toàn hơn; thay đổi thuật toán hash dễ dàng; quy trình rõ ràng.                                                            |
| Quên mật khẩu                | \[C\] Factory Method <br>\[S\] Adapter <br>\[B\] Command <br>\[B\] Observer <br>\[B\] State                                     | ResetTokenFactory tạo token; Email/SMS Adapter gửi liên kết reset; RequestPasswordResetCommand và ResetPasswordCommand đóng gói thao tác; token có state Pending/Used/Expired.                                                                                                                     | Dễ đổi kênh gửi mã; kiểm soát vòng đời token; giảm rủi ro bảo mật.                                                           |
| Tìm kiếm tài khoản           | \[B\] Specification <br>\[B\] Strategy <br>\[B\] Iterator                                                                       | Tạo các specification như RoleSpec, StatusSpec, CreatedDateSpec; SearchStrategy chọn kiểu tìm theo tên/email/số điện thoại; Iterator/Pagination duyệt kết quả theo trang.                                                                                                                          | Bộ lọc linh hoạt; tránh viết nhiều câu query trùng lặp; dễ phân trang.                                                       |
| Lọc tài khoản                | \[B\] Specification <br>\[S\] Composite <br>\[B\] Strategy                                                                      | Ghép điều kiện lọc bằng AND/OR: trạng thái, vai trò, ngày tạo, số lượng tin; Composite giúp tạo bộ lọc phức hợp; Strategy quyết định sắp xếp.                                                                                                                                                      | Dễ bổ sung điều kiện lọc mới; giao diện lọc có thể mở rộng.                                                                  |
| Khóa tài khoản               | \[B\] Command <br>\[B\] State <br>\[S\] Proxy <br>\[B\] Observer                                                                | LockAccountCommand thực hiện khóa; AccountState chuyển Active -> Locked; AdminPermissionProxy kiểm tra quyền; Observer gửi thông báo, ghi audit log, hủy phiên đăng nhập.                                                                                                                          | Quy trình admin rõ ràng; kiểm soát trạng thái hợp lệ; bảo mật tốt hơn.                                                       |
| Mở khóa tài khoản            | \[B\] Command <br>\[B\] State <br>\[S\] Proxy <br>\[B\] Observer                                                                | UnlockAccountCommand chuyển trạng thái Locked -> Active; Proxy kiểm tra quyền; Observer gửi thông báo và ghi log.                                                                                                                                                                                  | Không cho mở khóa sai quyền; dễ theo dõi lịch sử xử lý tài khoản.                                                            |

# 2\. Phân tích Design Pattern theo từng chức năng

## 2.1. Nhóm chức năng tài khoản và xác thực

## 2.2. Nhóm chức năng tin đăng bất động sản

| **Chức năng**          | **Pattern nên áp dụng**                                                                                                 | **Cách áp dụng trong hệ thống**                                                                                                                                                                                                                                                                               | **Lợi ích khi thiết kế**                                                                        |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| Trang chủ              | \[S\] Facade <br>\[B\] Strategy <br>\[S\] Decorator <br>\[B\] Observer                                                  | HomeFacade gom dữ liệu banner, tin nổi bật, tin mới, thống kê; RankingStrategy quyết định cách xếp tin; Decorator làm nổi bật tin VIP/đã xác thực; Observer cập nhật cache khi có tin mới.                                                                                                                    | Trang chủ gọn, dễ thay đổi thuật toán hiển thị; tin VIP dễ được mở rộng đặc quyền.              |
| Tạo tin đăng           | \[C\] Builder <br>\[B\] Chain of Responsibility <br>\[B\] State <br>\[B\] Observer <br>\[S\] Facade <br>\[C\] Prototype | ListingBuilder tạo Listing từ nhiều trường: tiêu đề, giá, địa chỉ, ảnh, tiện ích; chuỗi validate kiểm tra ảnh, nội dung cấm, giá, vị trí; trạng thái ban đầu Draft/Pending; phát ListingCreated event; ListingFacade gom upload ảnh, lưu DB, gán tiện ích; Prototype cho phép sao chép tin cũ để tạo tin mới. | Phù hợp object nhiều thuộc tính; dễ mở rộng rule kiểm duyệt; hỗ trợ workflow duyệt tin rõ ràng. |
| Xem chi tiết tin đăng  | \[S\] Proxy <br>\[S\] Facade <br>\[S\] Decorator <br>\[B\] Observer                                                     | ListingViewProxy kiểm tra tin có bị khóa/gỡ/hết hạn không; ListingDetailFacade lấy thông tin người đăng, tiện ích, ảnh, gói tin; Decorator hiển thị badge VIP/xác thực; Observer tăng lượt xem hoặc ghi lịch sử xem.                                                                                          | Bảo vệ tin không hợp lệ; tách logic hiển thị chi tiết; hỗ trợ thống kê lượt xem.                |
| Xem danh sách tin đăng | \[B\] Iterator <br>\[B\] Strategy <br>\[B\] Specification <br>\[S\] Decorator                                           | Iterator/Pagination phân trang; SortStrategy sắp xếp mới nhất, giá tăng, gần tôi, ưu tiên VIP; Specification lọc theo trạng thái đã duyệt; Decorator thêm thông tin hiển thị cho tin VIP.                                                                                                                     | Danh sách dễ phân trang, dễ sắp xếp; không làm query quá rối.                                   |
| Chỉnh sửa tin đăng     | \[B\] Command <br>\[B\] Memento <br>\[B\] Chain of Responsibility <br>\[B\] State <br>\[B\] Observer                    | UpdateListingCommand đóng gói thao tác sửa; ListingMemento lưu phiên bản cũ; chuỗi validate lại nội dung; tùy quy định có thể chuyển Approved -> PendingReview; phát ListingUpdated event.                                                                                                                    | Có lịch sử chỉnh sửa; kiểm soát nội dung sau sửa; dễ audit.                                     |
| Tìm kiếm tin đăng      | \[B\] Strategy <br>\[B\] Specification <br>\[C\] Builder <br>\[B\] Interpreter <br>\[B\] Iterator                       | SearchStrategy chọn tìm full-text, tìm theo vị trí, tìm theo giá; SearchCriteriaBuilder tạo tiêu chí; Specification ghép điều kiện; Interpreter dùng khi có cú pháp nâng cao như price &lt; 2 tỷ AND area &gt; 50; Iterator phân trang kết quả.                                                               | Tìm kiếm mạnh, dễ mở rộng; tách thuật toán tìm kiếm khỏi controller.                            |
| Lọc tin đăng           | \[B\] Specification <br>\[S\] Composite <br>\[B\] Strategy <br>\[C\] Builder                                            | Các spec: PriceRangeSpec, AreaSpec, LocationSpec, AmenitySpec, VerifiedSpec; Composite ghép nhiều filter; FilterBuilder tạo object filter; Strategy quyết định sort sau khi lọc.                                                                                                                              | Bộ lọc có thể thêm/bớt không ảnh hưởng các phần khác; phù hợp màn hình lọc nâng cao.            |
| Bộ lọc tin đăng        | \[S\] Composite <br>\[B\] Specification <br>\[C\] Builder <br>\[B\] Interpreter                                         | Composite biểu diễn cây điều kiện lọc gồm nhóm giá, diện tích, địa điểm, tiện ích; Specification chuyển thành điều kiện query; Builder dựng bộ lọc từ UI; Interpreter parse điều kiện nâng cao nếu có.                                                                                                        | Tái sử dụng cùng bộ lọc cho list view, map view, dashboard; dễ mở rộng điều kiện mới.           |
| Gỡ tin đăng            | \[B\] Command <br>\[B\] State <br>\[B\] Observer <br>\[S\] Proxy                                                        | RemoveListingCommand chuyển trạng thái Approved/Pending -> Removed; Proxy kiểm tra chỉ chủ tin hoặc admin được gỡ; Observer thông báo, ghi audit log, xóa khỏi index tìm kiếm.                                                                                                                                | Không xóa cứng dữ liệu; dễ khôi phục hoặc kiểm tra lịch sử.                                     |
| Cảnh báo tin đăng      | \[B\] Command <br>\[B\] Chain of Responsibility <br>\[B\] State <br>\[B\] Observer                                      | ReportListingCommand ghi nhận cảnh báo; chain kiểm tra lý do report, trùng report, mức độ vi phạm; ListingState có Warning/UnderReview; Observer thông báo admin và người đăng.                                                                                                                               | Quy trình xử lý vi phạm rõ ràng; tránh spam report; phù hợp nghiệp vụ kiểm duyệt.               |
| Xác thực tin đăng      | \[B\] Strategy <br>\[B\] Chain of Responsibility <br>\[S\] Adapter <br>\[B\] State <br>\[B\] Observer                   | VerifyStrategy gồm manual verify, auto verify, AI verify; chain kiểm tra giấy tờ, vị trí, hình ảnh, trùng lặp; Adapter tích hợp dịch vụ OCR/AI nếu có; trạng thái chuyển Verified/RejectedVerification.                                                                                                       | Có thể thay đổi cách xác thực; dễ thêm bên thứ ba; trạng thái minh bạch.                        |
| Tin đăng yêu thích     | \[B\] Command <br>\[B\] Observer <br>\[S\] Proxy                                                                        | AddFavoriteCommand/RemoveFavoriteCommand; Proxy kiểm tra user đã đăng nhập; Observer cập nhật thống kê yêu thích và gợi ý cá nhân hóa.                                                                                                                                                                        | Dễ log hành vi người dùng; hỗ trợ recommendation sau này.                                       |
| Từ chối tin đăng       | \[B\] Command <br>\[B\] State <br>\[B\] Observer <br>\[B\] Template Method                                              | RejectListingCommand chuyển Pending -> Rejected; Template Method chuẩn hóa quy trình kiểm duyệt: validate, quyết định, ghi lý do, thông báo; Observer gửi lý do từ chối cho người đăng.                                                                                                                       | Quy trình duyệt/từ chối thống nhất; dễ bổ sung bước kiểm tra mới.                               |
| Duyệt tin đăng         | \[B\] Command <br>\[B\] State <br>\[B\] Observer <br>\[B\] Chain of Responsibility <br>\[B\] Template Method            | ApproveListingCommand chuyển Pending -> Approved; trước khi duyệt chạy chain kiểm tra dữ liệu, ảnh, giá, pháp lý; Template Method định nghĩa khung duyệt; Observer cập nhật search index, dashboard, gửi thông báo.                                                                                           | Giảm lỗi duyệt sai; workflow rõ; dễ mở rộng kiểm duyệt tự động.                                 |
| Khóa tin đăng          | \[B\] Command <br>\[B\] State <br>\[S\] Proxy <br>\[B\] Observer                                                        | LockListingCommand chuyển Approved -> Locked; Proxy kiểm tra quyền admin; Observer thông báo chủ tin và loại tin khỏi kết quả tìm kiếm.                                                                                                                                                                       | Đảm bảo chỉ admin có quyền khóa; dễ theo dõi lý do khóa.                                        |

## 2.3. Nhóm chức năng lịch hẹn

| **Chức năng**          | **Pattern nên áp dụng**                                                                | **Cách áp dụng trong hệ thống**                                                                                                                                                                                                                                  | **Lợi ích khi thiết kế**                                                          |
| ---------------------- | -------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| Đặt lịch hẹn           | \[C\] Builder <br>\[B\] State <br>\[B\] Mediator <br>\[B\] Observer <br>\[B\] Strategy | AppointmentBuilder tạo lịch từ người xem, người đăng, tin đăng, thời gian, địa điểm; state ban đầu Pending; AppointmentMediator điều phối giữa người đặt, người đăng, hệ thống thông báo; AvailabilityStrategy kiểm tra khung giờ trống; Observer gửi thông báo. | Tách logic đặt lịch khỏi chat/user/listing; dễ kiểm soát trạng thái và thông báo. |
| Xem thông tin lịch hẹn | \[S\] Proxy <br>\[S\] Facade                                                           | AppointmentAccessProxy kiểm tra chỉ người đặt, người đăng hoặc admin xem được; AppointmentFacade gom dữ liệu tin đăng, người tham gia, trạng thái, lịch sử thay đổi.                                                                                             | Bảo mật thông tin lịch; controller đơn giản.                                      |
| Hủy lịch hẹn           | \[B\] Command <br>\[B\] State <br>\[B\] Observer                                       | CancelAppointmentCommand chuyển Pending/Confirmed -> Cancelled; State kiểm tra có được hủy hay không; Observer thông báo cho bên còn lại.                                                                                                                        | Tránh hủy sai trạng thái; lịch sử xử lý rõ ràng.                                  |
| Xác nhận lịch hẹn      | \[B\] Command <br>\[B\] State <br>\[B\] Observer <br>\[B\] Mediator                    | ConfirmAppointmentCommand chuyển Pending -> Confirmed; Mediator điều phối người đăng/người đặt; Observer gửi thông báo và cập nhật lịch.                                                                                                                         | Quy trình xác nhận thống nhất; giảm phụ thuộc trực tiếp giữa các đối tượng.       |
| Từ chối lịch hẹn       | \[B\] Command <br>\[B\] State <br>\[B\] Observer <br>\[B\] Mediator                    | RejectAppointmentCommand chuyển Pending -> Rejected; có lý do từ chối; Observer gửi thông báo cho người đặt.                                                                                                                                                     | Dễ lưu lý do; dễ thống kê tỷ lệ lịch bị từ chối.                                  |

## 2.4. Nhóm chức năng tiện ích

| **Chức năng**             | **Pattern nên áp dụng**                                                        | **Cách áp dụng trong hệ thống**                                                                                                                                                                              | **Lợi ích khi thiết kế**                                                       |
| ------------------------- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------ |
| Thêm tiện ích             | \[C\] Factory Method <br>\[S\] Composite <br>\[S\] Flyweight <br>\[B\] Command | AmenityFactory tạo tiện ích theo nhóm: nội thất, an ninh, giao thông; Composite cho phép nhóm tiện ích chứa tiện ích con; Flyweight tái sử dụng icon/tên chuẩn; CreateAmenityCommand ghi log thao tác admin. | Quản lý tiện ích có cấu trúc; tiết kiệm dữ liệu lặp; dễ mở rộng nhóm tiện ích. |
| Sửa tiện ích              | \[B\] Command <br>\[B\] Memento <br>\[S\] Flyweight                            | UpdateAmenityCommand đóng gói thao tác sửa; AmenityMemento lưu bản trước khi sửa; Flyweight giữ dữ liệu dùng chung như icon, label.                                                                          | Có thể khôi phục tiện ích; tránh sửa lan man dữ liệu dùng chung.               |
| Cài đặt hiển thị tiện ích | \[S\] Composite <br>\[B\] Strategy <br>\[S\] Flyweight                         | Composite tạo cấu trúc nhóm tiện ích hiển thị theo loại nhà đất; DisplayStrategy quyết định hiển thị theo căn hộ/nhà riêng/văn phòng; Flyweight dùng lại icon và mô tả.                                      | UI tiện ích linh hoạt; không cần nhân bản dữ liệu.                             |

## 2.5. Nhóm chức năng gói tin

| **Chức năng**                  | **Pattern nên áp dụng**                                                                                                            | **Cách áp dụng trong hệ thống**                                                                                                                                                                                                                                                                                           | **Lợi ích khi thiết kế**                                                                                                          |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Thêm gói tin                   | \[C\] Factory Method <br>\[C\] Builder <br>\[C\] Prototype <br>\[B\] Strategy                                                      | PackageFactory tạo gói Basic/VIP/Diamond; PackageBuilder tạo gói có nhiều trường: giá, thời hạn, priority, multiplier, số ảnh; Prototype sao chép gói mẫu; PricingStrategy tính giá theo tháng/tuần/khuyến mãi.                                                                                                           | Dễ thêm gói mới; không hard-code toàn bộ logic gói trong service.                                                                 |
| Khóa/Kích hoạt gói tin         | \[B\] Command <br>\[B\] State <br>\[S\] Proxy <br>\[B\] Observer                                                                   | ActivatePackageCommand/LockPackageCommand; PackageState chuyển Active/Inactive/Locked; Proxy kiểm tra quyền admin; Observer cập nhật cache và thông báo cho các tin đang dùng gói nếu cần.                                                                                                                                | Quản lý vòng đời gói rõ ràng; tránh user mua gói đã khóa.                                                                         |
| Chỉnh sửa gói tin              | \[B\] Command <br>\[B\] Memento <br>\[B\] Observer <br>\[B\] Strategy                                                              | UpdatePackageCommand cập nhật giá/quyền lợi; PackageMemento lưu cấu hình cũ; Observer cập nhật cache; PricingStrategy giữ logic tính giá riêng.                                                                                                                                                                           | Theo dõi lịch sử thay đổi giá/gói; giảm rủi ro ảnh hưởng các giao dịch cũ.                                                        |
| Tìm kiếm gói tin               | \[B\] Specification <br>\[B\] Strategy <br>\[B\] Iterator                                                                          | PackageNameSpec, StatusSpec, PriceRangeSpec; SearchStrategy chọn tìm theo tên/trạng thái/giá; Iterator phân trang.                                                                                                                                                                                                        | Dễ lọc gói khi admin quản lý nhiều loại gói.                                                                                      |
| Lọc gói tin                    | \[B\] Specification <br>\[S\] Composite <br>\[B\] Strategy                                                                         | Ghép điều kiện lọc theo trạng thái, giá, thời hạn, quyền lợi; Strategy sắp xếp theo giá/thời hạn/độ ưu tiên.                                                                                                                                                                                                              | Bộ lọc admin mở rộng tốt.                                                                                                         |
| Xem thông tin gói tin          | \[S\] Facade <br>\[S\] Decorator                                                                                                   | PackageFacade gom thông tin giá, quyền lợi, số ngày, trạng thái; Decorator biểu diễn quyền lợi cộng thêm như ghim đầu, nổi bật, ưu tiên tìm kiếm.                                                                                                                                                                         | Màn hình hiển thị gọn; quyền lợi gói dễ mở rộng.                                                                                  |
| Xem chi tiết thông tin gói tin | \[S\] Facade <br>\[S\] Proxy <br>\[S\] Decorator                                                                                   | PackageDetailFacade lấy thêm lịch sử sửa, danh sách tin đang dùng gói; Proxy phân quyền phần thông tin nội bộ; Decorator hiển thị các quyền lợi cộng thêm.                                                                                                                                                                | Tách dữ liệu công khai và dữ liệu admin; dễ bảo trì.                                                                              |
| Nâng cấp gói tin               | \[S\] Decorator <br>\[B\] Strategy <br>\[C\] Factory Method <br>\[S\] Adapter <br>\[B\] State <br>\[B\] Observer <br>\[B\] Command | UpgradePackageCommand khởi tạo yêu cầu nâng cấp; PricingStrategy tính số tiền; PaymentGatewayFactory tạo VNPAY/Momo; PaymentAdapter tích hợp cổng thanh toán; sau khi Paid, ListingDecorator thêm VIP/Highlight/TopSearch/Verified badge; ListingState/PackageState cập nhật; Observer gửi thông báo và cập nhật ranking. | Đây là chức năng nên trình bày kỹ trong báo cáo vì kết hợp nhiều pattern; thêm quyền lợi mới chỉ cần thêm decorator/strategy mới. |

## 2.6. Nhóm chức năng thanh toán, giao dịch, doanh thu và báo cáo

| **Chức năng**                  | **Pattern nên áp dụng**                                                                                                                                   | **Cách áp dụng trong hệ thống**                                                                                                                                                                                                                                                                            | **Lợi ích khi thiết kế**                                                                |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| Thanh toán                     | \[C\] Abstract Factory <br>\[C\] Factory Method <br>\[S\] Adapter <br>\[B\] Strategy <br>\[B\] State <br>\[B\] Chain of Responsibility <br>\[B\] Observer | PaymentProviderFactory tạo gateway, callback handler, refund service theo VNPAY/Momo; Adapter che giấu API bên thứ ba; PaymentStrategy xử lý từng phương thức; PaymentState Pending/Paid/Failed/Cancelled; callback đi qua chain verify signature, amount, order; Observer kích hoạt gói và gửi thông báo. | Tích hợp nhiều cổng thanh toán mà không sửa use case chính; quy trình callback an toàn. |
| Hủy thanh toán                 | \[B\] Command <br>\[B\] State <br>\[S\] Adapter <br>\[B\] Observer                                                                                        | CancelPaymentCommand chuyển Pending -> Cancelled hoặc gọi RefundAdapter nếu đã thanh toán; State kiểm soát trường hợp hợp lệ; Observer thông báo user và ghi log giao dịch.                                                                                                                                | Giảm lỗi hủy giao dịch sai trạng thái; dễ tích hợp hoàn tiền.                           |
| Tìm kiếm lịch sử giao dịch     | \[B\] Specification <br>\[B\] Strategy <br>\[B\] Iterator                                                                                                 | TransactionSpec lọc theo mã giao dịch, user, trạng thái, thời gian; SearchStrategy tìm theo mã hoặc nội dung; Iterator phân trang.                                                                                                                                                                         | Admin tra cứu nhanh, query dễ mở rộng.                                                  |
| Lọc lịch sử giao dịch          | \[B\] Specification <br>\[S\] Composite <br>\[B\] Strategy                                                                                                | Composite ghép điều kiện: ngày, cổng thanh toán, số tiền, trạng thái; Strategy sắp xếp mới nhất/số tiền cao nhất.                                                                                                                                                                                          | Phù hợp màn hình quản trị giao dịch.                                                    |
| Xem chi tiết lịch sử giao dịch | \[S\] Facade <br>\[S\] Proxy <br>\[B\] State                                                                                                              | TransactionDetailFacade gom payment, package, listing, user; Proxy kiểm tra quyền xem; State hiển thị trạng thái hiện tại và các hành động hợp lệ.                                                                                                                                                         | Bảo mật dữ liệu giao dịch; dễ hiển thị chi tiết đầy đủ.                                 |
| Xem doanh thu                  | \[S\] Facade <br>\[B\] Strategy <br>\[B\] Visitor <br>\[B\] Observer                                                                                      | RevenueFacade gom dữ liệu giao dịch; RevenueStrategy tính theo ngày/tháng/quý/năm; Visitor duyệt qua transaction/package/listing để tổng hợp; Observer cập nhật thống kê khi payment thành công.                                                                                                           | Dashboard doanh thu linh hoạt; dễ thêm kiểu thống kê mới.                               |
| Xuất báo cáo                   | \[C\] Factory Method <br>\[B\] Template Method <br>\[B\] Strategy <br>\[B\] Visitor <br>\[C\] Builder                                                     | ReportExporterFactory tạo PDF/Excel/CSV exporter; Template Method định nghĩa khung: lấy dữ liệu, format, sinh file; Strategy chọn loại báo cáo; Visitor gom dữ liệu từ nhiều entity; ReportBuilder dựng cấu trúc báo cáo.                                                                                  | Thêm định dạng xuất mới dễ dàng; quy trình xuất báo cáo thống nhất.                     |

## 2.7. Nhóm chức năng bản đồ, chat và dashboard

| **Chức năng**               | **Pattern nên áp dụng**                                                                         | **Cách áp dụng trong hệ thống**                                                                                                                                                                                                  | **Lợi ích khi thiết kế**                                                            |
| --------------------------- | ----------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Map view danh sách tin đăng | \[S\] Adapter <br>\[B\] Strategy <br>\[S\] Flyweight <br>\[B\] Specification <br>\[B\] Iterator | MapAdapter tích hợp Google Maps/OpenStreetMap; DistanceStrategy tính gần tôi, theo bán kính; Flyweight tái sử dụng marker/icon; Specification lọc tin hiển thị; Iterator phân trang hoặc load theo vùng bản đồ.                  | Dễ đổi nhà cung cấp bản đồ; hiệu năng tốt khi nhiều marker; tái sử dụng bộ lọc tin. |
| Chat với người đăng         | \[B\] Mediator <br>\[B\] Observer <br>\[S\] Proxy <br>\[S\] Adapter <br>\[B\] State             | ChatMediator điều phối người hỏi, người đăng và phòng chat; Observer gửi realtime notification; Proxy kiểm tra quyền chat, chặn user bị khóa; Adapter tích hợp WebSocket/Firebase/Pusher; MessageState Sent/Read/Deleted.        | Giảm phụ thuộc giữa user với user; dễ đổi công nghệ realtime; kiểm soát quyền chat. |
| Dashboard                   | \[S\] Facade <br>\[B\] Strategy <br>\[B\] Observer <br>\[B\] Visitor <br>\[S\] Proxy            | DashboardFacade gom dữ liệu user, listing, payment, appointment; Strategy chọn khoảng thời gian thống kê; Observer cập nhật số liệu khi có sự kiện; Visitor tổng hợp dữ liệu từ nhiều entity; Proxy giới hạn admin mới xem được. | Màn hình dashboard đơn giản; dễ thêm widget thống kê mới; bảo mật dữ liệu quản trị. |

# 3\. Các luồng mẫu nên trình bày kỹ trong báo cáo

## Luồng mẫu 1: Đăng nhập tài khoản / Google

Chức năng đăng nhập là ví dụ tốt để trình bày cách kết hợp Creational, Structural và Behavioral Pattern.

**Pattern chính:**

- Factory Method: chọn provider đăng nhập.
- Strategy: thay đổi thuật toán đăng nhập theo email, Google, Facebook.
- Adapter: chuyển API Google về interface nội bộ.
- Chain of Responsibility: kiểm tra tuần tự các điều kiện đăng nhập.
- Observer: xử lý log/thông báo sau khi đăng nhập.

**Luồng xử lý gợi ý:**

- Controller gọi AuthFacade.login(request).
- AuthProviderFactory chọn EmailPasswordAuthStrategy hoặc GoogleAuthStrategy.
- Nếu đăng nhập Google, GoogleOAuthAdapter gọi API Google và chuẩn hóa dữ liệu về User nội bộ.
- LoginValidationChain kiểm tra tài khoản tồn tại, trạng thái Locked, mật khẩu hoặc token hợp lệ.
- Nếu thành công, phát LoginSucceededEvent để ghi log, cập nhật last_login_at, gửi cảnh báo nếu đăng nhập thiết bị lạ.

## Luồng mẫu 2: Nâng cấp gói tin

Đây là chức năng rất mạnh để đưa vào báo cáo vì liên quan đến gói tin, thanh toán, cập nhật trạng thái và hiển thị tin nổi bật.

**Pattern chính:**

- Command: đóng gói thao tác nâng cấp.
- Strategy: tính giá và khuyến mãi.
- Factory Method + Adapter: chọn và tích hợp cổng thanh toán.
- State: quản lý trạng thái payment/package/listing.
- Decorator: gắn quyền lợi mới cho tin đăng.
- Observer: thông báo và cập nhật thống kê.

**Luồng xử lý gợi ý:**

- User chọn gói muốn nâng cấp cho một tin đăng.
- UpgradePackageCommand tạo yêu cầu nâng cấp.
- PricingStrategy tính số tiền dựa trên gói, thời hạn, khuyến mãi.
- PaymentGatewayFactory tạo VnpayPaymentAdapter hoặc MomoPaymentAdapter.
- PaymentState chuyển sang Pending và user được chuyển sang cổng thanh toán.
- Khi callback thành công, PaymentCallbackChain xác thực chữ ký, số tiền, mã giao dịch.
- ListingDecorator thêm các quyền lợi như VIP, Highlight, TopSearch, VerifiedBadge.
- Observer cập nhật ranking, gửi thông báo, ghi audit log.

## Luồng mẫu 3: Tạo tin đăng

Tạo tin đăng có nhiều dữ liệu và nhiều bước kiểm tra, vì vậy rất phù hợp với Builder, Chain of Responsibility và State.

**Pattern chính:**

- Builder: dựng object Listing nhiều thuộc tính.
- Chain of Responsibility: validate qua nhiều bước.
- Facade: che giấu quy trình tạo tin phức tạp.
- State: quản lý vòng đời tin đăng.
- Observer: thông báo và cập nhật dashboard.

**Luồng xử lý gợi ý:**

- ListingBuilder nhận dữ liệu từ form: tiêu đề, giá, diện tích, địa chỉ, ảnh, tiện ích.
- ListingValidationChain kiểm tra trường bắt buộc, định dạng giá, ảnh, từ cấm, trùng lặp.
- ListingFacade xử lý upload ảnh, lưu tiện ích, lưu tin đăng.
- ListingState đặt ban đầu là Pending để chờ duyệt.
- Observer gửi thông báo cho admin và ghi audit log.

## Luồng mẫu 4: Duyệt / Từ chối / Khóa tin đăng

Các chức năng kiểm duyệt là nhóm phù hợp nhất để trình bày State, Command và Observer.

**Pattern chính:**

- Command: mỗi thao tác admin là một lệnh riêng.
- Proxy: kiểm tra quyền admin.
- Template Method: chuẩn hóa quy trình kiểm duyệt.
- State: kiểm soát chuyển trạng thái.
- Observer: thông báo và cập nhật hệ thống.

**Luồng xử lý gợi ý:**

- Admin thực hiện ApproveListingCommand, RejectListingCommand hoặc LockListingCommand.
- AdminPermissionProxy kiểm tra quyền trước khi thực hiện.
- ModerationTemplate quy định khung xử lý: kiểm tra, đổi trạng thái, ghi lý do, thông báo.
- ListingState chỉ cho phép chuyển trạng thái hợp lệ, ví dụ Pending -> Approved hoặc Pending -> Rejected.
- Observer gửi thông báo cho chủ tin, cập nhật search index, ghi audit log.

## Luồng mẫu 5: Tìm kiếm và lọc tin đăng

Tìm kiếm/lọc là phần nên dùng Specification và Strategy để tránh code query bị rối.

**Pattern chính:**

- Builder: tạo object tiêu chí tìm kiếm.
- Specification: biểu diễn điều kiện lọc.
- Composite: ghép nhiều điều kiện.
- Strategy: đổi thuật toán sắp xếp/tìm kiếm.
- Interpreter: hỗ trợ cú pháp lọc nâng cao.
- Iterator: phân trang kết quả.

**Luồng xử lý gợi ý:**

- SearchCriteriaBuilder dựng tiêu chí từ giao diện: keyword, giá, diện tích, tỉnh/thành, tiện ích, trạng thái.
- Các Specification như PriceRangeSpec, LocationSpec, AmenitySpec được ghép bằng AND/OR.
- SortStrategy chọn cách sắp xếp: mới nhất, giá thấp, gần tôi, VIP trước.
- Nếu có ngôn ngữ lọc nâng cao, Interpreter parse câu lọc thành cây biểu thức.
- Iterator/Pagination trả kết quả theo trang.

# 4\. Bảng ưu tiên triển khai thực tế

Nếu không thể triển khai tất cả pattern, nên ưu tiên các pattern dưới đây vì chúng gắn chặt với nghiệp vụ và dễ chứng minh trong báo cáo:

| **Module**            | **Pattern nên ưu tiên**                                                              |
| --------------------- | ------------------------------------------------------------------------------------ |
| Tài khoản / Đăng nhập | Strategy, Factory Method, Adapter, Chain of Responsibility, Proxy                    |
| Tin đăng              | Builder, State, Command, Observer, Chain of Responsibility, Specification, Decorator |
| Lịch hẹn              | State, Command, Observer, Mediator, Builder                                          |
| Tiện ích              | Composite, Flyweight, Factory Method, Command                                        |
| Gói tin               | Decorator, Strategy, Factory Method, State, Command                                  |
| Thanh toán            | Abstract Factory, Factory Method, Adapter, State, Chain of Responsibility, Observer  |
| Chat                  | Mediator, Observer, Proxy, Adapter                                                   |
| Map view              | Adapter, Strategy, Flyweight, Specification                                          |
| Dashboard/Báo cáo     | Facade, Strategy, Visitor, Factory Method, Template Method                           |

# 5\. Kết luận

Với website đăng tin bất động sản, các pattern nên tập trung nhất là State, Strategy, Factory Method, Adapter, Observer, Builder, Decorator, Chain of Responsibility, Command và Specification. Các pattern này xuất hiện tự nhiên trong nghiệp vụ: tin đăng có vòng đời trạng thái, thanh toán cần tích hợp bên thứ ba, gói tin cần nâng cấp quyền lợi, tìm kiếm/lọc cần nhiều tiêu chí, và admin cần duyệt/từ chối/khóa/gỡ tin theo quy trình rõ ràng.

Khi viết báo cáo, nên chọn 3 luồng chính để minh họa bằng sơ đồ lớp hoặc sequence diagram: Đăng nhập Google, Tạo/Duyệt tin đăng, và Nâng cấp gói tin kèm thanh toán. Ba luồng này thể hiện đủ cả Creational, Structural và Behavioral Patterns.
