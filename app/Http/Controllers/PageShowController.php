<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Page;
use Inertia\Inertia;

class PageShowController extends Controller
{
    public function showPage($slug)
    {
        $page = Page::where('slug', $slug)->where('published', true)->firstOrFail();
        return Inertia::render('PageShow', ['page' => $page]);
    }

    public function showLandingPage($slug)
    {
        $page = LandingPage::where('slug', $slug)->where('published', true)->firstOrFail();
        return Inertia::render('LandingPageShow', ['page' => $page]);
    }
}