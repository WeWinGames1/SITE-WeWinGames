<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
Route::prefix('blog')->group(function () {
    Route::get('types-of-bets', function () {
        return inertia('blog/BetsWagersEducation');
    })->name('blog.types-of-bets');
    Route::get('betting-predictions-explained', function () {
        return inertia('blog/BettingPredictionsExplained');
    })->name('blog.betting-predictions-explained');
    Route::get('money-management', function () {
        return inertia('blog/MoneyManagement');
    })->name('blog.money-management');
    Route::get('where-is-online-sports-betting-biggest-and-growing-the-most-in-the-usa/', function () {
        return inertia('blog/WhereIsOnlineSportsBettingBiggest');
    })->name('blog.where-is-online-sports-betting-biggest-and-growing-the-most-in-the-usa');
    Route::get('why-was-america-late', function () {
        return inertia('blog/WhyWasAmericaLate');
    })->name('blog.why-was-america-late');
    Route::get('can-betting-be-profitable', function () {
        return inertia('blog/CanBettingBeProfitable');
    })->name('blog.can-betting-be-profitable');
    Route::get('is-betting-riskier-than-stock-market', function () {
        return inertia('blog/IsBettingRiskierThanStockMarket');
    })->name('blog.is-betting-riskier-than-stock-market');
    Route::get('sportbooks-earn-profits-through-online-sports-betting/', function () {
        return inertia('blog/SportbooksEarnProfits');
    })->name('blog.sportbooks-earn-profits-through-online-sports-betting');
    Route::get('how-to-become-a-more-profitable-sports-bettor', function () {
        return inertia('blog/HowToBecomeMoreProfitable');
    })->name('blog.how-to-become-a-more-profitable-sports-bettor');
    Route::get('best-betting-sites', function () {
        return inertia('blog/BestBettingSites');
    })->name('blog.best-betting-sites');
    Route::get('best-betting-picks-tricks-on-online-sports-betting', function () {
        return inertia('blog/BestBettingPicksTricks');
    })->name('blog.best-betting-picks-tricks-on-online-sports-betting');
    Route::get('are-parlays-a-good-bet', function () {
        return inertia('blog/AreParlaysAGoodBet');
    })->name('blog.are-parlays-a-good-bet');
    Route::get('statistics-versus-emotion-in-betting', function () {
        return inertia('blog/StatisticsVersusEmotionInBetting');
    })->name('blog.statistics-versus-emotion-in-betting');
    Route::get('in-play-fastest-maturing-areas-in-us-sports-betting', function () {
        return inertia('blog/InPlayFastestMaturingAreas');
    })->name('blog.in-play-fastest-maturing-areas-in-us-sports-betting');
    Route::get('best-sports-betting-picks-measures', function () {
        return inertia('blog/BestSportsBettingPicksMeasures');
    })->name('blog.best-sports-betting-picks-measures');
    Route::get('bet-predictions', function () {
        return inertia('blog/BetPredictions');
    })->name('blog.bet-predictions');
    Route::get('importance-of-line-shopping', function () {
        return inertia('blog/ImportanceOfLineShopping');
    })->name('blog.importance-of-line-shopping');
    Route::get('how-to-bet-on-baseball-best-mlb-tips-and-picks', function () {
        return inertia('blog/HowToBetOnBaseball');
    })->name('blog.how-to-bet-on-baseball-best-mlb-tips-and-picks');
    Route::get('best-nhl-betting-tips-and-picks', function () {
        return inertia('blog/BestNHLBettingTips');
    })->name('blog.best-nhl-betting-tips-and-picks');
    Route::get('how-to-bet-on-football-best-nfl-tips-and-picks', function () {
        return inertia('blog/HowToBetOnFootball');
    })->name('blog.how-to-bet-on-football-best-nfl-tips-and-picks');
    Route::get('how-to-bet-on-soccer-best-soccer-tips-and-picks', function () {
        return inertia('blog/HowToBetOnSoccer');
    })->name('blog.how-to-bet-on-soccer-best-soccer-tips-and-picks');
    Route::get('golf-betting-tips', function () {
        return inertia('blog/GolfBettingTips');
    })->name('blog.golf-betting-tips');
});