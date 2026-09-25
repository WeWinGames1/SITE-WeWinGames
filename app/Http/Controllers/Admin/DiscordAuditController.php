<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiscordAuditFixRequest;
use App\Services\DiscordService;
use App\Services\SubscriptionSyncService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DiscordAuditController extends Controller
{
    public function __construct(
        private DiscordService $discord,
        private SubscriptionSyncService $subscriptionSync,
    ) {}

    /**
     * Compare every Discord member's roles against the subscription database
     */
    public function index(): Response
    {
        $configured = $this->discord->isConfigured();

        if ($configured) {
            $this->subscriptionSync->expireManualSubscriptions();
        }

        $audit = $configured ? $this->discord->audit() : null;

        return Inertia::render('admin/DiscordAudit/Index', [
            'configured' => $configured,
            'audit' => $audit,
            'roleLabels' => collect($this->discord->managedRoleIds())
                ->mapWithKeys(fn (string $id): array => [$id => $this->discord->roleLabel($id)]),
        ]);
    }

    /**
     * Re-run the audit server-side and apply the requested set of changes
     */
    public function fix(DiscordAuditFixRequest $request): RedirectResponse
    {
        if (! $this->discord->isConfigured()) {
            return back()->with('error', 'Discord integration is not configured.');
        }

        $this->subscriptionSync->expireManualSubscriptions();
        $audit = $this->discord->audit();

        if ($audit === null) {
            return back()->with('error', 'Could not list Discord members. Enable the "Server Members Intent" for the bot in the Discord developer portal.');
        }

        $scope = $request->validated('scope');
        $result = $this->discord->applyAuditRows($audit[$scope]);

        activity()
            ->causedBy($request->user())
            ->withProperties(['scope' => $scope, 'result' => $result])
            ->log('Admin applied Discord role audit');

        $message = "Updated {$result['fixed']} Discord member(s).";

        return $result['failed'] > 0
            ? back()->with('error', $message." {$result['failed']} failed — check the logs.")
            : back()->with('success', $message);
    }
}
