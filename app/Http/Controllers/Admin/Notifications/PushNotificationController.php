<?php

namespace App\Http\Controllers\Admin\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PushNotificationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use NotificationChannels\WebPush\PushChannel;
use App\Notifications\AdminPushNotification;

class PushNotificationController extends Controller
{
    /**
     * Display the push notification history page.
     */
    public function index(Request $request)
    {
        $notifications = PushNotificationLog::with('sender:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/Notifications/PushNotifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new push notification.
     */
    public function create()
    {
        $stats = [
            'total_users' => User::count(),
            'push_enabled' => User::whereJsonContains('notification_preferences->push', true)->count(),
            'subscribers_by_tier' => User::whereNotNull('notification_preferences')
                ->whereJsonContains('notification_preferences->push', true)
                ->whereHas('subscriptions', function ($query) {
                    $query->active();
                })
                ->get()
                ->groupBy(function ($user) {
                    return $user->getCurrentTier() ?? 'free';
                })
                ->map->count(),
        ];

        return Inertia::render('admin/Notifications/PushNotifications/Create', [
            'stats' => $stats,
        ]);
    }

    /**
     * Send a push notification to selected users.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'url' => 'nullable|url',
            'icon' => 'nullable|string',
            'recipients' => 'required|in:all,push_enabled,tier',
            'tier' => 'required_if:recipients,tier|in:silver,gold,platinum',
        ]);

        // Build query based on recipients
        $query = User::query();

        switch ($validated['recipients']) {
            case 'push_enabled':
                $query->whereJsonContains('notification_preferences->push', true);
                break;
            
            case 'tier':
                $query->whereJsonContains('notification_preferences->push', true)
                    ->where(function ($q) use ($validated) {
                        // Check for override tier
                        $q->where('override_tier', $validated['tier'])
                            ->where(function ($subQ) {
                                $subQ->where('is_ambassador', true)
                                    ->orWhere('is_gifted', true)
                                    ->orWhere('admin_override', true);
                            })
                            ->where(function ($subQ) {
                                $subQ->whereNull('override_expiry')
                                    ->orWhere('override_expiry', '>=', now());
                            });
                        
                        // Or check Stripe subscription via the StripeProduct table
                        $q->orWhereHas('subscriptions', function ($subQ) use ($validated) {
                            $subQ->active()
                                ->whereIn('stripe_price', function ($priceQuery) use ($validated) {
                                    $priceQuery->select('stripe_price_id')
                                        ->from('stripe_products')
                                        ->where('tier', $validated['tier'])
                                        ->where('is_active', true);
                                });
                        });
                    });
                break;
            
            case 'all':
            default:
                // All users with push enabled
                $query->whereJsonContains('notification_preferences->push', true);
                break;
        }

        $users = $query->get();
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $user->notify(new AdminPushNotification(
                    $validated['title'],
                    $validated['body'],
                    $validated['url'] ?? null,
                    $validated['icon'] ?? null
                ));
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                \Log::error('Failed to send push notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log the notification
        PushNotificationLog::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'url' => $validated['url'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'recipients_type' => $validated['recipients'],
            'tier' => $validated['tier'] ?? null,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sent_by' => $request->user()->id,
            'metadata' => [
                'total_recipients' => $users->count(),
            ],
        ]);

        return redirect()->route('admin.notifications.push.index')
            ->with('success', "Push notification sent to {$sent} users" . ($failed > 0 ? " ({$failed} failed)" : ''));
    }

    /**
     * Test push notification to admin.
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'url' => 'nullable|url',
            'icon' => 'nullable|string',
        ]);

        try {
            $request->user()->notify(new AdminPushNotification(
                $validated['title'],
                $validated['body'],
                $validated['url'] ?? null,
                $validated['icon'] ?? null
            ));

            return back()->with('success', 'Test notification sent to your device');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test notification: ' . $e->getMessage());
        }
    }
}