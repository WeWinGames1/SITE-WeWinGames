<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BettingEducationController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Post::published()
            ->with('author:id,name')
            ->orderBy('published_at', 'desc');

        // Start with betting-education category by default
        if (! $request->filled('category') && ! $request->filled('tag') && ! $request->filled('search')) {
            $query->inCategory('betting-education');
        }

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
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('content', 'like', '%'.$request->search.'%')
                    ->orWhere('excerpt', 'like', '%'.$request->search.'%');
            });
        }

        $posts = $query->paginate(12)->withQueryString();

        // Transform posts to include featured_image_url
        $posts->through(function ($post) {
            $postArray = $post->toArray();
            $postArray['featured_image_url'] = $post->featured_image_url;

            return $postArray;
        });

        return Inertia::render('blog/Index', [
            'posts' => $posts,
            'categories' => Post::getCategories(),
            'popularTags' => Post::getPopularTags(),
            'filters' => $request->only(['category', 'tag', 'search']),
            'customHeader' => [
                'title' => 'Betting Education',
                'subtitle' => 'Master the fundamentals of sports betting with our comprehensive educational resources',
                'description' => 'When it comes to learning about sports betting it is hard to know who to trust. Often what may look like educational content is just a guise to funnel your attention to a sponsoring sportsbook. At We Win Games, we believe a more knowledgeable sports bettor is good for everyone. It provides a richer and more sustainable market for all to enjoy.',
            ],
        ]);
    }
}
