<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesPortalPerson;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use ResolvesPortalPerson;

    public function index(Request $request): View
    {
        $person = $this->portalPerson($request);

        $documents = Document::query()
            ->where('person_id', $person->id)
            ->active()
            ->with('latestVersion')
            ->latest()
            ->get();

        return view('portal.documents.index', compact('person', 'documents'));
    }

    public function download(Request $request, Document $document, DocumentVersion $version): StreamedResponse
    {
        $person = $this->portalPerson($request);

        abort_unless($document->person_id === $person->id, 403);
        abort_unless($version->document_id === $document->id, 404);
        abort_unless(Storage::disk('local')->exists($version->file_path), 404);

        return Storage::disk('local')->download($version->file_path, $version->file_name);
    }
}
