<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

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
        return Inertia::render('CareersJobs');
    }

    public function aboutUs()
    {
        return Inertia::render('AboutUs');
    }
}