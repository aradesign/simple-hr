<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Enums\HrTicketStatus;
use App\Http\Controllers\Concerns\ResolvesPortalPerson;
use App\Http\Controllers\Controller;
use App\Models\HrTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    use ResolvesPortalPerson;

    public function index(Request $request): View
    {
        $person = $this->portalPerson($request);

        $tickets = HrTicket::query()
            ->where('person_id', $person->id)
            ->latest()
            ->paginate(15);

        return view('portal.tickets.index', compact('person', 'tickets'));
    }

    public function create(Request $request): View
    {
        $person = $this->portalPerson($request);

        return view('portal.tickets.create', compact('person'));
    }

    public function store(Request $request): RedirectResponse
    {
        $person = $this->portalPerson($request);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = HrTicket::query()->create([
            'person_id' => $person->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => HrTicketStatus::Open,
        ]);

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('success', 'تیکت شما ثبت شد و به زودی بررسی می‌شود.');
    }

    public function show(Request $request, HrTicket $ticket): View
    {
        $person = $this->portalPerson($request);

        abort_unless($ticket->person_id === $person->id, 403);

        return view('portal.tickets.show', compact('person', 'ticket'));
    }
}
