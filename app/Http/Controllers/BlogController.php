<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class BlogController extends Controller
{
    public function typesOfBets()
    {
        return Inertia::render('blog/BetsWagersEducation');
    }

    public function bettingPredictionsExplained()
    {
        return Inertia::render('blog/BettingPredictionsExplained');
    }

    public function moneyManagement()
    {
        return Inertia::render('blog/MoneyManagement');
    }

    public function whereIsOnlineSportsBettingBiggest()
    {
        return Inertia::render('blog/WhereIsOnlineSportsBettingBiggest');
    }

    public function whyWasAmericaLate()
    {
        return Inertia::render('blog/WhyWasAmericaLate');
    }

    public function canBettingBeProfitable()
    {
        return Inertia::render('blog/CanBettingBeProfitable');
    }

    public function isBettingRiskierThanStockMarket()
    {
        return Inertia::render('blog/IsBettingRiskierThanStockMarket');
    }

    public function sportbooksEarnProfits()
    {
        return Inertia::render('blog/SportbooksEarnProfits');
    }

    public function howToBecomeMoreProfitable()
    {
        return Inertia::render('blog/HowToBecomeMoreProfitable');
    }

    public function bestBettingSites()
    {
        return Inertia::render('blog/BestBettingSites');
    }

    public function bestBettingPicksTricks()
    {
        return Inertia::render('blog/BestBettingPicksTricks');
    }

    public function areParlaysAGoodBet()
    {
        return Inertia::render('blog/AreParlaysAGoodBet');
    }

    public function statisticsVersusEmotionInBetting()
    {
        return Inertia::render('blog/StatisticsVersusEmotionInBetting');
    }

    public function inPlayFastestMaturingAreas()
    {
        return Inertia::render('blog/InPlayFastestMaturingAreas');
    }

    public function bestSportsBettingPicksMeasures()
    {
        return Inertia::render('blog/BestSportsBettingPicksMeasures');
    }

    public function betPredictions()
    {
        return Inertia::render('blog/BetPredictions');
    }

    public function importanceOfLineShopping()
    {
        return Inertia::render('blog/ImportanceOfLineShopping');
    }

    public function howToBetOnBaseball()
    {
        return Inertia::render('blog/HowToBetOnBaseball');
    }

    public function bestNHLBettingTips()
    {
        return Inertia::render('blog/BestNHLBettingTips');
    }

    public function howToBetOnFootball()
    {
        return Inertia::render('blog/HowToBetOnFootball');
    }

    public function howToBetOnSoccer()
    {
        return Inertia::render('blog/HowToBetOnSoccer');
    }

    public function golfBettingTips()
    {
        return Inertia::render('blog/GolfBettingTips');
    }
}