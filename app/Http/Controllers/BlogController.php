<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display the blog index page.
     */
    public function index(Request $request)
    {
        $query = Post::published()
            ->with('author:id,name')
            ->orderBy('published_at', 'desc');
        
        // Filter by category
        if ($request->filled('category')) {
            $query->inCategory($request->category);
        }
        
        // Filter by tag
        if ($request->filled('tag')) {
            $query->withTag($request->tag);
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('content', 'like', '%' . $request->search . '%')
                    ->orWhere('excerpt', 'like', '%' . $request->search . '%');
            });
        }
        
        $posts = $query->paginate(12)->withQueryString();
        
        return Inertia::render('blog/Index', [
            'posts' => $posts,
            'categories' => Post::getCategories(),
            'popularTags' => Post::getPopularTags(),
            'filters' => $request->only(['category', 'tag', 'search']),
        ]);
    }
    
    /**
     * Display a single blog post.
     */
    public function show(Post $post)
    {
        // Only show published posts
        if (!$post->is_published || ($post->published_at && $post->published_at->isFuture())) {
            abort(404);
        }
        
        // Increment view count
        $post->incrementViewCount();
        
        // Get related posts
        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                $query->where('category', $post->category)
                    ->orWhere(function ($q) use ($post) {
                        foreach ($post->tags as $tag) {
                            $q->orWhereJsonContains('tags', $tag);
                        }
                    });
            })
            ->with('author:id,name')
            ->limit(3)
            ->orderBy('published_at', 'desc')
            ->get();
        
        return Inertia::render('blog/Show', [
            'post' => $post->load('author:id,name'),
            'relatedPosts' => $relatedPosts,
        ]);
    }
    // Legacy static blog routes
    public function typesOfBets()
    {
        return Inertia::render('blog/BetsWagersEducation');
    }

    public function bettingPredictionsExplained()
    {
        return Inertia::render('blog/BettingPredictionsExplained');
    }

    public function moneyManagement()
    {
        return Inertia::render('blog/MoneyManagement');
    }

    public function whereIsOnlineSportsBettingBiggest()
    {
        return Inertia::render('blog/WhereIsOnlineSportsBettingBiggest');
    }

    public function whyWasAmericaLate()
    {
        return Inertia::render('blog/WhyWasAmericaLate');
    }

    public function canBettingBeProfitable()
    {
        return Inertia::render('blog/CanBettingBeProfitable');
    }

    public function isBettingRiskierThanStockMarket()
    {
        return Inertia::render('blog/IsBettingRiskierThanStockMarket');
    }

    public function sportbooksEarnProfits()
    {
        return Inertia::render('blog/SportbooksEarnProfits');
    }

    public function howToBecomeMoreProfitable()
    {
        return Inertia::render('blog/HowToBecomeMoreProfitable');
    }

    public function bestBettingSites()
    {
        return Inertia::render('blog/BestBettingSites');
    }

    public function bestBettingPicksTricks()
    {
        return Inertia::render('blog/BestBettingPicksTricks');
    }

    public function areParlaysAGoodBet()
    {
        return Inertia::render('blog/AreParlaysAGoodBet');
    }

    public function statisticsVersusEmotionInBetting()
    {
        return Inertia::render('blog/StatisticsVersusEmotionInBetting');
    }

    public function inPlayFastestMaturingAreas()
    {
        return Inertia::render('blog/InPlayFastestMaturingAreas');
    }

    public function bestSportsBettingPicksMeasures()
    {
        return Inertia::render('blog/BestSportsBettingPicksMeasures');
    }

    public function betPredictions()
    {
        return Inertia::render('blog/BetPredictions');
    }

    public function importanceOfLineShopping()
    {
        return Inertia::render('blog/ImportanceOfLineShopping');
    }

    public function howToBetOnBaseball()
    {
        return Inertia::render('blog/HowToBetOnBaseball');
    }

    public function bestNHLBettingTips()
    {
        return Inertia::render('blog/BestNHLBettingTips');
    }

    public function howToBetOnFootball()
    {
        return Inertia::render('blog/HowToBetOnFootball');
    }

    public function howToBetOnSoccer()
    {
        return Inertia::render('blog/HowToBetOnSoccer');
    }

    public function golfBettingTips()
    {
        return Inertia::render('blog/GolfBettingTips');
    }
}