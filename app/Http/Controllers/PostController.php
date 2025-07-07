<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request)
    {
        $posts = Post::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($request->get('category'), function ($query, $category) {
                $query->where('category', $category);
            })
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return Inertia::render('blog/Index', [
            'posts' => $posts,
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    /**
     * Display the specified blog post.
     */
    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        // Increment view count
        $post->increment('views_count');

        // Get related posts
        $relatedPosts = Post::where('id', '!=', $post->id)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($post->category, function ($query) use ($post) {
                $query->where('category', $post->category);
            })
            ->limit(3)
            ->orderBy('published_at', 'desc')
            ->get();

        return Inertia::render('blog/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
