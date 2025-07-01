<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\TicketCategory;

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
        $resumeCategory = TicketCategory::where('slug', 'resume')->first();
        
        return Inertia::render('CareersJobs', [
            'resumeCategoryId' => $resumeCategory ? $resumeCategory->id : null,
        ]);
    }

    public function aboutUs()
    {
        return Inertia::render('AboutUs');
    }
}