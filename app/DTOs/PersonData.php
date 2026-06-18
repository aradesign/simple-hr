<?php

namespace App\DTOs;

use App\Domain\Enums\Gender;
use App\Domain\Enums\MaritalStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\Person;

class PersonData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $nationalId = null,
        public ?string $mobile = null,
        public ?string $birthDate = null,
        public bool $birthDateProvided = false,
        public ?Gender $gender = null,
        public ?PersonLifecycleStatus $lifecycleStatus = null,
        public ?MaritalStatus $maritalStatus = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $postalCode = null,
        public ?string $profilePhoto = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            nationalId: $data['national_id'] ?? null,
            mobile: $data['mobile'] ?? null,
            birthDate: array_key_exists('birth_date', $data) && filled($data['birth_date'])
                ? $data['birth_date']
                : null,
            birthDateProvided: array_key_exists('birth_date', $data),
            gender: isset($data['gender']) ? Gender::from($data['gender']) : null,
            lifecycleStatus: isset($data['lifecycle_status']) ? PersonLifecycleStatus::from($data['lifecycle_status']) : null,
            maritalStatus: isset($data['marital_status']) ? MaritalStatus::from($data['marital_status']) : null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            province: $data['province'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            profilePhoto: $data['profile_photo'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public static function fromModel(Person $person): self
    {
        return new self(
            firstName: $person->first_name,
            lastName: $person->last_name,
            nationalId: $person->national_id,
            mobile: $person->mobile,
            birthDate: $person->birth_date?->toDateString(),
            gender: $person->gender,
            lifecycleStatus: $person->lifecycle_status,
            maritalStatus: $person->marital_status,
            address: $person->address,
            city: $person->city,
            province: $person->province,
            postalCode: $person->postal_code,
            profilePhoto: $person->profile_photo,
            notes: $person->notes,
        );
    }

    public function toArray(): array
    {
        $data = array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'national_id' => $this->nationalId,
            'mobile' => $this->mobile,
            'gender' => $this->gender?->value,
            'lifecycle_status' => $this->lifecycleStatus?->value,
            'marital_status' => $this->maritalStatus?->value,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,
            'profile_photo' => $this->profilePhoto,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);

        if ($this->birthDateProvided) {
            $data['birth_date'] = $this->birthDate;
        }

        return $data;
    }
}
