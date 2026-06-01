# Map Listing Search with Marker Clustering

We will implement a high-performance map listing search on both the **Sales** and **Rent** pages. When the user clicks the "Tìm kiếm theo bản đồ" (Search by Map) widget, a map view modal will display all active listings matching the current filters. The map markers will cluster dynamically based on zoom levels using a custom greedy distance-based clustering algorithm, and hovering over a single listing will show its summary card.

## User Review Required

> [!IMPORTANT]
> - **Lightweight Map Endpoint**: We will create a dedicated `/api/v1/listings/map` endpoint returning minimal metadata (coordinates, price, area, title, thumbnail) for matching active listings. This avoids heavy data transfers and memory overhead on the client compared to using the paginated `ListingResource`.
> - **Greedy Distance-Based Clustering**: We will implement a custom, high-performance clustering algorithm in JavaScript (running in $O(N)$) inside the frontend map component. This removes the need for external clustering dependencies and allows full customization of cluster styles.
> - **Map Tiles Service**: We will use CartoDB Positron (`https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png`) as the map source to match the clean light-gray design shown in the user's mockup.

---

## Proposed Changes

### Backend (Laravel 12 API)

#### [NEW] [MapListingResource.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Resources/MapListingResource.php)
Create a lightweight API resource containing only the fields required for the map search view to optimize network payload size.

#### [MODIFY] [ListingRepository.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Repositories/ListingRepository.php)
Add method signature to retrieve map listings:
```php
public function getMapListings(
    ?string $demandType,
    ?string $posterType = null,
    ?float $minPrice = null,
    ?float $maxPrice = null,
    ?float $minArea = null,
    ?float $maxArea = null
): Collection;
```

#### [MODIFY] [EloquentListingRepository.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Repositories/Eloquent/EloquentListingRepository.php)
Implement `getMapListings` to return a `Collection` of active listings with properties containing non-null latitude and longitude values. Eager load only the necessary columns and listing images to minimize resource usage.

#### [MODIFY] [ListingService.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Listing/ListingService.php)
Add service layer method signature.

#### [MODIFY] [ListingServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php)
Implement `getMapListings` with Redis/Cache tagged caching (`listings:public`). Cache responses for 5 minutes (`300` seconds) using hashed cache keys constructed from query filters.

#### [MODIFY] [ListingController.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Controllers/Api/V1/Listing/ListingController.php)
Add `mapListings` action to handle incoming filtering parameters and return data through the lightweight `MapListingResource`.

#### [MODIFY] [api.php](file:///d:/PROJECT/Meyland/PropifyBackend/routes/api.php)
Register the public route `Route::get('/map', [ListingController::class, 'mapListings'])->name('map');` under the `listings` prefix.

---

### Frontend (Vue 3 + Vite)

#### [NEW] [MapSearchModal.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/shared/MapSearchModal.vue)
A modal component that loads Leaflet and displays a fullscreen map layout:
- Fetches all active map listings matching current filters using TanStack Query.
- Implements dynamic greedy distance-based clustering:
  - Groups coordinates based on screen pixel threshold (`60px`) at current zoom level.
  - Updates clusters on map `zoomend` and `moveend` events.
- Renders cluster points as black circles with count numbers (scaled by count magnitude).
- Renders single points as blue pills with building icon and label "1".
- On hover of a single point, displays the custom popup listing summary card (Image 4 mockup).
- Clicking a cluster zooms into that region.

#### [MODIFY] [listingService.js](file:///d:/PROJECT/Meyland/PropifyFrontend/src/services/listingService.js)
Add `getMapListings` calling the `/api/v1/listings/map` endpoint.

#### [MODIFY] [queryKeys.js](file:///d:/PROJECT/Meyland/PropifyFrontend/src/composables/queryKeys.js)
Define map listing query keys:
```javascript
  mapList: (params = {}) => [...listingKeys.all, 'map', params],
```

#### [MODIFY] [Sale/index.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Sale/index.vue)
Import and render `MapSearchModal`. Add click listener to "Tìm kiếm theo bản đồ" widget to toggle `isMapOpen` to `true`. Pass active search keyword and filters into the modal.

#### [MODIFY] [Rent/index.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Rent/index.vue)
Perform the same integration for the Rent page.

---

## Verification Plan

### Automated Tests
- Run `composer run test` to verify that existing test suites are intact.
- Create a feature test `ListingMapSearchTest.php` on the backend testing coordinate filters, active listings checks, and output payload format.

### Manual Verification
- Click on "Tìm kiếm theo bản đồ" on both Sales and Rent pages.
- Verify Leaflet map renders using CARTO light tiles.
- Hover over markers to verify custom popup card renders thumbnail, price, area, and address properly.
- Zoom in and out to verify clusters merge/separate dynamically.
