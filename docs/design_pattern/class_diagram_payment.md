# Sơ đồ Lớp Phân hệ: Thanh toán & Nâng cấp (Payment & Upgrade Class Diagram)

Sơ đồ lớp chi tiết của phân hệ Thanh toán trực tuyến và Nâng cấp gói tin (Command, Adapter, Factory Method).

---

## Sơ đồ Lớp (PlantUML)

```plantuml
@startuml
title Phân hệ Thanh toán & Nâng cấp — Payment Class Diagram

skinparam linetype ortho
skinparam classAttributeIconSize 0

class ListingUpgradeController {
  -CreateUpgradePaymentCommand createPayCommand
  -UpgradeListingCommand upgradeCommand
  +upgrade(UpgradeRequest request): JsonResponse
  +vnpayCallback(Request request): JsonResponse
}

class CreateUpgradePaymentCommand {
  -PaymentProviderFactory paymentProviderFactory
  +execute(User user, Listing listing, Package newPackage, int durationDays, float amount, string clientIp): string
}

class UpgradeListingCommand {
  -UpgradeEligibilityPolicy policy
  -ExpiryCalculationStrategyFactory expiryFactory
  -PackageBenefitStrategyFactory benefitFactory
  +execute(Transaction transaction, Listing listing, Package newPackage, UpgradeContext context): Listing
}

interface PaymentGateway {
  +method(): string
  +createPaymentUrl(Transaction transaction, string clientIp): string
  +verifyCallback(Request request): CallbackResult
}

class VnpayGateway {
  -VnpayService vnpayService
  +createPaymentUrl(Transaction transaction, string clientIp): string
  +verifyCallback(Request request): CallbackResult
}

class PaymentProviderFactory {
  -VnpayGateway vnpayGateway
  +for(string method): PaymentGateway
}

interface ExpiryCalculationStrategy {
  +calculate(UpgradeContext context): CarbonInterface
}

class FreshPurchaseExpiryStrategy {
  +calculate(UpgradeContext context): CarbonInterface
}

class RenewalExpiryStrategy {
  +calculate(UpgradeContext context): CarbonInterface
}

class ExpiryCalculationStrategyFactory {
  +make(UpgradeContext context): ExpiryCalculationStrategy
}

class Transaction {
  +int id
  +int user_id
  +int listing_id
  +decimal amount
  +string status
  +string vnp_txn_ref
}

ListingUpgradeController --> CreateUpgradePaymentCommand : "invokes"
ListingUpgradeController --> UpgradeListingCommand : "invokes"

CreateUpgradePaymentCommand --> PaymentProviderFactory : "resolves"
CreateUpgradePaymentCommand --> Transaction : "creates PENDING"
PaymentProviderFactory --> PaymentGateway : "returns"

PaymentGateway <|.. VnpayGateway : "implements"
VnpayGateway --> VnpayService : "delegates"

UpgradeListingCommand --> Transaction : "updates SUCCESS"
UpgradeListingCommand --> ExpiryCalculationStrategyFactory : "gets strategy"
ExpiryCalculationStrategy <|.. FreshPurchaseExpiryStrategy : "implements"
ExpiryCalculationStrategy <|.. RenewalExpiryStrategy : "implements"
UpgradeListingCommand --> ExpiryCalculationStrategy : "calculates expiry"
@enduml
```
