# Sơ đồ Lớp Phân hệ: Xác thực (Authentication Class Diagram)

Sơ đồ lớp chi tiết của phân hệ Xác thực (Authentication Subsystem) bao gồm các chức năng Đăng ký, Đăng nhập, và Tích hợp bên thứ ba (Google Login).

---

## Sơ đồ Lớp (PlantUML)

```plantuml
@startuml
title Phân hệ Xác thực — Authentication Class Diagram

skinparam linetype ortho
skinparam classAttributeIconSize 0

class AuthController {
  -RegisterUserCommand registerCommand
  -AuthStrategyResolver authResolver
  +register(RegisterRequest request): JsonResponse
  +login(LoginRequest request): JsonResponse
}

class RegisterUserCommand {
  -UserRepository userRepository
  -RegistrationValidationChain validationChain
  -OtpService otpService
  +execute(RegisterUserDto dto): void
}

class RegistrationValidationChain {
  -UserRepository userRepository
  +validate(RegisterUserDto dto): void
  -checkEmailFormat(string email): void
  -checkPasswordStrength(string password): void
  -checkEmailUnique(string email): void
}

class LoginValidationChain {
  -UserRepository userRepository
  +validate(string email, string password): User
}

class AuthStrategyResolver {
  -AuthStrategy[] strategies
  +resolve(AuthMethod method): AuthStrategy
}

interface AuthStrategy {
  +method(): AuthMethod
  +authenticate(AuthPayload payload): AuthResultDto
}

class EmailPasswordAuthStrategy {
  -LoginValidationChain validationChain
  +authenticate(AuthPayload payload): AuthResultDto
}

class GoogleOAuthAuthStrategy {
  -SocialUserAdapter socialUserAdapter
  +authenticate(AuthPayload payload): AuthResultDto
}

interface SocialUserAdapter {
  +getEmail(): string
  +getName(): string
}

class GoogleSocialiteAdapter {
  -SocialiteUser adaptee
  +getEmail(): string
  +getName(): string
}

class User {
  +int id
  +string email
  +string password
  +string status
}

AuthController --> RegisterUserCommand : "invokes"
AuthController --> AuthStrategyResolver : "calls"
RegisterUserCommand --> RegistrationValidationChain : "delegates validation"
RegisterUserCommand --> User : "creates & saves"
EmailPasswordAuthStrategy --> LoginValidationChain : "delegates validation"
GoogleOAuthAuthStrategy --> SocialUserAdapter : "uses"
SocialUserAdapter <|.. GoogleSocialiteAdapter : "implements"
AuthStrategy <|.. EmailPasswordAuthStrategy : "implements"
AuthStrategy <|.. GoogleOAuthAuthStrategy : "implements"
AuthStrategyResolver --> AuthStrategy : "resolves dynamic"
@enduml
```
