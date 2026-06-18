<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Enums\HrTicketStatus;
use App\Http\Controllers\Controller;
use App\Models\HrTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrTicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = HrTicket::query()
            ->with(['person', 'assignee'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(HrTicket $ticket): View
    {
        $ticket->load(['person', 'assignee']);
        $hrUsers = User::query()->where('hr_access', true)->orderBy('name')->get();

        return view('admin.tickets.show', compact('ticket', 'hrUsers'));
    }

    public function update(Request $request, HrTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'hr_reply' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->update([
            'status' => HrTicketStatus::from($validated['status']),
            'hr_reply' => $validated['hr_reply'] ?? $ticket->hr_reply,
            'assigned_to' => $validated['assigned_to'] ?? $ticket->assigned_to,
            'replied_at' => filled($validated['hr_reply'] ?? null) ? now() : $ticket->replied_at,
        ]);

        return back()->with('success', 'تیکت به‌روزرسانی شد.');
    }
}
