# Sequence Diagram - Đăng Ký Tài Khoản

```plantuml
@startuml

title Đăng Ký Tài Khoản

actor "User" as User
participant "RegisterForm\n/ Frontend" as FE
participant "AuthController\n/ API" as Controller
participant "RegisterRequest\n«validation»" as Validation
participant "AuthService" as AuthService
participant "RegisterUserCommand" as RegisterCmd
participant "RegistrationValidationChain" as ValidationChain
participant "OtpService" as OtpService
participant "User\n«entity»" as UserEntity
participant "UserRepository\n«repository»" as UserRepo
database "Database" as DB

User -> FE: Nhập thông tin đăng ký\n(họ tên, email, password)
User -> FE: Nhấn "Đăng ký"

FE -> Controller: POST /api/v1/auth/register\nregisterData

Controller -> Validation: validate()

alt Validation failed
    Validation --> Controller: ValidationError
    Controller --> FE: 422 Validation Error
    FE --> User: Hiển thị lỗi
else Validation passed
    Controller -> Validation: toDto()
    Validation --> Controller: RegisterUserDto
    
    Controller -> AuthService: register(dto)
    
    AuthService -> RegisterCmd: execute(dto)
    
    RegisterCmd -> ValidationChain: validate(dto)
    
    ValidationChain -> ValidationChain: Check email không trùng\nvới user ACTIVE
    
    alt Email đã tồn tại (ACTIVE)
        ValidationChain --> RegisterCmd: EmailAlreadyExistsException
        RegisterCmd --> AuthService: Exception
        AuthService --> Controller: Exception
        Controller --> FE: 409 Conflict
        FE --> User: "Email đã được sử dụng"
    else Email hợp lệ
        
        RegisterCmd -> UserRepo: findByEmail(email)
        UserRepo -> DB: SELECT * FROM users\nWHERE email = ?
        DB --> UserRepo: user (or null)
        
        alt User tồn tại (status = PENDING)
            note right
                User đã đăng ký trước
                nhưng chưa verify OTP
                → Cho phép đăng ký lại
            end note
            
            RegisterCmd -> UserRepo: update(user_id, data)
            UserRepo -> DB: UPDATE users SET\nfull_name, password, updated_at
            DB --> UserRepo: affected rows
            
        else User chưa tồn tại
            RegisterCmd -> UserRepo: create(data)
            UserRepo -> DB: INSERT INTO users\n(full_name, email, password,\nrole, status)
            DB --> UserRepo: user_id
            UserRepo --> RegisterCmd: User entity
        end
        
        RegisterCmd -> OtpService: generate(user, REGISTER)
        
        OtpService -> OtpService: Tạo mã OTP 6 chữ số
        OtpService -> OtpService: Hash OTP
        
        OtpService -> DB: INSERT INTO otps\n(user_id, otp_code, context,\nexpires_at)
        DB --> OtpService: otp_id
        
        OtpService -> OtpService: Gửi email chứa OTP
        
        OtpService --> RegisterCmd: success
        RegisterCmd --> AuthService: success
        AuthService --> Controller: success
        
        Controller --> FE: 202 Accepted\n"Đăng ký thành công.\nVui lòng kiểm tra email"
        FE --> User: Hiển thị thông báo
        
        == Bước 2: Verify OTP ==
        
        User -> FE: Mở email, lấy mã OTP
        User -> FE: Nhập OTP vào form
        User -> FE: Nhấn "Xác thực"
        
        FE -> Controller: POST /api/v1/auth/verify-otp\n{email, otp}
        
        Controller -> Validation: validate()
        Validation --> Controller: VerifyOtpDto
        
        Controller -> AuthService: verifyOtp(email, otp)
        
        AuthService -> DB: SELECT * FROM otps\nWHERE user_id =\n(SELECT id FROM users WHERE email = ?)\nAND context = 'REGISTER'\nAND expires_at > NOW()\nAND verified_at IS NULL
        DB --> AuthService: otp_record
        
        alt OTP không tồn tại hoặc hết hạn
            AuthService --> Controller: OtpInvalidException
            Controller --> FE: 400 Bad Request
            FE --> User: "Mã OTP không hợp lệ\nhoặc đã hết hạn"
        else OTP hợp lệ
            AuthService -> AuthService: Verify hash OTP
            
            alt OTP không khớp
                AuthService --> Controller: OtpInvalidException
                Controller --> FE: 400 Bad Request
                FE --> User: "Mã OTP không chính xác"
            else OTP khớp
                AuthService -> UserRepo: update(user_id,\n{status: ACTIVE})
                UserRepo -> DB: UPDATE users\nSET status = 'ACTIVE'
                DB --> UserRepo: success
                
                AuthService -> DB: UPDATE otps\nSET verified_at = NOW()
                DB --> AuthService: success
                
                AuthService -> AuthService: Tạo Access Token (JWT)
                AuthService -> AuthService: Tạo Refresh Token
                
                AuthService -> DB: INSERT INTO refresh_tokens
                DB --> AuthService: token_id
                
                AuthService --> Controller: AuthResult\n(access_token, refresh_token, user)
                
                Controller -> Controller: Set cookies
                
                Controller --> FE: 200 OK + tokens\n"Xác thực OTP thành công"
                FE --> User: Tự động đăng nhập\nvà chuyển đến trang chủ
            end
        end
    end
end

@enduml
```

## Giải Thích

**Quy trình đăng ký gồm 2 bước:**

### Bước 1: Đăng ký (POST /api/v1/auth/register)
1. **Frontend → Controller**: Gửi dữ liệu đăng ký
2. **Controller → Validation**: Validate format (email, password length)
3. **Controller → AuthService → RegisterUserCommand**: Xử lý logic đăng ký
4. **ValidationChain**: Kiểm tra email không trùng với user ACTIVE
5. **UserRepository**:
   - Nếu email tồn tại với status PENDING → Cập nhật thông tin (cho phép đăng ký lại)
   - Nếu email chưa tồn tại → Tạo user mới với status PENDING
6. **OtpService**: Tạo OTP, hash, lưu vào DB, gửi email
7. **Response**: 202 Accepted "Kiểm tra email"

### Bước 2: Verify OTP (POST /api/v1/auth/verify-otp)
1. **Frontend → Controller**: Gửi email + OTP
2. **AuthService**: Tìm OTP trong DB (chưa hết hạn, chưa dùng)
3. **Verify**: So sánh hash OTP
4. **Nếu đúng**:
   - Cập nhật user status = ACTIVE
   - Đánh dấu OTP đã verify
   - Tạo JWT tokens (access + refresh)
   - Lưu refresh token vào DB
   - Set cookies
5. **Response**: 200 OK + tokens, user tự động đăng nhập

---

**Cách xem diagram**: Copy code PlantUML vào https://www.plantuml.com/plantuml/uml/
