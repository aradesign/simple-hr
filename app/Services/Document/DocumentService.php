<?php

namespace App\Services\Document;

use App\DTOs\DocumentUploadData;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function upload(DocumentUploadData $data, ?Document $existingDocument = null): Document
    {
        return DB::transaction(function () use ($data, $existingDocument) {
            $document = $existingDocument ?? Document::query()->create([
                'person_id' => $data->personId,
                'type' => $data->type,
                'title' => $data->title,
                'expires_at' => $data->expiresAt,
                'is_active' => true,
            ]);

            if ($existingDocument && $data->title !== $existingDocument->title) {
                $document->update(['title' => $data->title]);
            }

            if ($data->expiresAt) {
                $document->update(['expires_at' => $data->expiresAt]);
            }

            $nextVersion = ($document->versions()->max('version_number') ?? 0) + 1;
            $storedPath = $data->file->store("documents/{$document->person_id}/{$document->id}", 'local');

            DocumentVersion::query()->create([
                'document_id' => $document->id,
                'version_number' => $nextVersion,
                'file_path' => $storedPath,
                'file_name' => $data->file->getClientOriginalName(),
                'mime_type' => $data->file->getMimeType(),
                'file_size' => $data->file->getSize(),
                'uploaded_by' => $data->uploadedBy,
                'uploaded_at' => now(),
                'notes' => $data->notes,
            ]);

            return $document->fresh(['latestVersion', 'versions']);
        });
    }

    public function delete(Document $document): void
    {
        DB::transaction(function () use ($document) {
            foreach ($document->versions as $version) {
                Storage::disk('local')->delete($version->file_path);
            }

            $document->delete();
        });
    }

    public function deactivate(Document $document): Document
    {
        $document->update(['is_active' => false]);

        return $document->fresh();
    }
}
