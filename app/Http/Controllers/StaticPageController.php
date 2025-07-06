<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\TicketCategory;
use App\Models\Page;

class StaticPageController extends Controller
{
    public function odds()
    {
        return Inertia::render('Odds');
    }

    public function futures()
    {
        return Inertia::render('Futures');
    }

    public function buyOurPicks()
    {
        return Inertia::render('BuyOurPicks');
    }

    public function partnerOffers()
    {
        return Inertia::render('PartnerOffers');
    }

    public function careersJobs()
    {
        $page = Page::where('slug', 'careers-jobs')->first();
        
        if (!$page) {
            abort(404);
        }
        
        // Get active job positions for the form
        $jobPositions = \App\Models\JobPosition::active()->ordered()->get(['id', 'title']);
        
        return Inertia::render('CareersJobs', [
            'page' => $page,
            'jobPositions' => $jobPositions,
        ]);
    }

    public function aboutUs()
    {
        $page = Page::where('slug', 'about-us')->first();
        
        if (!$page) {
            abort(404);
        }
        
        return Inertia::render('AboutUs', [
            'page' => $page,
        ]);
    }
}