<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\DocumentUploadData;
use App\Domain\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Person;
use App\Services\Document\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::query()
            ->with(['person', 'latestVersion'])
            ->when($request->filled('person_id'), fn ($query) => $query->where('person_id', $request->integer('person_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.documents.index', [
            'documents' => $documents,
            'documentTypes' => DocumentType::cases(),
            'persons' => Person::query()
                ->inPersonnelRoster()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'mobile']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $validated = $request->validate([
            'person_id' => ['required', 'exists:persons,id'],
            'type' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'document_id' => ['nullable', 'exists:documents,id'],
        ]);

        $validated['uploaded_by'] = $request->user()->id;

        $existingDocument = isset($validated['document_id'])
            ? Document::query()->findOrFail($validated['document_id'])
            : null;

        $this->documentService->upload(
            DocumentUploadData::fromArray($validated),
            $existingDocument,
        );

        if ($request->filled('redirect_tab') && $request->filled('person_id')) {
            return redirect()
                ->route('admin.persons.show', [
                    'person' => $request->integer('person_id'),
                    'tab' => $request->input('redirect_tab'),
                ])
                ->with('success', 'سند با موفقیت بارگذاری شد.');
        }

        return back()->with('success', 'سند با موفقیت بارگذاری شد.');
    }

    public function downloadVersion(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->authorize('download', $document);

        abort_unless($version->document_id === $document->id, 404);

        abort_unless(Storage::disk('local')->exists($version->file_path), 404);

        return Storage::disk('local')->download($version->file_path, $version->file_name);
    }
}
