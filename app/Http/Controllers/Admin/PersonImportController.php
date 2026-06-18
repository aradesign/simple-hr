<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Services\Person\PersonCsvImportSessionStore;
use App\Services\Person\PersonnelCsvImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PersonImportController extends Controller
{
    public function __construct(
        private readonly PersonnelCsvImportService $importService,
        private readonly PersonCsvImportSessionStore $sessionStore,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Person::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        $diskPath = $validated['file']->store('imports/persons', 'local');
        $absolutePath = $this->sessionStore->absolutePath($diskPath);

        try {
            $totalRows = $this->importService->countImportableRows($absolutePath);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($diskPath);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        if ($totalRows === 0) {
            Storage::disk('local')->delete($diskPath);

            return response()->json([
                'message' => 'فایل CSV ردیف قابل import ندارد.',
            ], 422);
        }

        $session = $this->sessionStore->create(
            (int) $request->user()->id,
            $diskPath,
            $totalRows,
        );

        return response()->json([
            'import_id' => $session['id'],
            'total' => $session['total'],
        ]);
    }

    public function process(Request $request, string $importId): JsonResponse
    {
        $this->authorize('viewAny', Person::class);

        $session = $this->sessionStore->get($importId);

        if (! $session) {
            return response()->json(['message' => 'نشست import یافت نشد.'], 404);
        }

        if ((int) $session['user_id'] !== (int) $request->user()->id) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 403);
        }

        if ($session['completed']) {
            return response()->json($this->progressPayload($session));
        }

        if ($session['status'] === 'pending') {
            $session = $this->sessionStore->markProcessing($session);
        }

        if (! Storage::disk('local')->exists($session['disk_path'])) {
            return response()->json(['message' => 'فایل import در دسترس نیست.'], 410);
        }

        $batch = $this->importService->importBatch(
            $this->sessionStore->absolutePath($session['disk_path']),
            (int) $session['processed'],
            1,
        );

        $session = $this->sessionStore->applyBatch($session, $batch);

        return response()->json($this->progressPayload($session));
    }

    /** @param array<string, mixed> $session */
    private function progressPayload(array $session): array
    {
        $total = max((int) $session['total'], 1);
        $processed = (int) $session['processed'];

        return [
            'import_id' => $session['id'],
            'status' => $session['status'],
            'total' => (int) $session['total'],
            'processed' => $processed,
            'imported' => (int) $session['imported'],
            'updated' => (int) $session['updated'],
            'skipped' => (int) $session['skipped'],
            'errors' => $session['errors'],
            'current_label' => $session['current_label'],
            'completed' => (bool) $session['completed'],
            'percent' => min(100, (int) round(($processed / $total) * 100)),
        ];
    }
}
