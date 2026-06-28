# Sơ đồ Lớp Phân hệ: Lọc & Sắp xếp tin (Filtering & Sorting Class Diagram)

Sơ đồ lớp chi tiết của phân hệ Sắp xếp hiển thị và Tìm kiếm lọc tin đăng (Strategy & Factory Method).

---

## Sơ đồ Lớp (PlantUML)

```plantuml
@startuml
title Phân hệ Lọc & Sắp xếp — Filter & Sort Class Diagram

skinparam linetype ortho
skinparam classAttributeIconSize 0

interface ListingSortingStrategy {
  +apply(Builder query): Builder
}

class DefaultPackageScoreSortingStrategy {
  +apply(Builder query): Builder
}

class NewestListingSortingStrategy {
  +apply(Builder query): Builder
}

class PriceLowToHighSortingStrategy {
  +apply(Builder query): Builder
}

class PriceHighToLowSortingStrategy {
  +apply(Builder query): Builder
}

class ListingSortingStrategyFactory {
  +{static} make(?string sortBy): ListingSortingStrategy
}

interface SearchFieldStrategy {
  +apply(Builder query, string normalizedKeyword): void
}

class TitleSearchStrategy {
  +apply(Builder query, string normalizedKeyword): void
}

class OwnerSearchStrategy {
  +apply(Builder query, string normalizedKeyword): void
}

class AddressSearchStrategy {
  +apply(Builder query, string normalizedKeyword): void
}

class SearchFieldStrategyFactory {
  +for(?string searchField): SearchFieldStrategy
}

ListingSortingStrategy <|.. DefaultPackageScoreSortingStrategy
ListingSortingStrategy <|.. NewestListingSortingStrategy
ListingSortingStrategy <|.. PriceLowToHighSortingStrategy
ListingSortingStrategy <|.. PriceHighToLowSortingStrategy
ListingSortingStrategyFactory ..> ListingSortingStrategy : "creates"

SearchFieldStrategy <|.. TitleSearchStrategy
SearchFieldStrategy <|.. OwnerSearchStrategy
SearchFieldStrategy <|.. AddressSearchStrategy
SearchFieldStrategyFactory ..> SearchFieldStrategy : "creates"
@enduml
```
