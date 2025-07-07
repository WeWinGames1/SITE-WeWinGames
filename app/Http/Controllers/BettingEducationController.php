<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;

class BettingEducationController extends Controller
{
    public function __invoke()
    {
        $posts = Post::published()
            ->inCategory('betting-education')
            ->with('author:id,name')
            ->orderBy('published_at', 'desc')
            ->get();

        return Inertia::render('BettingEducation', [
            'posts' => $posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'featured_image' => $post->featured_image,
                    'featured_image_url' => $post->featured_image_url,
                    'published_at' => $post->published_at,
                    'reading_time' => $post->reading_time,
                    'author' => $post->author,
                    'views_count' => $post->views_count,
                ];
            }),
        ]);
    }
}
