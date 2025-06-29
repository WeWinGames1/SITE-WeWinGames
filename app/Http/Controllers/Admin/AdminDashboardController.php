<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Post;
use App\Models\User;
use App\Models\DiscountCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Get statistics
        $stats = $this->getStatistics();
        $charts = $this->getChartData();
        $recentActivity = $this->getRecentActivity();
        $systemHealth = $this->getSystemHealth();

        return Inertia::render('admin/Dashboard', [
            'stats' => $stats,
            'charts' => $charts,
            'recentActivity' => $recentActivity,
            'systemHealth' => $systemHealth,
        ]);
    }

    /**
     * Get dashboard statistics.
     */
    private function getStatistics()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // User statistics
        $totalUsers = User::count();
        $activeSubscribers = User::whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active');
        })->count();
        $newUsersToday = User::whereDate('created_at', $today)->count();
        $newUsersThisMonth = User::where('created_at', '>=', $thisMonth)->count();

        // Revenue statistics
        $mrr = DB::table('subscriptions')
            ->where('stripe_status', 'active')
            ->join('subscription_items', 'subscriptions.id', '=', 'subscription_items.subscription_id')
            ->sum('subscription_items.quantity') * 100; // Assuming price is in cents

        $revenueThisMonth = DB::table('subscriptions')
            ->where('created_at', '>=', $thisMonth)
            ->where('stripe_status', 'active')
            ->count() * 100; // Simplified calculation

        // Betting statistics
        $totalBets = Bet::count();
        $betsToday = Bet::whereDate('created_at', $today)->count();
        $activeBets = Bet::where('status', 'pending')->count();
        $winRate = Bet::where('status', 'won')->count() / max(Bet::whereIn('status', ['won', 'lost'])->count(), 1) * 100;

        // Content statistics
        $totalPosts = Post::count();
        $publishedPosts = Post::published()->count();
        $postsThisMonth = Post::where('created_at', '>=', $thisMonth)->count();
        $totalPageViews = Post::sum('views_count');

        // Discount statistics
        $activeDiscounts = DiscountCode::where('is_active', true)->valid()->count();
        $discountUsage = DB::table('discount_redemptions')
            ->where('created_at', '>=', $thisMonth)
            ->count();

        return [
            'users' => [
                'total' => $totalUsers,
                'active_subscribers' => $activeSubscribers,
                'new_today' => $newUsersToday,
                'new_this_month' => $newUsersThisMonth,
                'growth_rate' => $this->calculateGrowthRate('users', $thisMonth, $lastMonth),
            ],
            'revenue' => [
                'mrr' => $mrr / 100, // Convert to dollars
                'this_month' => $revenueThisMonth / 100,
                'growth_rate' => $this->calculateGrowthRate('revenue', $thisMonth, $lastMonth),
            ],
            'bets' => [
                'total' => $totalBets,
                'today' => $betsToday,
                'active' => $activeBets,
                'win_rate' => round($winRate, 1),
            ],
            'content' => [
                'total_posts' => $totalPosts,
                'published' => $publishedPosts,
                'this_month' => $postsThisMonth,
                'page_views' => $totalPageViews,
            ],
            'discounts' => [
                'active' => $activeDiscounts,
                'used_this_month' => $discountUsage,
            ],
        ];
    }

    /**
     * Get chart data for dashboard visualizations.
     */
    private function getChartData()
    {
        $days = 30;
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays($days - 1);

        // User growth chart
        $userGrowth = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Revenue chart
        $revenueData = DB::table('subscriptions')
            ->selectRaw('DATE(created_at) as date, COUNT(*) * 100 as revenue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('stripe_status', 'active')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('revenue', 'date');

        // Bet activity chart
        $betActivity = Bet::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Fill in missing dates
        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $dates[] = [
                'date' => $date,
                'users' => $userGrowth->get($date, 0),
                'revenue' => ($revenueData->get($date, 0) / 100), // Convert to dollars
                'bets' => $betActivity->get($date, 0),
            ];
        }

        // Subscription tier breakdown
        $tierBreakdown = User::whereHas('subscriptions', function ($query) {
                $query->where('stripe_status', 'active');
            })
            ->select('override_tier as tier', DB::raw('COUNT(*) as count'))
            ->whereNotNull('override_tier')
            ->groupBy('tier')
            ->get()
            ->map(function ($item) {
                return [
                    'tier' => $item->tier ?: 'Unknown',
                    'count' => $item->count,
                ];
            });

        return [
            'daily_metrics' => $dates,
            'tier_breakdown' => $tierBreakdown,
        ];
    }

    /**
     * Get recent activity for the dashboard.
     */
    private function getRecentActivity()
    {
        $activities = collect();

        // Recent user registrations
        $recentUsers = User::latest()->limit(5)->get()->map(function ($user) {
            return [
                'type' => 'user_registration',
                'message' => "New user registered: {$user->name}",
                'time' => $user->created_at,
                'icon' => 'UserPlusIcon',
                'color' => 'blue',
            ];
        });

        // Recent bets
        $recentBets = Bet::with('user')->latest()->limit(5)->get()->map(function ($bet) {
            return [
                'type' => 'bet_placed',
                'message' => "Bet placed by {$bet->user->name}",
                'time' => $bet->created_at,
                'icon' => 'CurrencyDollarIcon',
                'color' => 'green',
            ];
        });

        // Recent posts
        $recentPosts = Post::with('author')->latest()->limit(5)->get()->map(function ($post) {
            return [
                'type' => 'post_published',
                'message' => "{$post->author->name} published: {$post->title}",
                'time' => $post->created_at,
                'icon' => 'DocumentTextIcon',
                'color' => 'purple',
            ];
        });

        // Combine and sort by time
        return $activities
            ->merge($recentUsers)
            ->merge($recentBets)
            ->merge($recentPosts)
            ->sortByDesc('time')
            ->take(10)
            ->values();
    }

    /**
     * Get system health metrics.
     */
    private function getSystemHealth()
    {
        // Database size
        $dbSize = $this->getDatabaseSize();
        
        // Storage usage
        $storageUsed = $this->getStorageUsage();
        $storageTotal = disk_total_space(storage_path());
        $storagePercentage = ($storageUsed / $storageTotal) * 100;

        // Queue status
        $failedJobs = DB::table('failed_jobs')->count();
        $pendingJobs = DB::table('jobs')->count();

        // Error logs
        $recentErrors = 0;
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $log = file_get_contents($logFile);
            $today = Carbon::today()->format('Y-m-d');
            $recentErrors = substr_count($log, $today . ' ') && substr_count($log, 'ERROR');
        }

        return [
            'database' => [
                'size' => $this->formatBytes($dbSize),
                'status' => 'healthy',
            ],
            'storage' => [
                'used' => $this->formatBytes($storageUsed),
                'total' => $this->formatBytes($storageTotal),
                'percentage' => round($storagePercentage, 1),
                'status' => $storagePercentage > 90 ? 'warning' : 'healthy',
            ],
            'queue' => [
                'failed' => $failedJobs,
                'pending' => $pendingJobs,
                'status' => $failedJobs > 10 ? 'error' : 'healthy',
            ],
            'errors' => [
                'today' => $recentErrors,
                'status' => $recentErrors > 50 ? 'error' : ($recentErrors > 10 ? 'warning' : 'healthy'),
            ],
        ];
    }

    /**
     * Calculate growth rate between two periods.
     */
    private function calculateGrowthRate($type, $currentPeriod, $previousPeriod)
    {
        $current = 0;
        $previous = 0;

        switch ($type) {
            case 'users':
                $current = User::where('created_at', '>=', $currentPeriod)->count();
                $previous = User::whereBetween('created_at', [$previousPeriod, $currentPeriod])->count();
                break;
            case 'revenue':
                // Simplified calculation
                $current = DB::table('subscriptions')
                    ->where('created_at', '>=', $currentPeriod)
                    ->where('stripe_status', 'active')
                    ->count();
                $previous = DB::table('subscriptions')
                    ->whereBetween('created_at', [$previousPeriod, $currentPeriod])
                    ->where('stripe_status', 'active')
                    ->count();
                break;
        }

        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get database size.
     */
    private function getDatabaseSize()
    {
        try {
            if (config('database.default') === 'mysql') {
                $result = DB::select('SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = ?', [config('database.connections.mysql.database')]);
                return $result[0]->size ?? 0;
            } elseif (config('database.default') === 'sqlite') {
                $path = config('database.connections.sqlite.database');
                return file_exists($path) ? filesize($path) : 0;
            }
        } catch (\Exception $e) {
            return 0;
        }

        return 0;
    }

    /**
     * Get storage usage.
     */
    private function getStorageUsage()
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(storage_path())) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}