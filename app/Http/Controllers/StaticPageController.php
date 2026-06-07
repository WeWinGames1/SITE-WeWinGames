<?php

namespace App\Http\Controllers;

use App\Models\Page;
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
        // forOffersPage() already applies active() and orderBy('sort_order')
        $offers = \App\Models\PartnerOffer::forOffersPage()
            ->with('discountCode:id,code')
            ->get()
            ->map(function ($offer) {
                $offer->click_url = $offer->getClickUrl();

                return $offer;
            });

        // Group offers by type
        $sportsbooks = $offers->where('type', 'sportsbook')->values();
        $predictions = $offers->where('type', 'prediction')->values();
        $casinos = $offers->where('type', 'casino')->values();

        return Inertia::render('PartnerOffers', [
            'offers' => $offers,
            'sportsbooks' => $sportsbooks,
            'predictions' => $predictions,
            'casinos' => $casinos,
        ]);
    }

    public function careersJobs()
    {
        $page = Page::where('slug', 'careers-jobs')->first();

        if (! $page) {
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

        if (! $page) {
            abort(404);
        }

        return Inertia::render('AboutUs', [
            'page' => $page,
        ]);
    }
}
