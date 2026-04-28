<?php

namespace App\Http\Controllers\Api\V1\Geocoding;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class GeocodingController
{
    /**
     * Proxy reverse geocode request to Nominatim.
     * Frontend không thể gọi Nominatim trực tiếp do CORS,
     * nên backend đóng vai trò proxy với User-Agent hợp lệ.
     */
    public function reverse(Request $request): JsonResponse
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (!$lat || !$lng) {
            return response()->json(['error' => 'lat và lng là bắt buộc'], 422);
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return response()->json(['error' => 'Tọa độ không hợp lệ'], 422);
        }

        try {
            $response = Http::withHeaders([
                'User-Agent'      => 'Propify/1.0 (contact@propify.vn)',
                'Accept'          => 'application/json',
                'Accept-Language' => 'vi,en;q=0.9',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/reverse', [
                'lat'            => $lat,
                'lon'            => $lng,
                'format'         => 'json',
                'addressdetails' => 1,
                'zoom'           => 18,
                'accept-language' => 'vi',
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Nominatim không phản hồi'], 502);
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Lỗi kết nối đến dịch vụ geocoding'], 503);
        }
    }

    /**
     * Proxy search/forward geocode request to Nominatim.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q');

        if (!$query) {
            return response()->json(['error' => 'q là bắt buộc'], 422);
        }

        try {
            $response = Http::withHeaders([
                'User-Agent'      => 'Propify/1.0 (contact@propify.vn)',
                'Accept'          => 'application/json',
                'Accept-Language' => 'vi,en;q=0.9',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                'q'               => $query,
                'format'          => 'json',
                'addressdetails'  => 1,
                'limit'           => 1,
                'countrycodes'    => 'vn',
                'accept-language' => 'vi',
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Nominatim không phản hồi'], 502);
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Lỗi kết nối đến dịch vụ geocoding'], 503);
        }
    }
}
