<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['user', 'category', 'assignedTo', 'latestReply', 'potentialUser'])
            ->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $tickets = $query->paginate(20)->withQueryString();
        
        // Get admin users for assignment
        $adminUsers = User::whereHas('roles', function($q) {
            $q->where('name', 'admin');
        })->get(['id', 'name']);

        return Inertia::render('admin/SupportTickets/Index', [
            'tickets' => $tickets,
            'categories' => TicketCategory::all(),
            'adminUsers' => $adminUsers,
            'filters' => $request->only(['status', 'priority', 'category_id', 'assigned_to', 'search']),
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['category', 'user', 'assignedTo', 'replies.user']);

        // Get admin users for assignment
        $adminUsers = User::whereHas('roles', function($q) {
            $q->where('name', 'admin');
        })->get(['id', 'name']);

        return Inertia::render('admin/SupportTickets/Show', [
            'ticket' => $ticket,
            'adminUsers' => $adminUsers,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $ticket->replies()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        // Update ticket status if needed
        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'pending']);
        }

        return redirect()->back()->with('success', 'Reply added successfully.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,pending,resolved,closed',
        ]);

        $ticket->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Ticket status updated.');
    }

    public function updatePriority(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $ticket->update(['priority' => $validated['priority']]);

        return redirect()->back()->with('success', 'Ticket priority updated.');
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update(['assigned_to' => $validated['assigned_to']]);

        return redirect()->back()->with('success', 'Ticket assignment updated.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'exists:support_tickets,id',
            'action' => 'required|in:close,resolve,assign,priority',
            'value' => 'required_if:action,assign,priority',
        ]);

        $tickets = SupportTicket::whereIn('id', $validated['ticket_ids']);

        switch ($validated['action']) {
            case 'close':
                $tickets->update(['status' => 'closed']);
                break;
            case 'resolve':
                $tickets->update(['status' => 'resolved']);
                break;
            case 'assign':
                $tickets->update(['assigned_to' => $validated['value']]);
                break;
            case 'priority':
                $tickets->update(['priority' => $validated['value']]);
                break;
        }

        return redirect()->back()->with('success', 'Tickets updated successfully.');
    }

    public function statistics()
    {
        $stats = [
            'total_tickets' => SupportTicket::count(),
            'open_tickets' => SupportTicket::where('status', 'open')->count(),
            'pending_tickets' => SupportTicket::where('status', 'pending')->count(),
            'resolved_tickets' => SupportTicket::where('status', 'resolved')->count(),
            'closed_tickets' => SupportTicket::where('status', 'closed')->count(),
            'urgent_tickets' => SupportTicket::where('priority', 'urgent')->where('status', '!=', 'closed')->count(),
            'tickets_by_category' => SupportTicket::selectRaw('category_id, COUNT(*) as count')
                ->with('category')
                ->groupBy('category_id')
                ->get(),
            'recent_tickets' => SupportTicket::with(['user', 'category'])
                ->latest()
                ->limit(10)
                ->get(),
        ];

        return response()->json($stats);
    }
}