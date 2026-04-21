<?php

namespace App\Http\Requests;

use App\DTOs\CreateListingDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CreateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'demand_type' => ['required', Rule::in(['SALE', 'RENT'])],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'property_type' => ['required', 'string', 'max:50'],
            'province_code' => ['required', 'string', 'max:20'],
            'district_code' => ['required', 'string', 'max:20'],
            'ward_code' => ['nullable', 'string', 'max:20'],
            'street_code' => ['nullable', 'string', 'max:20'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'area' => ['required', 'numeric', 'gt:0'],
            'price' => ['nullable', 'numeric', 'gte:0'],
            'is_negotiable' => ['nullable', 'boolean'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:9'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:9'],
            'floors' => ['nullable', 'integer', 'min:0', 'max:99'],
            'floor_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'balconies' => ['nullable', 'integer', 'min:0', 'max:9'],
            'facade_width' => ['nullable', 'numeric', 'gt:0'],
            'depth' => ['nullable', 'numeric', 'gt:0'],
            'road_width' => ['nullable', 'numeric', 'gt:0'],
            'direction_code' => ['nullable', 'string', 'max:30'],
            'balcony_direction_code' => ['nullable', 'string', 'max:30'],
            'furniture_status' => ['nullable', Rule::in(['NONE', 'BASIC', 'FULL'])],
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'regex:/^(03|05|07|08|09)\d{8}$/'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'poster_type' => ['required', Rule::in(['OWNER', 'BROKER'])],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],

            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['string', 'url', 'max:2048'],
            'video' => ['nullable', 'string', 'url', 'max:2048'],

            'attribute_ids' => ['nullable', 'array'],
            'attribute_ids.*' => ['integer', 'exists:attributes,id'],

            'request_verification' => ['nullable', 'boolean'],
            'identity_card_front' => ['nullable', 'string', 'url', 'max:2048'],
            'identity_card_back' => ['nullable', 'string', 'url', 'max:2048'],
            'legal_documents' => ['nullable', 'array', 'max:5'],
            'legal_documents.*' => ['string', 'url', 'max:2048'],

            'appointment_at' => ['nullable', 'date', 'after:now'],
            'appointment_contact_name' => ['nullable', 'string', 'max:100'],
            'appointment_contact_phone' => ['nullable', 'regex:/^(03|05|07|08|09)\d{8}$/'],
            'appointment_contact_email' => ['nullable', 'email', 'max:255'],
            'appointment_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tieu de khong duoc de trong.',
            'title.max' => 'Tieu de khong duoc vuot qua 120 ky tu.',
            'description.required' => 'Mo ta khong duoc de trong.',
            'description.min' => 'Mo ta phai co it nhat 20 ky tu.',
            'description.max' => 'Mo ta khong duoc vuot qua 5000 ky tu.',
            'images.required' => 'Ban phai tai len it nhat 1 anh.',
            'images.min' => 'Ban phai tai len it nhat 1 anh.',
            'images.max' => 'Toi da 10 anh cho moi tin dang.',
            'images.*.max' => 'Moi anh khong duoc vuot qua 30MB.',
            'video.max' => 'Video khong duoc vuot qua 100MB.',
            'area.gt' => 'Dien tich phai lon hon 0.',
            'price.required' => 'Gia bat buoc nhap neu tin dang khong de o che do thuong luong.',
            'price.gt' => 'Gia phai lon hon 0.',
            'contact_phone.regex' => 'So dien thoai lien he khong dung dinh dang.',
            'appointment_at.after' => 'Khong duoc chon lich hen trong qua khu.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isNegotiable = filter_var($this->input('is_negotiable', false), FILTER_VALIDATE_BOOLEAN);
            $price = $this->input('price');

            if (!$isNegotiable) {
                if ($price === null || $price === '') {
                    $validator->errors()->add('price', 'Gia bat buoc nhap neu tin dang khong de o che do thuong luong.');
                } elseif ((float) $price <= 0) {
                    $validator->errors()->add('price', 'Gia phai lon hon 0.');
                }
            }

            $requestVerification = filter_var($this->input('request_verification', false), FILTER_VALIDATE_BOOLEAN);

            if ($requestVerification) {
                if (!$this->input('identity_card_front')) {
                    $validator->errors()->add('identity_card_front', 'Can tai len anh CCCD mat truoc de gui yeu cau xac thuc.');
                }

                if (!$this->input('identity_card_back')) {
                    $validator->errors()->add('identity_card_back', 'Can tai len anh CCCD mat sau de gui yeu cau xac thuc.');
                }
            }
        });
    }

    public function toDto(): CreateListingDto
    {
        $validated = $this->validated();

        return new CreateListingDto(
            demandType: $validated['demand_type'],
            title: trim($validated['title']),
            description: trim($validated['description']),
            propertyType: $validated['property_type'],
            provinceCode: $validated['province_code'],
            districtCode: $validated['district_code'],
            wardCode: $validated['ward_code'] ?? null,
            streetCode: $validated['street_code'] ?? null,
            projectName: isset($validated['project_name']) ? trim($validated['project_name']) : null,
            addressDetail: isset($validated['address_detail']) ? trim($validated['address_detail']) : null,
            area: (float) $validated['area'],
            price: isset($validated['price']) ? (float) $validated['price'] : 0.0,
            isNegotiable: (bool) ($validated['is_negotiable'] ?? false),
            bedrooms: (int) ($validated['bedrooms'] ?? 0),
            bathrooms: (int) ($validated['bathrooms'] ?? 0),
            floors: isset($validated['floors']) ? (int) $validated['floors'] : null,
            floorNumber: isset($validated['floor_number']) ? (int) $validated['floor_number'] : null,
            balconies: isset($validated['balconies']) ? (int) $validated['balconies'] : null,
            facadeWidth: isset($validated['facade_width']) ? (float) $validated['facade_width'] : null,
            depth: isset($validated['depth']) ? (float) $validated['depth'] : null,
            roadWidth: isset($validated['road_width']) ? (float) $validated['road_width'] : null,
            directionCode: $validated['direction_code'] ?? null,
            balconyDirectionCode: $validated['balcony_direction_code'] ?? null,
            furnitureStatus: $validated['furniture_status'] ?? null,
            contactName: trim($validated['contact_name']),
            contactPhone: trim($validated['contact_phone']),
            contactEmail: isset($validated['contact_email']) ? trim($validated['contact_email']) : null,
            posterType: $validated['poster_type'],
            lat: isset($validated['lat']) ? (float) $validated['lat'] : null,
            lng: isset($validated['lng']) ? (float) $validated['lng'] : null,
            images: $validated['images'] ?? [],
            video: $validated['video'] ?? null,
            attributeIds: array_map('intval', $validated['attribute_ids'] ?? []),
            amenities: array_values(array_map('strval', $validated['amenities'] ?? [])),
            legalPaperTypes: array_values(array_map('strval', $validated['legal_paper_types'] ?? [])),
            publicInfoAgreed: (bool) ($validated['public_info_agreed'] ?? false),
            requestVerification: (bool) ($validated['request_verification'] ?? false),
            identityCardFront: $validated['identity_card_front'] ?? null,
            identityCardBack: $validated['identity_card_back'] ?? null,
            legalDocuments: $validated['legal_documents'] ?? [],
            appointmentAt: $validated['appointment_at'] ?? null,
            appointmentDays: array_map('intval', $validated['appointment_days'] ?? []),
            appointmentTimeSlot: $validated['appointment_time_slot'] ?? null,
            appointmentContactName: isset($validated['appointment_contact_name']) ? trim($validated['appointment_contact_name']) : null,
            appointmentContactPhone: isset($validated['appointment_contact_phone']) ? trim($validated['appointment_contact_phone']) : null,
            appointmentContactEmail: isset($validated['appointment_contact_email']) ? trim($validated['appointment_contact_email']) : null,
            appointmentNote: isset($validated['appointment_note']) ? trim($validated['appointment_note']) : null,
            packageId: isset($validated['package_id']) ? (int) $validated['package_id'] : null,
        );
    }
}
