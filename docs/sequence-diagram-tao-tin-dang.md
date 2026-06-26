# Sequence Diagram - Tạo Tin Đăng

```plantuml
@startuml

title Tạo Tin Đăng

actor "Owner" as Owner
participant "PostListing\n/ Frontend" as FE
participant "ListingController\n/ API" as Controller
participant "CreateListingRequest\n«validation»" as Validation
participant "ListingService" as ListingService
participant "CreateListingCommand" as CreateCmd
participant "ListingSubmissionValidationPipeline" as ValidationPipeline
participant "Listing / Property\n«entity»" as Entity
participant "ListingRepository\n«repository»" as Repo
database "Database" as DB

Owner -> FE: Nhập thông tin tin đăng
Owner -> FE: Bấm đăng tin / lưu nháp

FE -> Controller: POST /api/v1/listings\nlistingData

Controller -> Validation: validate()

alt Dữ liệu không hợp lệ
    Validation --> Controller: ValidationError
    Controller --> FE: 422 ValidationError
    FE --> Owner: Hiển thị lỗi
else Validation passed
    Controller -> Validation: toDto()
    Validation --> Controller: CreateListingDto
    
    Controller -> ListingService: create(user, dto)
    
    ListingService -> CreateCmd: handle(user, dto)
    
    CreateCmd -> ValidationPipeline: validate(context)
    
    ValidationPipeline -> ValidationPipeline: Kiểm tra user.phone
    
    alt User chưa có SĐT
        ValidationPipeline --> CreateCmd: AuthPhoneNotVerified
        CreateCmd --> ListingService: Exception
        ListingService --> Controller: Exception
        Controller --> FE: 403 Forbidden
        FE --> Owner: Hiển thị lỗi
    else User có SĐT verified
        
        CreateCmd -> CreateCmd: BEGIN TRANSACTION
        
        CreateCmd -> Repo: createProperty(data)
        Repo -> DB: INSERT properties\n(type, province_code, ward_code,\naddress_detail, area, price,\nbedrooms, bathrooms, amenities, ...)
        DB --> Repo: property_id
        Repo --> CreateCmd: Property entity
        
        alt dto.attributeIds có dữ liệu
            CreateCmd -> Repo: syncAttributes(property_id, attribute_ids)
            Repo -> DB: INSERT INTO property_attributes
            DB --> Repo: success
        end
        
        CreateCmd -> CreateCmd: Calculate status\nDRAFT or PENDING
        
        CreateCmd -> Repo: createListing(data)
        Repo -> DB: INSERT listings\n(property_id, owner_id,\ndemand_type, title, description,\nstatus, package_id, score,\nis_verified, has_video,\nrequest_verification, submitted_at)
        DB --> Repo: listing_id
        Repo --> CreateCmd: Listing entity
        
        CreateCmd -> Repo: createListingImage(...)
        loop Cho mỗi ảnh
            Repo -> DB: INSERT listing_images\n(listing_id, image_url,\nis_thumbnail, sort_order)
            DB --> Repo: image_id
        end
        
        opt Có video
            CreateCmd -> Repo: createListingVideo(...)
            Repo -> DB: INSERT listing_videos\n(listing_id, video_url,\nprovider, mime_type)
            DB --> Repo: video_id
        end
        
        opt request_verification = true
            CreateCmd -> Repo: createVerificationDocument(...)
            loop ID_FRONT, ID_BACK, LEGAL_DOCUMENTS
                Repo -> DB: INSERT listing_verification_documents\n(listing_id, document_type,\nfile_url, sort_order)
                DB --> Repo: document_id
            end
        end
        
        CreateCmd -> CreateCmd: COMMIT TRANSACTION
        
        CreateCmd -> Repo: load(['property.attributes',\n'images', 'videos',\n'verificationDocuments'])
        Repo -> DB: SELECT với relations
        DB --> Repo: Listing với relations
        
        CreateCmd -> CreateCmd: Dispatch ListingSaved event
        
        CreateCmd --> ListingService: Listing
        ListingService -> ListingService: clearPublicListingsCache()
        
        ListingService --> Controller: Listing
        
        Controller --> FE: 201 Created + ListingResource
        FE --> Owner: Hiển thị tin tạo thành công
    end
end

@enduml
```

## Giải Thích

**Quy trình tạo tin đăng:**

### 1. Frontend → Controller (POST /api/v1/listings)
- Owner nhập thông tin: tiêu đề, mô tả, loại BĐS, địa chỉ, diện tích, giá, hình ảnh, video, giấy tờ xác thực
- Owner chọn: "Lưu nháp" (save_as_draft = true) hoặc "Đăng tin" (save_as_draft = false)

### 2. Validation (CreateListingRequest)
- **Nếu lưu nháp**: Validation lỏng lẻo (nhiều trường nullable)
- **Nếu đăng tin**: Validation nghiêm ngặt:
  - Tiêu đề bắt buộc (max 120 ký tự)
  - Mô tả >= 20 ký tự
  - Ít nhất 1 ảnh (max 10)
  - Giá > 0 (nếu không thương lượng)
  - Thông tin liên hệ đầy đủ

### 3. Service Layer
**ListingService → CreateListingCommand → ValidationPipeline:**
- **UserPhoneVerifiedHandler**: Kiểm tra user đã verify SĐT chưa (bắt buộc để đăng tin)
- **VerificationDocumentsHandler**: Validate giấy tờ xác thực (nếu có)

### 4. Database Transaction (Atomic)
Tất cả thao tác trong 1 transaction:

**a) Tạo Property:**
```sql
INSERT INTO properties (type, province_code, ward_code, street_code, 
  address_detail, area, price, is_negotiable, bedrooms, bathrooms, 
  floors, amenities, legal_paper_types, contact_name, contact_phone, ...)
```

**b) Sync Attributes:**
```sql
INSERT INTO property_attributes (property_id, attribute_id)
```

**c) Tạo Listing:**
```sql
INSERT INTO listings (property_id, owner_id, demand_type, title, 
  description, status, package_id, score, is_verified, has_video, 
  request_verification, submitted_at)
```
- **status**: DRAFT (nếu lưu nháp) hoặc PENDING (nếu gửi duyệt)
- **score**: Tính toán dựa trên độ đầy đủ thông tin (0-100)
- **is_verified**: PENDING_VERIFICATION (nếu có đủ giấy tờ) hoặc NOT_VERIFIED

**d) Tạo Images:**
```sql
INSERT INTO listing_images (listing_id, image_url, is_thumbnail, sort_order)
```
- Ảnh đầu tiên: `is_thumbnail = true`

**e) Tạo Video (optional):**
```sql
INSERT INTO listing_videos (listing_id, video_url, provider, mime_type)
```

**f) Tạo Verification Documents (optional):**
```sql
INSERT INTO listing_verification_documents 
  (listing_id, document_type, file_url, sort_order)
```
- `document_type`: ID_FRONT, ID_BACK, LEGAL_DOCUMENT

### 5. Post-processing
- **Load relations**: property.attributes, images, videos, verificationDocuments
- **Dispatch event**: `ListingSaved` (listeners: CreateListingNotification, LogListingSaved, ClearPublicListingCache)
- **Clear cache**: Xóa cache tin đăng công khai

### 6. Response
- **201 Created** + ListingResource
- Message: 
  - "Lưu tin nháp thành công" (nếu DRAFT)
  - "Tạo tin đăng thành công. Tin đăng đang chờ duyệt." (nếu PENDING)

---

**Cách xem diagram**: Copy code PlantUML vào https://www.plantuml.com/plantuml/uml/
