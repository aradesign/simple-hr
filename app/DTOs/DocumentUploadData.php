<?php

namespace App\DTOs;

use App\Domain\Enums\DocumentType;
use Illuminate\Http\UploadedFile;

class DocumentUploadData
{
    public function __construct(
        public int $personId,
        public DocumentType $type,
        public string $title,
        public UploadedFile $file,
        public ?string $expiresAt = null,
        public ?string $notes = null,
        public ?int $uploadedBy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            personId: $data['person_id'],
            type: DocumentType::from($data['type']),
            title: $data['title'],
            file: $data['file'],
            expiresAt: $data['expires_at'] ?? null,
            notes: $data['notes'] ?? null,
            uploadedBy: $data['uploaded_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'person_id' => $this->personId,
            'type' => $this->type->value,
            'title' => $this->title,
            'expires_at' => $this->expiresAt,
            'notes' => $this->notes,
            'uploaded_by' => $this->uploadedBy,
        ], fn ($value) => $value !== null);
    }
}
