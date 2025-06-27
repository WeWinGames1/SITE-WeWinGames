<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use App\Http\Resources\UserResource;
use App\Services\BetService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $sharedData = [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? new UserResource($request->user()) : null,
                'isSubscribed' => $request->user() ? $request->user()->subscribed() : false,
                'isAdmin' => $request->user() ? $request->user()->hasRole('admin') : false,
            ],
        ];

        // Include bets only if the user is an admin
        if ($request->user() && $request->user()->hasRole('admin')) {
            $betService = app(BetService::class);
            $sharedData['bets'] = $betService->getAllBets();
            $sharedData['adminPages'] = \App\Models\Page::orderBy('title')->get(['id', 'title', 'slug']);
        }

        return array_merge($sharedData, [
            'stripePrices' => [
                'gold_monthly' => env('GOLD_MONTHLY'),
                'silver_monthly' => env('SILVER_MONTHLY'),
                'platinum_monthly' => env('PLATINUM_MONTHLY'),
                'gold_weekly' => env('GOLD_WEEKLY'),
                'silver_weekly' => env('SILVER_WEEKLY'),
                'platinum_weekly' => env('PLATINUM_WEEKLY'),
                'gold_daily' => env('GOLD_DAILY'),
                'silver_daily' => env('SILVER_DAILY'),
                'platinum_daily' => env('PLATINUM_DAILY'),
            ],
        ]);
    }
}
