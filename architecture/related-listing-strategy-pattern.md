# Strategy Pattern cho chức năng Tin đăng liên quan

## 1. Chức năng áp dụng

Áp dụng cho phần `Tin đăng liên quan` trong trang chi tiết tin đăng.

File liên quan:

- `PropifyFrontend/src/pages/Listings/Detail.vue`
- `PropifyFrontend/src/utils/relatedListingStrategies.js`
- `PropifyBackend/app/Repositories/Eloquent/EloquentListingRepository.php`
- `PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php`

## 2. Pattern được áp dụng

Pattern sử dụng: **Strategy Pattern**.

Mục tiêu của pattern là tách thuật toán chọn tin liên quan ra khỏi trang chi tiết tin đăng. `Detail.vue` không tự xử lý toàn bộ logic lọc và sắp xếp nữa, mà gọi strategy để lấy danh sách kết quả phù hợp.

## 3. Vấn đề trước khi áp dụng

Trước đó phần `Tin đăng liên quan` được xử lý trực tiếp trong `Detail.vue`.

Các bước như:

- Loại bỏ chính tin đang xem.
- Lọc cùng loại nhu cầu `SALE` hoặc `RENT`.
- Lọc cùng thành phố/tỉnh.
- Tính khoảng cách theo `lat/lng`.
- Sắp xếp tin gần nhất.
- Chuyển dữ liệu listing thành dữ liệu card để hiển thị.

đều nằm chung trong page component.

Cách này vẫn chạy được, nhưng làm `Detail.vue` bị nhiều trách nhiệm hơn:

- Vừa render UI.
- Vừa gọi API.
- Vừa biết thuật toán chọn tin liên quan.
- Vừa map dữ liệu sang card hiển thị.

Nếu sau này đổi tiêu chí liên quan, ví dụ thêm cùng loại BĐS, cùng khoảng giá, cùng phường, hoặc ưu tiên tin có gói cao hơn, thì sẽ phải sửa trực tiếp trong page.

## 4. Cách áp dụng Strategy Pattern

Tạo file:

```txt
PropifyFrontend/src/utils/relatedListingStrategies.js
```

Trong file này có strategy chính:

```js
class SameDemandSameProvinceNearestStrategy extends RelatedListingStrategy
```

Strategy này chịu trách nhiệm chọn tin liên quan theo thứ tự:

1. Bỏ chính tin hiện tại.
2. Chỉ giữ tin có cùng `demand_type`.
   - Tin mua bán thì chỉ gợi ý tin mua bán.
   - Tin cho thuê thì chỉ gợi ý tin cho thuê.
3. Chỉ giữ tin cùng `province_code`.
4. Nếu có tọa độ `lat/lng`, tính khoảng cách giữa tin hiện tại và tin liên quan.
5. Sắp xếp theo khoảng cách gần nhất.
6. Lấy tối đa 4 tin.

Hàm public được dùng bởi page:

```js
selectRelatedListings(currentListing, listings, {
  limit: 4,
  formatPrice,
})
```

## 5. Chỗ gọi trong Detail.vue

Trong `Detail.vue`, hàm `loadRelatedListings()` chỉ còn nhiệm vụ:

1. Gọi API lấy danh sách public listings.
2. Hydrate địa chỉ cho các listing.
3. Gọi Strategy để chọn tin liên quan.

Ví dụ:

```js
relatedListings.value = selectRelatedListings(current, items, {
  limit: 4,
  formatPrice,
});
```

Như vậy `Detail.vue` không cần biết chi tiết thuật toán chọn tin liên quan.

## 6. Vì sao dùng Strategy Pattern

Strategy Pattern phù hợp vì chức năng này có thể có nhiều thuật toán chọn tin liên quan khác nhau.

Ví dụ hiện tại dùng:

```txt
Cùng nhu cầu + cùng thành phố + gần nhất
```

Sau này có thể thêm các strategy khác:

```txt
Cùng nhu cầu + cùng loại BĐS + cùng khoảng giá
Cùng nhu cầu + cùng phường + gần nhất
Ưu tiên tin gói cao + cùng thành phố
Ưu tiên tin đã xác thực + gần nhất
```

Khi đó chỉ cần tạo strategy mới và thay strategy được truyền vào, không phải sửa lại template hoặc flow chính của `Detail.vue`.

## 7. Lợi ích đạt được

- `Detail.vue` gọn hơn, tập trung vào hiển thị và gọi dữ liệu.
- Logic chọn tin liên quan được gom vào một nơi riêng.
- Dễ thay đổi thuật toán chọn tin liên quan.
- Dễ mở rộng thêm strategy mới.
- Dễ giải thích trong tài liệu thiết kế vì mỗi strategy tương ứng với một cách chọn tin liên quan.

## 8. Backend hỗ trợ dữ liệu cho strategy

Để Strategy có thể tính khoảng cách, API danh sách tin public cần trả thêm tọa độ:

```txt
lat
lng
```

Vì vậy backend đã bổ sung `lat,lng` vào eager load property trong:

```txt
PropifyBackend/app/Repositories/Eloquent/EloquentListingRepository.php
```

Ngoài ra cache key public listings được tăng version trong:

```txt
PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php
```

Mục đích là tránh cache cũ thiếu dữ liệu tọa độ.

## 9. Kết luận

Chức năng `Tin đăng liên quan` hiện áp dụng **Strategy Pattern** để tách thuật toán chọn tin ra khỏi UI.

Strategy hiện tại:

```txt
SameDemandSameProvinceNearestStrategy
```

Ý nghĩa:

```txt
Chọn tin cùng nhu cầu, cùng thành phố/tỉnh, và ưu tiên tin gần bất động sản hiện tại nhất.
```
