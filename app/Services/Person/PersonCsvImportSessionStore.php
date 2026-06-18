<?php

namespace App\Services\Person;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonCsvImportSessionStore
{
    private const CACHE_PREFIX = 'person-csv-import:';

    /** @return array<string, mixed> */
    public function create(int $userId, string $diskPath, int $totalRows): array
    {
        $id = (string) Str::uuid();

        $session = [
            'id' => $id,
            'user_id' => $userId,
            'disk_path' => $diskPath,
            'total' => $totalRows,
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'current_label' => null,
            'status' => 'pending',
            'completed' => false,
        ];

        $this->put($id, $session);

        return $session;
    }

    /** @return array<string, mixed>|null */
    public function get(string $id): ?array
    {
        /** @var array<string, mixed>|null $session */
        $session = Cache::get(self::CACHE_PREFIX.$id);

        return $session;
    }

    /** @param array<string, mixed> $session */
    public function put(string $id, array $session): void
    {
        Cache::put(self::CACHE_PREFIX.$id, $session, now()->addHours(2));
    }

    /** @param array<string, mixed> $session */
    public function markProcessing(array $session): array
    {
        $session['status'] = 'processing';

        $this->put($session['id'], $session);

        return $session;
    }

    /**
     * @param array<string, mixed> $session
     * @param array{processed: int, imported: int, updated: int, skipped: int, errors: list<string>, current_label: string|null, completed: bool} $batch
     */
    public function applyBatch(array $session, array $batch): array
    {
        $session['processed'] += $batch['processed'];
        $session['imported'] += $batch['imported'];
        $session['updated'] += $batch['updated'];
        $session['skipped'] += $batch['skipped'];
        $session['errors'] = array_values(array_merge($session['errors'], $batch['errors']));
        $session['current_label'] = $batch['current_label'];
        $session['completed'] = $batch['completed'];
        $session['status'] = $batch['completed'] ? 'completed' : 'processing';

        $this->put($session['id'], $session);

        if ($batch['completed']) {
            $this->cleanupFile($session['disk_path']);
        }

        return $session;
    }

    public function cleanupFile(string $diskPath): void
    {
        Storage::disk('local')->delete($diskPath);
    }

    public function absolutePath(string $diskPath): string
    {
        return Storage::disk('local')->path($diskPath);
    }
}
