<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class StaticPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Careers & Jobs page
        Page::updateOrCreate(
            ['slug' => 'careers-jobs'],
            [
                'title' => 'Careers & Jobs',
                'content' => '
<h2>Who We Are</h2>
<p>WeWinGames.com is a successful Sports Picks and Sports Media company that has picked 1,600+ winners over the past 3 years.</p>
<p>WE ARE LOOKING FOR BOTH PART TIME AND FULL TIME REPS. OPPORTUNITIES ACROSS THE COUNTRY.</p>

<h2>Looking For A Fun Job?</h2>
<p>The atmosphere of sports bars and casinos are unmatched. The excitement of the game is palpable. That\'s why so many people like to hang out in sports bars. We look at our job as a professional bar-goer. Literally, that\'s what we\'re looking for. We love going to sports bars and the regulars love us. We talk to all of the people in the bar, giving out our free picks. Customers who see a real value in our free picks often upgrade to our platinum tier picks.</p>

<h2>States</h2>
<p>We are in the following States and always looking for hard working entrepreneurial sales staff. Please let us know below what States you are interested in:</p>
<ul class="text-warning fw-bold">
    <li>Arizona - Phoenix</li>
    <li>Colorado - Denver</li>
    <li>Indiana - Indianapolis</li>
    <li>Michigan - Detroit</li>
    <li>Massachusetts - Boston</li>
    <li>Pennsylvania - Philadelphia and Pittsburgh</li>
    <li>New Jersey Shore and Hoboken</li>
    <li>North Carolina - Charlotte</li>
    <li>Texas - Austin & Houston</li>
    <li>Florida - Tampa/St Pete</li>
</ul>

<h2>Make Great Money!</h2>
<p><strong class="text-warning">TOP EARNERS MAKE over $1000 PER 20 HOUR WEEK.</strong> The more money you make the happier everyone is. That\'s why we are constantly adding new products and venues for you to sell in. We don\'t hire anyone. This is a sales job and you have to sell us on why you would be great at this position. We\'re looking for people to grow with and are laser focused on being the top dollar earner that we\'re looking for.</p>

<h2>Our Opportunity</h2>
<p>We link you to your future career path. We have grown to almost 100 staff in 3 years which creates plenty of growth opportunities for you in both our physical and digital business. This equally applies to the new online sports and casino betting industry. If you prove yourself, the sky is the limit!</p>

<div class="d-flex flex-wrap gap-3 mb-5">
    <span class="badge bg-primary fs-6 px-4 py-3">Apply</span>
    <span class="badge bg-primary fs-6 px-4 py-3">Interview</span>
    <span class="badge bg-primary fs-6 px-4 py-3">Training</span>
    <span class="badge bg-primary fs-6 px-4 py-3">Lift-Off</span>
</div>

{{resume-form}}',
                'published' => true,
            ]
        );

        // Create About Us page
        Page::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'About Us',
                'content' => '
<h2>Our Story</h2>
<p>Our European origins have given us a love of Sports betting as something to help us enjoy sport more. The author grew up in an Irish household where his father (a conservative teacher) spent every Saturday glued to the television. He was following the horse racing bets he had selected, live on TV. He spent a couple of evenings after school during the week assiduously putting his \'picks\' together. On Saturday morning he would leave for the bookies immediately after breakfast to place his small bets. As soon as he arrived back, he would then command the TV for the rest of Saturday afternoon. It was a lifetime passion.</p>
<p>There is nothing more exciting than, not only seeing your team winning, but to also have a small reward as well when they do as you expect. Sports betting, like all things, should be done in moderation. You should never gamble more than you can afford. Having said that, many people like to take it seriously enough to ensure they can have the enjoyment of following their bets while ensuring they do not lose money. In addition, many hope to make a small return as well. In days of very low interest rates, the returns of 20%+ achievable in Sport-betting is not to be sniffed at.</p>

<h2>Our Mission</h2>
<p>Our mission with this website is to help you enjoy your Sports-betting more by giving you both simple and practical advice. Even better, we have exhaustively studied and analyzed all of the best international sources of betting tips, to provide you with what we believe are dependable picks, across all major US and international sports.</p>
<p>We want our customers to be able to access our picks no matter what their budget is, so we have created tiers from daily through monthly picks. We are confident that you will make money following our picks, the numbers shown for results on our site are based on standard $30 stakes. Our Betting Education section should also help you become a better bettor.</p>
<p>Good luck with your betting and please keep it responsible.</p>

<h2>Our promise to you</h2>
<p>At WeWinGames.com, we genuinely want you to become a better sports bettor. This is not a bland statement, and we back it up as follows-</p>
<ol>
    <li>We provide you free and premium picks with only 1 objective in mind—to maximize returns. We are NOT tied into any particular tipsters, which means we are continually revising and exploring the various sources we use. These sources include a variety of American and International tipsters. We drop any who start to underperform from our betting picks and tips. We are continuously scouring the world for new quality tipping sources.</li>
    <li>Unlike many US-based betting services, we do not try to FOOL you by displaying only fancy win ratios. This is for the simple reason that a win ratio on its own is almost meaningless. You must take the odds into account to know if the service is profitable, as well as the stake size. This is why we focus very much on ROI (Return on Investment) in our results.</li>
    <li>We display our betting picks results completely transparently and summarized for you in easy-to-understand tables. Even more importantly, we show you all of the bets we have made ourselves and that make up our returns on our <a href="https://docs.google.com/spreadsheets/d/1dNj41tUxP2sdnMLWJ_Oz_K9zrn8kT6Kd1AeuSraC7xw/edit#gid=2142298601" target="_blank">google sheet</a> linked into the website.</li>
    <li>Line Shopping is a vital part to become a better sports bettor. Yet, most websites displaying a full odds comparison service leave out the Sportsbooks they are not affiliated with, as they do not earn money from those sites. At WeWinGames, we prefer to put the customer first, which is the reason that our <a href="https://wewingames.com/odds-comparison/" target="_blank">Odds Comparison</a> shows all available books where we can access their feeds/odds.</li>
    <li>Our betting education goes well beyond the bland articles and statements of many of our competitors, e.g. sportsbooks make their money off only margin which is simply not true—see our article on <a href="https://wewingames.com/sportbooks-earn-profits-through-online-sports-betting/" target="_blank">How do Sportsbooks make their profit</a>.</li>
</ol>',
                'published' => true,
            ]
        );
    }
}
