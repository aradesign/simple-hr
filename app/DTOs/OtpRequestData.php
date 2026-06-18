<?php

namespace App\DTOs;

use App\Domain\Enums\OtpPurpose;

class OtpRequestData
{
    public function __construct(
        public string $mobile,
        public OtpPurpose $purpose,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            mobile: $data['mobile'],
            purpose: OtpPurpose::from($data['purpose']),
        );
    }

    public function toArray(): array
    {
        return [
            'mobile' => $this->mobile,
            'purpose' => $this->purpose->value,
        ];
    }
}
