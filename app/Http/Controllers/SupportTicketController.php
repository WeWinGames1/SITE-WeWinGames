<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = auth()->user()->supportTickets()
            ->with(['category', 'latestReply'])
            ->latest()
            ->get();

        return Inertia::render('support/Index', [
            'tickets' => $tickets,
            'categories' => TicketCategory::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('support/Create', [
            'categories' => TicketCategory::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:ticket_categories,id',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $ticket = auth()->user()->supportTickets()->create([
            'ticket_number' => 'TICKET-' . strtoupper(Str::random(8)),
            'category_id' => $validated['category_id'],
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        return redirect("/support/tickets/{$ticket->id}")
            ->with('success', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $ticket)
    {
        // Ensure user can only view their own tickets
        if ($ticket->user_id !== auth()->id() && !auth()->user()->is_admin) {
            abort(403);
        }

        $ticket->load(['category', 'user', 'replies.user']);

        return Inertia::render('support/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        // Ensure user can only reply to their own tickets
        if ($ticket->user_id !== auth()->id() && !auth()->user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $ticket->replies()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'is_internal' => false,
        ]);

        // Update ticket status if it was resolved/closed
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
        }

        return redirect()->back()->with('success', 'Reply added successfully.');
    }

    public function close(SupportTicket $ticket)
    {
        // Ensure user can only close their own tickets
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->update(['status' => 'closed']);

        return redirect()->back()->with('success', 'Ticket closed successfully.');
    }

    public function reopen(SupportTicket $ticket)
    {
        // Ensure user can only reopen their own tickets
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->update(['status' => 'open']);

        return redirect()->back()->with('success', 'Ticket reopened successfully.');
    }
}