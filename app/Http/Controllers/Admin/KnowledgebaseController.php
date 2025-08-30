<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgebaseArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgebaseController extends Controller
{
    public function index()
    {
        $articles = KnowledgebaseArticle::active()
            ->orderBy('type')
            ->orderBy('order')
            ->get()
            ->groupBy('type');

        return Inertia::render('admin/Knowledgebase/Index', [
            'articles' => $articles,
        ]);
    }

    public function getForPage(Request $request)
    {
        // Check if user is admin
        if (! $request->user() || ! $request->user()->hasRole('admin')) {
            return response()->json([
                'article' => null,
                'error' => 'Unauthorized',
            ], 403);
        }

        $pageIdentifier = $request->input('page_identifier');

        $article = KnowledgebaseArticle::active()
            ->forPage($pageIdentifier)
            ->first();

        return response()->json([
            'article' => $article,
        ]);
    }
}
