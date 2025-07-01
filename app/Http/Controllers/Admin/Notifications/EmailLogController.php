<?php

namespace App\Http\Controllers\Admin\Notifications;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailLogController extends Controller
{
    /**
     * Display a listing of email logs.
     */
    public function index(Request $request)
    {
        $query = EmailLog::query();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('to_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('to_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('template_key')) {
            $query->where('template_key', $request->template_key);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get stats
        $stats = [
            'total' => EmailLog::count(),
            'sent' => EmailLog::where('status', EmailLog::STATUS_SENT)->count(),
            'delivered' => EmailLog::where('status', EmailLog::STATUS_DELIVERED)->count(),
            'opened' => EmailLog::where('status', EmailLog::STATUS_OPENED)->count(),
            'failed' => EmailLog::where('status', EmailLog::STATUS_FAILED)->count(),
            'bounced' => EmailLog::where('status', EmailLog::STATUS_BOUNCED)->count(),
        ];

        $logs = $query->latest()
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/Notifications/EmailLogs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'template_key', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display the specified email log.
     */
    public function show(EmailLog $emailLog)
    {
        return Inertia::render('admin/Notifications/EmailLogs/Show', [
            'log' => $emailLog,
        ]);
    }

    /**
     * Resend a failed email
     */
    public function resend(EmailLog $emailLog)
    {
        // This would integrate with your email service
        // For now, we'll just mark it as pending
        if ($emailLog->status === EmailLog::STATUS_FAILED) {
            $emailLog->update([
                'status' => EmailLog::STATUS_PENDING,
                'error_message' => null,
            ]);

            // TODO: Actually resend the email through your email service

            return redirect()->back()->with('success', 'Email queued for resending.');
        }

        return redirect()->back()->with('error', 'Only failed emails can be resent.');
    }
}