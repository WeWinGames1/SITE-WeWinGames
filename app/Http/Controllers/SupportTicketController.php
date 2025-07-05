<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\CloudflareService;
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

    public function requestClose(SupportTicket $ticket)
    {
        // Ensure user can only request to close their own tickets
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->update(['status' => 'user-requests-close']);

        return redirect()->back()->with('success', 'Your request to close this ticket has been submitted. Our support team will review it shortly.');
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

    /**
     * Show public support form (for both guests and authenticated users)
     */
    public function publicCreate()
    {
        return Inertia::render('support/PublicCreate', [
            'categories' => TicketCategory::all(),
            'isAuthenticated' => auth()->check(),
        ]);
    }

    /**
     * Store support ticket from public form
     */
    public function publicStore(Request $request, CloudflareService $cloudflareService)
    {
        // Different validation rules for guests vs authenticated users
        $rules = [
            'category_id' => 'required|exists:ticket_categories,id',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
        ];

        // If user is not authenticated, require guest information
        if (!auth()->check()) {
            $rules['first_name'] = 'required|string|max:255';
            $rules['last_name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|max:255';
        }

        // Add Turnstile validation if enabled
        if (config('services.turnstile.enabled')) {
            $rules['cf-turnstile-response'] = 'required|string';
        }

        $validated = $request->validate($rules);

        // Verify Turnstile if enabled
        if (config('services.turnstile.enabled')) {
            $turnstileResult = $cloudflareService->verifyTurnstile(
                $request->input('cf-turnstile-response'),
                $request->ip()
            );

            if (!$turnstileResult['success']) {
                return back()->withErrors([
                    'cf-turnstile-response' => 'Security verification failed. Please try again.',
                ])->withInput();
            }
        }

        // Check if email belongs to existing user
        $existingUser = null;
        $isGuestSubmission = !auth()->check();
        
        if ($isGuestSubmission) {
            $existingUser = User::where('email', $validated['email'])->first();
        }

        // Create ticket
        $ticketData = [
            'ticket_number' => 'TICKET-' . strtoupper(Str::random(8)),
            'category_id' => $validated['category_id'],
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'user_id' => auth()->id(),
            'is_guest_submission' => $isGuestSubmission,
        ];

        // Add guest information if not authenticated
        if ($isGuestSubmission) {
            $ticketData['guest_name'] = $validated['first_name'] . ' ' . $validated['last_name'];
            $ticketData['guest_email'] = $validated['email'];
            $ticketData['potential_user_id'] = $existingUser?->id;
        }

        $ticket = SupportTicket::create($ticketData);

        // Return appropriate response
        if (auth()->check()) {
            return redirect("/support/tickets/{$ticket->id}")
                ->with('success', 'Support ticket created successfully.');
        } else {
            return Inertia::render('support/GuestTicketCreated', [
                'ticketNumber' => $ticket->ticket_number,
                'email' => $validated['email'],
            ]);
        }
    }
}