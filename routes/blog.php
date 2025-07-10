<?php

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

// Dynamic blog routes - using database-driven content
Route::get('/', [BlogController::class, 'index'])->name('index');
Route::get('/{post}', [BlogController::class, 'show'])->name('show');

// Legacy redirects - redirect old URLs to new database-driven posts
Route::redirect('types-of-bets', '/blog/types-of-bets', 301);
Route::redirect('betting-predictions-explained', '/blog/betting-predictions-explained', 301);
Route::redirect('money-management', '/blog/money-management', 301);
Route::redirect('where-is-online-sports-betting-biggest-and-growing-the-most-in-the-usa/', '/blog/where-is-online-sports-betting-biggest-and-growing-the-most-in-the-usa', 301);
Route::redirect('why-was-america-late', '/blog/why-was-america-late', 301);
Route::redirect('can-betting-be-profitable', '/blog/can-betting-be-profitable', 301);
Route::redirect('is-betting-riskier-than-stock-market', '/blog/is-betting-riskier-than-stock-market', 301);
Route::redirect('sportbooks-earn-profits-through-online-sports-betting/', '/blog/sportbooks-earn-profits-through-online-sports-betting', 301);
Route::redirect('how-to-become-a-more-profitable-sports-bettor', '/blog/how-to-become-a-more-profitable-sports-bettor', 301);
Route::redirect('best-betting-sites', '/blog/best-betting-sites', 301);
Route::redirect('best-betting-picks-tricks-on-online-sports-betting', '/blog/best-betting-picks-tricks-on-online-sports-betting', 301);
Route::redirect('are-parlays-a-good-bet', '/blog/are-parlays-a-good-bet', 301);
Route::redirect('statistics-versus-emotion-in-betting', '/blog/statistics-versus-emotion-in-betting', 301);
Route::redirect('in-play-fastest-maturing-areas-in-us-sports-betting', '/blog/in-play-fastest-maturing-areas-in-us-sports-betting', 301);
Route::redirect('best-sports-betting-picks-measures', '/blog/best-sports-betting-picks-measures', 301);
Route::redirect('bet-predictions', '/blog/bet-predictions', 301);
Route::redirect('importance-of-line-shopping', '/blog/importance-of-line-shopping', 301);
Route::redirect('how-to-bet-on-baseball-best-mlb-tips-and-picks', '/blog/how-to-bet-on-baseball-best-mlb-tips-and-picks', 301);
Route::redirect('best-nhl-betting-tips-and-picks', '/blog/best-nhl-betting-tips-and-picks', 301);
Route::redirect('how-to-bet-on-football-best-nfl-tips-and-picks', '/blog/how-to-bet-on-football-best-nfl-tips-and-picks', 301);
Route::redirect('how-to-bet-on-soccer-best-soccer-tips-and-picks', '/blog/how-to-bet-on-soccer-best-soccer-tips-and-picks', 301);
Route::redirect('golf-betting-tips', '/blog/golf-betting-tips', 301);
