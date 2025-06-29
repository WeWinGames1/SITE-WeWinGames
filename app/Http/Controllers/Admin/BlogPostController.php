<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request)
    {
        $query = Post::with('author:id,name,email');
        
        // Apply filters
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'published':
                    $query->published();
                    break;
                case 'draft':
                    $query->draft();
                    break;
                case 'scheduled':
                    $query->scheduled();
                    break;
            }
        }
        
        if ($request->filled('category')) {
            $query->inCategory($request->category);
        }
        
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('content', 'like', '%' . $request->search . '%')
                    ->orWhere('excerpt', 'like', '%' . $request->search . '%');
            });
        }
        
        $posts = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
        
        return Inertia::render('admin/BlogPosts/Index', [
            'posts' => $posts,
            'filters' => $request->only(['status', 'category', 'search']),
            'categories' => Post::getCategories(),
        ]);
    }
    
    /**
     * Show the form for creating a new blog post.
     */
    public function create()
    {
        return Inertia::render('admin/BlogPosts/Create', [
            'categories' => Post::getCategories(),
            'popularTags' => Post::getPopularTags(),
        ]);
    }
    
    /**
     * Store a newly created blog post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts'),
                'regex:/^[a-z0-9-]+$/'
            ],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:5120', // 5MB max
            'category' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string|max:255',
        ]);
        
        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('posts/featured-images', 'public');
            $validated['featured_image'] = $path;
        }
        
        // Set the author
        $validated['user_id'] = auth()->id();
        
        // Create the post
        $post = Post::create($validated);
        
        return redirect()->route('admin.blog-posts.edit', $post)
            ->with('success', 'Blog post created successfully.');
    }
    
    /**
     * Show the form for editing a blog post.
     */
    public function edit(Post $post)
    {
        return Inertia::render('admin/BlogPosts/Edit', [
            'post' => $post->load('author:id,name,email'),
            'categories' => Post::getCategories(),
            'popularTags' => Post::getPopularTags(),
        ]);
    }
    
    /**
     * Update the specified blog post.
     */
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts')->ignore($post->id),
                'regex:/^[a-z0-9-]+$/'
            ],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:5120', // 5MB max
            'category' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string|max:255',
        ]);
        
        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($post->featured_image && Storage::disk('public')->exists($post->featured_image)) {
                Storage::disk('public')->delete($post->featured_image);
            }
            
            $path = $request->file('featured_image')->store('posts/featured-images', 'public');
            $validated['featured_image'] = $path;
        }
        
        // Update the post
        $post->update($validated);
        
        return redirect()->route('admin.blog-posts.edit', $post)
            ->with('success', 'Blog post updated successfully.');
    }
    
    /**
     * Remove the specified blog post.
     */
    public function destroy(Post $post)
    {
        // Delete featured image if exists
        if ($post->featured_image && Storage::disk('public')->exists($post->featured_image)) {
            Storage::disk('public')->delete($post->featured_image);
        }
        
        $post->delete();
        
        return redirect()->route('admin.blog-posts.index')
            ->with('success', 'Blog post deleted successfully.');
    }
    
    /**
     * Duplicate a blog post.
     */
    public function duplicate(Post $post)
    {
        $newPost = $post->replicate();
        $newPost->title = $post->title . ' (Copy)';
        $newPost->slug = Post::generateUniqueSlug($newPost->title);
        $newPost->is_published = false;
        $newPost->published_at = null;
        $newPost->views_count = 0;
        $newPost->created_at = now();
        $newPost->updated_at = now();
        $newPost->save();
        
        return redirect()->route('admin.blog-posts.edit', $newPost)
            ->with('success', 'Blog post duplicated successfully.');
    }
    
    /**
     * Upload image for rich text editor.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048', // 2MB max
        ]);
        
        $path = $request->file('image')->store('posts/content-images', 'public');
        
        return response()->json([
            'location' => Storage::url($path),
        ]);
    }
    
    /**
     * Get blog statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_posts' => Post::count(),
            'published_posts' => Post::published()->count(),
            'draft_posts' => Post::draft()->count(),
            'scheduled_posts' => Post::scheduled()->count(),
            'total_views' => Post::sum('views_count'),
            'posts_this_month' => Post::whereMonth('created_at', now()->month)->count(),
            'popular_categories' => Post::select('category')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'top_posts' => Post::published()
                ->orderByDesc('views_count')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'views_count']),
        ];
        
        return response()->json($stats);
    }
}