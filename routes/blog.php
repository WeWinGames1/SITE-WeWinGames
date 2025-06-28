<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;

Route::get('types-of-bets', [BlogController::class, 'typesOfBets'])->name('types-of-bets');
Route::get('betting-predictions-explained', [BlogController::class, 'bettingPredictionsExplained'])->name('betting-predictions-explained');
Route::get('money-management', [BlogController::class, 'moneyManagement'])->name('money-management');
Route::get('where-is-online-sports-betting-biggest-and-growing-the-most-in-the-usa/', [BlogController::class, 'whereIsOnlineSportsBettingBiggest'])->name('where-is-online-sports-betting-biggest-and-growing-the-most-in-the-usa');
Route::get('why-was-america-late', [BlogController::class, 'whyWasAmericaLate'])->name('why-was-america-late');
Route::get('can-betting-be-profitable', [BlogController::class, 'canBettingBeProfitable'])->name('can-betting-be-profitable');
Route::get('is-betting-riskier-than-stock-market', [BlogController::class, 'isBettingRiskierThanStockMarket'])->name('is-betting-riskier-than-stock-market');
Route::get('sportbooks-earn-profits-through-online-sports-betting/', [BlogController::class, 'sportbooksEarnProfits'])->name('sportbooks-earn-profits-through-online-sports-betting');
Route::get('how-to-become-a-more-profitable-sports-bettor', [BlogController::class, 'howToBecomeMoreProfitable'])->name('how-to-become-a-more-profitable-sports-bettor');
Route::get('best-betting-sites', [BlogController::class, 'bestBettingSites'])->name('best-betting-sites');
Route::get('best-betting-picks-tricks-on-online-sports-betting', [BlogController::class, 'bestBettingPicksTricks'])->name('best-betting-picks-tricks-on-online-sports-betting');
Route::get('are-parlays-a-good-bet', [BlogController::class, 'areParlaysAGoodBet'])->name('are-parlays-a-good-bet');
Route::get('statistics-versus-emotion-in-betting', [BlogController::class, 'statisticsVersusEmotionInBetting'])->name('statistics-versus-emotion-in-betting');
Route::get('in-play-fastest-maturing-areas-in-us-sports-betting', [BlogController::class, 'inPlayFastestMaturingAreas'])->name('in-play-fastest-maturing-areas-in-us-sports-betting');
Route::get('best-sports-betting-picks-measures', [BlogController::class, 'bestSportsBettingPicksMeasures'])->name('best-sports-betting-picks-measures');
Route::get('bet-predictions', [BlogController::class, 'betPredictions'])->name('bet-predictions');
Route::get('importance-of-line-shopping', [BlogController::class, 'importanceOfLineShopping'])->name('importance-of-line-shopping');
Route::get('how-to-bet-on-baseball-best-mlb-tips-and-picks', [BlogController::class, 'howToBetOnBaseball'])->name('how-to-bet-on-baseball-best-mlb-tips-and-picks');
Route::get('best-nhl-betting-tips-and-picks', [BlogController::class, 'bestNHLBettingTips'])->name('best-nhl-betting-tips-and-picks');
Route::get('how-to-bet-on-football-best-nfl-tips-and-picks', [BlogController::class, 'howToBetOnFootball'])->name('how-to-bet-on-football-best-nfl-tips-and-picks');
Route::get('how-to-bet-on-soccer-best-soccer-tips-and-picks', [BlogController::class, 'howToBetOnSoccer'])->name('how-to-bet-on-soccer-best-soccer-tips-and-picks');
Route::get('golf-betting-tips', [BlogController::class, 'golfBettingTips'])->name('golf-betting-tips');