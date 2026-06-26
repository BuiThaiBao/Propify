# Sequence Diagram - Quên Mật Khẩu

```plantuml
@startuml

title Quên Mật Khẩu

actor "User" as User
participant "ForgotPasswordForm\n/ Frontend" as FE
participant "AuthController\n/ API" as Controller
participant "ForgotPasswordRequest\n«validation»" as Validation
participant "AuthService" as AuthService
participant "RequestPasswordResetCommand" as ResetCmd
participant "ForgotPasswordChain" as FPChain
participant "FindResetUserHandler" as FindUserHandler
participant "SendResetOtpHandler" as SendOtpHandler
participant "LogResetAttemptHandler" as LogHandler
participant "OtpService" as OtpService
participant "User\n«entity»" as UserEntity
participant "UserRepository\n«repository»" as UserRepo
participant "Otp\n«entity»" as OtpEntity
database "Database" as DB

== Bước 1: Yêu cầu reset password ==

User -> FE: Nhấn "Quên mật khẩu?"
User -> FE: Nhập email
User -> FE: Nhấn "Gửi mã OTP"

FE -> Controller: POST /api/v1/auth/forgot-password\n{email}

Controller -> Validation: validate()

alt Validation failed
    Validation --> Controller: ValidationError
    Controller --> FE: 422 Validation Error
    FE --> User: "Email không đúng định dạng"
else Validation passed
    Controller -> AuthService: forgotPassword(email)
    
    AuthService -> ResetCmd: execute(email)
    
    ResetCmd -> FPChain: execute(email)
    
    FPChain -> FindUserHandler: handle(email)
    
    FindUserHandler -> UserRepo: findByEmail(email)
    UserRepo -> DB: SELECT * FROM users\nWHERE email = ?
    DB --> UserRepo: user (or null)
    
    alt User không tồn tại
        note right
            Không tiết lộ user có tồn tại hay không
            (security by obscurity)
            → Vẫn trả về success
        end note
        FindUserHandler --> FPChain: Continue (silent fail)
    else User tồn tại
        FindUserHandler -> FindUserHandler: Check status = ACTIVE
        
        alt Status != ACTIVE
            FindUserHandler --> FPChain: Skip (không gửi OTP)
        else Status = ACTIVE
            FPChain -> SendOtpHandler: handle(user)
            
            SendOtpHandler -> OtpService: generate(user, PASSWORD_RESET)
            
            OtpService -> OtpService: Tạo mã OTP 6 chữ số
            OtpService -> OtpService: Hash OTP
            
            OtpService -> DB: INSERT INTO otps\n(user_id, otp_code,\ncontext = 'PASSWORD_RESET',\nexpires_at = NOW() + 5 mins)
            DB --> OtpService: otp_id
            
            OtpService -> OtpService: Gửi email chứa OTP
            
            OtpService --> SendOtpHandler: success
            
            SendOtpHandler --> FPChain: success
            
            FPChain -> LogHandler: handle(email)
            
            LogHandler -> DB: INSERT INTO reset_attempts\n(email, ip, timestamp)
            DB --> LogHandler: log_id
            note right
                Log để detect abuse
                (brute force, spam)
            end note
        end
    end
    
    FPChain --> ResetCmd: success
    ResetCmd --> AuthService: success
    AuthService --> Controller: success
    
    Controller --> FE: 200 OK\n"Mã OTP đã được gửi\ntới email của bạn"
    FE --> User: Hiển thị thông báo
end

== Bước 2: Kiểm tra OTP ==

User -> FE: Mở email, lấy mã OTP
User -> FE: Nhập email + OTP
User -> FE: Nhấn "Xác thực"

FE -> Controller: POST /api/v1/auth/check-reset-otp\n{email, otp}

Controller -> Validation: validate()
Validation --> Controller: CheckResetOtpDto

Controller -> AuthService: checkResetOtp(email, otp)

AuthService -> DB: SELECT * FROM otps\nWHERE user_id = (SELECT id FROM users WHERE email = ?)\nAND context = 'PASSWORD_RESET'\nAND expires_at > NOW()\nAND verified_at IS NULL\nORDER BY created_at DESC\nLIMIT 1
DB --> AuthService: otp_record

alt OTP không tồn tại hoặc hết hạn
    AuthService --> Controller: OtpInvalidException
    Controller --> FE: 400 Bad Request\n"Mã OTP không hợp lệ\nhoặc đã hết hạn"
    FE --> User: Hiển thị lỗi
else OTP hợp lệ
    AuthService -> AuthService: Verify hash OTP
    
    alt OTP không khớp
        AuthService --> Controller: OtpInvalidException
        Controller --> FE: 400 Bad Request\n"Mã OTP không chính xác"
        FE --> User: Hiển thị lỗi
    else OTP khớp
        AuthService --> Controller: success
        Controller --> FE: 200 OK\n"Mã OTP hợp lệ"
        FE --> User: Hiển thị form đặt password mới
    end
end

== Bước 3: Đặt lại mật khẩu ==

User -> FE: Nhập password mới
User -> FE: Xác nhận password
User -> FE: Nhấn "Đặt lại mật khẩu"

FE -> Controller: POST /api/v1/auth/reset-password\n{email, otp, password, password_confirmation}

Controller -> Validation: validate()

alt Validation failed
    Validation --> Controller: ValidationError
    Controller --> FE: 422 Validation Error
    FE --> User: Hiển thị lỗi
else Validation passed
    Controller -> AuthService: resetPassword(email, otp, password)
    
    AuthService -> DB: SELECT * FROM otps\nWHERE user_id = (SELECT id FROM users WHERE email = ?)\nAND context = 'PASSWORD_RESET'\nAND expires_at > NOW()\nAND verified_at IS NULL
    DB --> AuthService: otp_record
    
    alt OTP đã hết hạn
        AuthService --> Controller: OtpExpiredException
        Controller --> FE: 400 Bad Request\n"Mã OTP đã hết hạn"
        FE --> User: "Vui lòng thực hiện lại"
    else OTP vẫn hợp lệ
        AuthService -> AuthService: Verify OTP lần nữa
        
        alt OTP không khớp
            AuthService --> Controller: OtpInvalidException
            Controller --> FE: 400 Bad Request
            FE --> User: Hiển thị lỗi
        else OTP khớp
            AuthService -> AuthService: Hash password mới
            
            AuthService -> UserRepo: update(user_id,\n{password: hash, password_updated_at: NOW()})
            UserRepo -> DB: UPDATE users\nSET password = ?,\npassword_updated_at = NOW()
            DB --> UserRepo: affected rows
            
            AuthService -> DB: UPDATE otps\nSET verified_at = NOW()\nWHERE id = ?
            DB --> AuthService: success
            
            AuthService -> DB: DELETE FROM refresh_tokens\nWHERE user_id = ?
            DB --> AuthService: deleted count
            note right
                Xóa tất cả refresh tokens
                → Force logout khỏi mọi thiết bị
            end note
            
            AuthService --> Controller: success
            
            Controller --> FE: 200 OK\n"Mật khẩu đã được đặt lại thành công.\nVui lòng đăng nhập."
            FE --> User: Chuyển đến trang đăng nhập
        end
    end
end

@enduml
```

## Giải Thích

**Quy trình reset mật khẩu gồm 3 bước:**

### Bước 1: Yêu cầu reset (POST /api/v1/auth/forgot-password)
1. **Frontend → Controller**: Gửi {email}
2. **AuthService → ForgotPasswordChain**:
   - **FindResetUserHandler**: Tìm user, check status = ACTIVE
   - **SendResetOtpHandler**: Tạo OTP, gửi email
   - **LogResetAttemptHandler**: Log attempt để detect abuse
3. **Security**: Luôn trả về success dù email có tồn tại hay không (không tiết lộ thông tin)
4. **Response**: 200 OK "Mã OTP đã được gửi"

### Bước 2: Kiểm tra OTP (POST /api/v1/auth/check-reset-otp)
1. **Frontend → Controller**: Gửi {email, otp}
2. **AuthService**: 
   - Tìm OTP trong DB (context = PASSWORD_RESET, chưa hết hạn, chưa dùng)
   - Verify hash OTP
3. **Nếu đúng**: Response 200 OK "Mã OTP hợp lệ"
4. **Frontend**: Hiển thị form nhập password mới

### Bước 3: Reset password (POST /api/v1/auth/reset-password)
1. **Frontend → Controller**: Gửi {email, otp, password, password_confirmation}
2. **Validation**: Password >= 8 ký tự, password khớp với confirmation
3. **AuthService**:
   - Verify OTP lần nữa (đảm bảo không hết hạn giữa chừng)
   - Hash password mới
   - Update users.password
   - Đánh dấu OTP đã verify
   - **Xóa tất cả refresh_tokens** → Force logout khỏi mọi thiết bị
4. **Response**: 200 OK "Đặt lại thành công"
5. **Frontend**: Chuyển đến trang đăng nhập

**Security Features:**
- **Email enumeration prevention**: Không tiết lộ email có tồn tại hay không
- **OTP expiry**: 5 phút
- **One-time use**: OTP chỉ dùng được 1 lần
- **Double verification**: Verify OTP ở cả bước 2 và bước 3
- **Session revocation**: Force logout tất cả devices sau reset
- **Attempt logging**: Track để detect brute force
- **Context isolation**: OTP PASSWORD_RESET khác với REGISTER

---

**Cách xem diagram**: Copy code PlantUML vào https://www.plantuml.com/plantuml/uml/
