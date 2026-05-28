# Chuc nang bao cao tin dang

## Pham vi da lam

Chuc nang nam o man hinh xem chi tiet tin dang, tai section `Bao cao tin dang`.
Nguoi dung chon mot ly do vi pham va bam `Gui phan anh`.

Du lieu bao cao duoc luu rieng trong bang `listing_reports`, khong ghi truc tiep vao bang `listings`.

## Bang du lieu moi

File migration:

- `PropifyBackend/database/migrations/2026_05_28_000001_create_listing_reports_table.php`

Bang `listing_reports` gom cac cot chinh:

- `listing_id`: tin dang bi bao cao.
- `reporter_id`: nguoi gui bao cao.
- `reason`: mot ly do bao cao. Neu nguoi dung chon nhieu ly do, he thong tao nhieu ban ghi rieng.
- `description`: mo ta tieng Viet cua ly do bao cao, giup admin doc nhanh thay vi chi nhin ma `reason`.
- `status`: trang thai xu ly, hien mac dinh la `WARNING`.

Model:

- `PropifyBackend/app/Models/ListingReport.php`

Quan he:

- `Listing` co `reports()`.
- `ListingReport` thuoc ve `listing()` va `reporter()`.

## API

Route:

- `POST /api/v1/listings/{id}/reports`

File:

- `PropifyBackend/routes/api.php`
- `PropifyBackend/app/Http/Controllers/Api/V1/Listing/ListingController.php`
- `PropifyBackend/app/Http/Requests/Listing/StoreListingReportRequest.php`

Payload:

```json
{
  "reasons": ["WRONG_PRICE", "WRONG_ADDRESS"]
}
```

Danh sach ly do hop le:

- `WRONG_PRICE`
- `WRONG_ADDRESS`
- `SOLD_OR_RENTED`
- `WRONG_INFORMATION`
- `UNREACHABLE_OWNER`
- `DUPLICATE_LISTING`

## Design Pattern da ap dung

### 1. Command Pattern

File:

- `PropifyBackend/app/Services/Listing/Reports/ReportListingCommand.php`

Ly do ap dung:

- Tac vu gui bao cao la mot nghiep vu doc lap: tim tin, validate rule, tao report.
- Controller khong phai xu ly business logic.
- Sau nay neu can gui notification cho admin, ghi audit log, hoac cap nhat trang thai canh bao thi co the mo rong trong command/event ma khong lam phinh controller.

Luong xu ly:

1. Controller nhan request.
2. `StoreListingReportRequest` validate payload.
3. Controller goi `ReportListingCommand`.
4. Command tao `ListingReportContext`.
5. Command chay chain validate.
6. Command luu ban ghi vao `listing_reports`.

### 2. Chain of Responsibility

File:

- `PropifyBackend/app/Services/Listing/Reports/ListingReportValidationChain.php`
- `PropifyBackend/app/Services/Listing/Reports/Rules/ListingReportValidationHandler.php`
- `PropifyBackend/app/Services/Listing/Reports/Rules/EnsureListingCanBeReportedHandler.php`
- `PropifyBackend/app/Services/Listing/Reports/Rules/PreventDuplicateListingReportHandler.php`

Ly do ap dung:

- Chuc nang bao cao co nhieu rule co the tang dan theo thoi gian.
- Moi rule nam trong mot handler rieng, de doc va de them bot.

Rule hien tai:

- Chi cho bao cao tin co trang thai `ACTIVE`.
- Chan spam: mot user khong gui lai bao cao cho cung mot tin trong vong 24 gio.

## Frontend

File:

- `PropifyFrontend/src/pages/Listings/Detail.vue`
- `PropifyFrontend/src/services/listingService.js`

Da lam:

- Section `Bao cao tin dang` dung radio button theo design.
- Cho phep chon nhieu ly do bang checkbox.
- Moi ly do duoc luu thanh mot dong rieng trong `listing_reports`.
- Nguoi bao cao duoc luu bang `reporter_id`, la id user dang dang nhap.
- Bat buoc chon it nhat mot ly do truoc khi gui.
- Nut `Gui phan anh` bi disable cho den khi co it nhat mot ly do duoc chon.
- Thong bao thanh cong/that bai hien bang toast nhu form dang tin.
- Goi API `listingService.report(id, payload)`.
- Hien thong bao thanh cong/lỗi ngay trong section.

## Ghi chu

Chua lam man hinh admin xu ly danh sach report. Hien tai du lieu da co bang rieng de admin query/lam UI kiem duyet o buoc sau.
