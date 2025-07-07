<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class BettingEducationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Types of Bets and Wagers',
                'slug' => 'types-of-bets-and-wagers',
                'featured_image' => '/images/blog/002-1.png',
                'content' => $this->getBetsWagersContent(),
                'published' => true,
            ],
            [
                'title' => 'Betting Odds Explained',
                'slug' => 'betting-odds-explained',
                'featured_image' => '/images/blog/003-1.png',
                'content' => $this->getBettingOddsContent(),
                'published' => true,
            ],
            [
                'title' => 'How to Bet on Football',
                'slug' => 'how-to-bet-on-football',
                'featured_image' => '/images/blog/football.jpg',
                'content' => $this->getFootballBettingContent(),
                'published' => true,
            ],
            [
                'title' => 'Money Management in Sports Betting',
                'slug' => 'money-management-betting',
                'featured_image' => '/images/blog/stack-money-coin-with-trading-graph-min.jpg',
                'content' => $this->getMoneyManagementContent(),
                'published' => true,
            ],
            [
                'title' => 'Line Shopping - Finding the Best Odds',
                'slug' => 'line-shopping-best-odds',
                'featured_image' => '/images/blog/line-shopping.jpg',
                'content' => $this->getLineShoppingContent(),
                'published' => true,
            ],
            [
                'title' => 'Understanding Parlays',
                'slug' => 'understanding-parlays',
                'featured_image' => '/images/blog/parlays.jpg',
                'content' => $this->getParlaysContent(),
                'published' => true,
            ],
        ];

        foreach ($pages as $pageData) {
            Page::create($pageData);
        }

        $this->command->info('Betting education pages created successfully!');
    }

    private function getBetsWagersContent(): string
    {
        return <<<'HTML'
<h2 class="text-2xl font-semibold mt-8 mb-4">Most Common for US sports</h2>
<p class="mb-4">
    Let's use this extract from a live NHL game from Points Bet to explain the 3 most common forms of US bets or wagers, which are used in our best sports betting picks service.
</p>
<img src="/images/blog/001.png" alt="best sports betting picks" class="mb-8 rounded shadow" />

<h3 class="text-xl font-bold mt-6 mb-2">Spread</h3>
<p class="mb-4">
    This is the 'line' that the bookies (Sportsbook) think most probably represents a 50/50 bet. In the box above, the New York Rangers are already losing by 2.0. So the line is +2.5, meaning if they lose by 2 or less, you would win your bet. Conversely, Tampa Bay are -2.5, so they must win by 3 scores or more for you to win this bet. The odds on a spread bet should be fairly close to the standard -110 if the spread is fair. (See odds explanation <a href="/betting-education/betting-odds-explained" class="text-indigo-400 underline">here</a>). The spread has different terms such as the Puck Line in hockey, the Run Line in baseball, etc. The objective is the same. It is the expected goal difference between the 2 teams to allow for an almost 50/50 bet. The Best Betting site will always have spreads included.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Total or Over/Under</h3>
<p class="mb-4">
    This is the 'line' at which the expected goals scored is set. In the example above, it is 4.5. Again, this is usually a 50/50 bet with odds close to -110. So, if you think more goals are likely, you bet on the 'over'. In this case, you win the bet if the final total is 5 (or higher). You lose if it is 4 (or lower). You will win on 4 goals if you bet on the 'under'.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Moneyline</h3>
<p class="mb-4">
    This is the 'straight bet' with no spread or complications. The favorite will have low odds and the underdog higher odds. In the example above, with the New York Rangers already down by 2, you can win over 8 times your bet if they come back to win. Conversely, there is no point in betting on the Lightning to win at -2587. These odds are ridiculously low. On $100 you would lose all if they lose and only win less than $4 if they win. Needless to say, this would not represent one of our best sports betting picks.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Futures</h3>
<p class="mb-4">
    As it says, a bet on a future event, e.g. the winner of the Stanley Cup or the next World Cup. This is to distinguish them from 'tonight's games which dominate the betting screens on most Sportsbooks (for easy access).
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">More Complicated Types of Wagers/Bets</h2>

<h3 class="text-xl font-bold mt-6 mb-2">Parlays</h3>
<p class="mb-4">
    Parlays or Accumulators (Accas) or combos. This is a very popular form of bet which combines different single or straight bets (i.e. one of the 3 above) into 1 connected bet. All legs of the bet must win in order for you to win. These bets have the characteristics of a 'lotto ticket', i.e. high risk for high reward. However, tempting as they are (and popular) they are generally a poor form of betting. The bettor only sees the upside of a mega-win, but the reality is that only one leg needs fail to lose all. Therefore, it is not surprising to see the larger Sportsbooks (such as Fan Duel) promoting them.
</p>
<p class="mb-4">
    The most recent trend is same-game parlays so that you can combine straight bets from the same game. This is an option that many other leading Sportsbooks are introducing. If a website wishes to be the best betting site, it should avoid the temptation of overly pushing parlay betting. This goes against what is most popular with especially younger bettors. However, it would not be consistent with wishing to give the best sports betting picks to strongly advocate parlays as a dominant betting strategy.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Round Robins</h3>
<p class="mb-4">
    This is another version of Parlays where you select a number of straight bets and then options to combine them in different ways. The sportsbooks normally provide these after the bets are selected in your bet slip. As an example, if you selected 3 single bets, let's say on teams A, B and C to win, you could have 3 2-leg options (A&B, A&C, B&C) and one 3-way option (=the same as a parlay).
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Pointsbetting</h3>
<p class="mb-4">
    This is a unique form of betting developed by Points Bet. This takes normal single bets but leverages returns for the goals scored. In the straight Over of 4.5. from the box above, it does not matter to the bet outcome if the end result is 5 or 6 goals. However, if you were pointsbetting, it would matter as your returns would double for every score in excess of the line. Conversely, if you are wrong and the total ends up 2 under the line, your losses double.
</p>
<p class="mb-4">
    You need to have enough funds in your account to cover the maximum potential loss as set by Points Bet. An option is sometimes provided to limit the losses that can occur. This will also limit the upside by the same proportion. Leveraged betting, like parlays, is much riskier, so you need to pick games or lines where you believe the upside is a good bit more than the downside, to make sensible use of Pointsbetting.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">In-Play or live bets</h3>
<p class="mb-4">
    As it says, this is any form of bet made once the game has started. This is the fastest growing part of sports betting as the enjoyment of combining live bets while watching the action is the highest. All the Sportsbooks now offer them as standard, though service levels while placing a bet can be frustrating sometimes. The lines keep changing during the game, making it difficult to place the bet in time before the line moves again. In-Play betting can be very profitable, as the emotional overreaction to what happens on the pitch changes the odds too much. You can see good examples in our article on whether <a href="/betting-education/can-betting-be-profitable" class="text-indigo-400 underline">can betting be profitable</a>.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Props</h3>
<p class="mb-4">
    Another form of betting that is growing at a fast pace. Prop bets mean any form of specialist bets such as the following:
</p>
<ul class="list-disc list-inside mb-4">
    <li>Player bets such as baskets scored, assists, rebounds, turnovers, etc in basketball</li>
    <li>Half or Quarter or period bets, or 1st 5 innings in baseball</li>
    <li>Any other form of unique bets such as top American at a golf major</li>
</ul>

<h3 class="text-xl font-bold mt-6 mb-2">Teasers</h3>
<p class="mb-4">
    Most common in football, it is a form of parlay where the sportsbook allows you to adjust the spread in your favor but gives you lower odds. This can be a more 'sensible' way to play parlays, to maximize your chances of winning by adjusting the spread in your favor.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Each-Way betting</h2>
<p class="mb-4">
    Very common around the world, but not yet in the USA, is the concept of combining a winning bet with a place bet. This is most useful in events with large fields such as horse racing and golf. The more places the sportsbook offers the better. Typically, a quarter of the winning stake is paid out if your runner is placed (places range from top 3 to 7 in large events). This type of bet can be replicated in the US by halving your bet stake and placing it on the win outright market, with the other half being placed on a Top 5 or Top 10 market. It is unfortunate that each-way bets should form part of any best sports betting picks, yet they are hard to access in the USA.
</p>
HTML;
    }

    private function getBettingOddsContent(): string
    {
        return <<<'HTML'
<h2 class="text-2xl font-semibold mt-8 mb-4">American Odds</h2>
<p class="mb-4">
    American odds are the most common format used in the United States. They are displayed as either positive (+) or negative (-) numbers.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Negative Odds (Favorites)</h3>
<p class="mb-4">
    Negative odds indicate how much you need to bet to win $100. For example, odds of -150 mean you need to bet $150 to win $100. The more negative the number, the bigger the favorite.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Positive Odds (Underdogs)</h3>
<p class="mb-4">
    Positive odds show how much you would win on a $100 bet. For example, odds of +200 mean you would win $200 on a $100 bet. The higher the positive number, the bigger the underdog.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Fractional Odds (UK)</h2>
<p class="mb-4">
    Fractional odds are commonly used in the UK and Ireland. They are displayed as fractions like 3/1 or 5/2. The first number represents potential profit, while the second number represents the stake.
</p>
<p class="mb-4">
    For example, 3/1 odds mean you win $3 for every $1 bet. Your total return would be $4 (your $1 stake plus $3 profit).
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Decimal Odds (European)</h2>
<p class="mb-4">
    Decimal odds are popular in Europe, Canada, and Australia. They represent the total return for every $1 wagered, including your stake.
</p>
<p class="mb-4">
    For example, decimal odds of 2.50 mean you receive $2.50 for every $1 bet (including your original stake). To calculate profit, subtract 1 from the decimal odds and multiply by your stake.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Converting Between Formats</h2>
<p class="mb-4">
    Understanding how to convert between different odds formats is essential for comparing lines across different sportsbooks:
</p>
<ul class="list-disc list-inside mb-4">
    <li><strong>American to Decimal:</strong> For negative odds: (100 / odds) + 1. For positive odds: (odds / 100) + 1</li>
    <li><strong>Decimal to American:</strong> If decimal > 2: (decimal - 1) × 100. If decimal < 2: -100 / (decimal - 1)</li>
    <li><strong>Fractional to Decimal:</strong> (numerator / denominator) + 1</li>
</ul>

<h2 class="text-2xl font-semibold mt-10 mb-4">Implied Probability</h2>
<p class="mb-4">
    All odds formats can be converted to implied probability, which shows the likelihood of an outcome according to the bookmaker:
</p>
<ul class="list-disc list-inside mb-4">
    <li><strong>Negative American odds:</strong> Odds / (Odds + 100) × 100</li>
    <li><strong>Positive American odds:</strong> 100 / (Odds + 100) × 100</li>
    <li><strong>Decimal odds:</strong> (1 / Decimal odds) × 100</li>
</ul>
HTML;
    }

    private function getFootballBettingContent(): string
    {
        return <<<'HTML'
<img src="/images/blog/football.jpg" alt="How to bet on Football" class="mb-8 rounded shadow" />

<h2 class="text-2xl font-semibold mt-8 mb-4">Understanding Football Point Spreads</h2>
<p class="mb-4">
    The point spread is the most popular way to bet on football. The spread levels the playing field between two teams by giving points to the underdog and taking points from the favorite.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">How Point Spreads Work</h3>
<p class="mb-4">
    If the Kansas City Chiefs are -7 against the Denver Broncos, the Chiefs must win by more than 7 points for a bet on them to win. The Broncos +7 means they can lose by up to 6 points (or win outright) for a bet on them to cash.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Key Numbers in Football</h3>
<p class="mb-4">
    In NFL betting, certain numbers are more important than others. The most common margins of victory are 3 and 7 points, making these "key numbers" in spread betting. Getting +3.5 instead of +3 or -6.5 instead of -7 can significantly impact your winning percentage.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Football Totals (Over/Under)</h2>
<p class="mb-4">
    Total betting involves wagering on the combined score of both teams. Weather conditions, offensive/defensive matchups, and pace of play all factor into total betting decisions.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Factors Affecting Totals</h3>
<ul class="list-disc list-inside mb-4">
    <li>Weather conditions (wind, rain, snow decrease scoring)</li>
    <li>Offensive and defensive rankings</li>
    <li>Injury reports (especially QBs and key skill players)</li>
    <li>Historical matchup data</li>
    <li>Home field advantage</li>
</ul>

<h2 class="text-2xl font-semibold mt-10 mb-4">Football Props and Futures</h2>
<p class="mb-4">
    Prop bets have exploded in popularity, offering wagers on individual player performances, team statistics, and game events.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Popular Football Props</h3>
<ul class="list-disc list-inside mb-4">
    <li>Passing yards/touchdowns</li>
    <li>Rushing yards/touchdowns</li>
    <li>Receiving yards/receptions</li>
    <li>First touchdown scorer</li>
    <li>Defensive stats (sacks, interceptions)</li>
</ul>

<h2 class="text-2xl font-semibold mt-10 mb-4">NFL vs College Football Betting</h2>
<p class="mb-4">
    While the basic bet types are the same, there are key differences between NFL and college football betting:
</p>
<ul class="list-disc list-inside mb-4">
    <li><strong>Talent disparity:</strong> College has much larger spreads due to talent gaps</li>
    <li><strong>Information:</strong> NFL has more reliable injury reports and data</li>
    <li><strong>Line movement:</strong> College lines move more dramatically</li>
    <li><strong>Scheduling:</strong> College teams play once per week, NFL has Thursday/Monday games</li>
</ul>
HTML;
    }

    private function getMoneyManagementContent(): string
    {
        return <<<'HTML'
<h2 class="text-2xl font-semibold mt-8 mb-4">The Importance of Bankroll Management</h2>
<p class="mb-4">
    Proper bankroll management is the foundation of successful sports betting. Without it, even the best handicappers will eventually go broke. Your bankroll is the amount of money you've set aside specifically for betting - money you can afford to lose.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Setting Your Bankroll</h3>
<p class="mb-4">
    Never bet with money you need for essential expenses. Your betting bankroll should be completely separate from your everyday finances. A good starting point is an amount that, if lost entirely, wouldn't affect your lifestyle or cause financial stress.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Unit Sizing</h2>
<p class="mb-4">
    A "unit" is your standard bet size, typically 1-5% of your total bankroll. Most professional bettors recommend risking no more than 1-2% of your bankroll on any single wager.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Flat Betting vs Variable Betting</h3>
<ul class="list-disc list-inside mb-4">
    <li><strong>Flat Betting:</strong> Betting the same amount on every wager regardless of confidence</li>
    <li><strong>Variable Betting:</strong> Adjusting bet size based on perceived edge or confidence level</li>
</ul>
<p class="mb-4">
    For beginners, flat betting is recommended as it's easier to track and prevents emotional decision-making.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">The Kelly Criterion</h2>
<p class="mb-4">
    The Kelly Criterion is a mathematical formula used to determine optimal bet sizing based on your perceived edge. While powerful in theory, it requires accurate assessment of probabilities and can suggest very large bets, making it risky for casual bettors.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Common Money Management Mistakes</h2>
<ul class="list-disc list-inside mb-4">
    <li><strong>Chasing losses:</strong> Increasing bet sizes to recover losses quickly</li>
    <li><strong>Betting while emotional:</strong> Making decisions based on frustration or excitement</li>
    <li><strong>Poor record keeping:</strong> Not tracking bets, wins, losses, and ROI</li>
    <li><strong>Overconfidence after wins:</strong> Increasing stakes during hot streaks</li>
    <li><strong>Parlaying to compensate for small bankroll:</strong> Taking unnecessary risks for bigger payouts</li>
</ul>

<h2 class="text-2xl font-semibold mt-10 mb-4">Building Long-Term Success</h2>
<p class="mb-4">
    Successful sports betting is a marathon, not a sprint. Focus on making positive expected value (+EV) bets consistently rather than trying to get rich quickly. Track every bet, analyze your results, and adjust your strategy based on data, not emotions.
</p>
<p class="mb-4">
    Remember: even the best sports bettors only win 52-60% of their bets. Proper bankroll management ensures you survive the inevitable losing streaks and capitalize on winning runs.
</p>
HTML;
    }

    private function getLineShoppingContent(): string
    {
        return <<<'HTML'
<h2 class="text-2xl font-semibold mt-8 mb-4">What is Line Shopping?</h2>
<p class="mb-4">
    Line shopping is the practice of comparing odds and lines across multiple sportsbooks to find the best value for your bets. Just like comparing prices when shopping for any product, finding the best odds can significantly impact your long-term profitability.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Why Line Shopping Matters</h3>
<p class="mb-4">
    The difference between -110 and -105 might seem small, but over hundreds of bets, these small edges compound into significant profits. Getting an extra half-point on a spread or a few cents better on a total can be the difference between a winning and losing season.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Real Examples of Line Value</h2>
<p class="mb-4">
    Consider an NFL game where different sportsbooks offer:
</p>
<ul class="list-disc list-inside mb-4">
    <li>Sportsbook A: Chiefs -7 (-110)</li>
    <li>Sportsbook B: Chiefs -6.5 (-110)</li>
    <li>Sportsbook C: Chiefs -7 (-105)</li>
</ul>
<p class="mb-4">
    If you like the Chiefs, Sportsbook B offers the best line. That extra half-point is crucial when games land on key numbers. Meanwhile, Sportsbook C offers better juice, saving you money on the vigorish.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Tools for Line Shopping</h2>
<p class="mb-4">
    Several tools and websites aggregate lines from multiple sportsbooks:
</p>
<ul class="list-disc list-inside mb-4">
    <li>Odds comparison websites</li>
    <li>Mobile apps with real-time odds</li>
    <li>Browser extensions for quick comparisons</li>
    <li>Professional betting software</li>
</ul>

<h2 class="text-2xl font-semibold mt-10 mb-4">Managing Multiple Accounts</h2>
<p class="mb-4">
    To effectively line shop, you need accounts at multiple sportsbooks. Consider these factors:
</p>
<ul class="list-disc list-inside mb-4">
    <li><strong>Sign-up bonuses:</strong> Take advantage of new user promotions</li>
    <li><strong>Deposit/withdrawal options:</strong> Ensure convenient banking methods</li>
    <li><strong>Betting limits:</strong> Higher limits at books where you win</li>
    <li><strong>User experience:</strong> Fast, reliable platforms for live betting</li>
</ul>

<h2 class="text-2xl font-semibold mt-10 mb-4">The Mathematics of Line Shopping</h2>
<p class="mb-4">
    Let's say you make 1000 bets per year at -110 odds, winning 54% (a solid winning percentage). Without line shopping, your profit would be about 3.6 units. By consistently finding -105 instead of -110, your profit increases to 8.1 units - more than double!
</p>
<p class="mb-4">
    This demonstrates why professional bettors consider line shopping non-negotiable. It's not about finding dramatically different lines - it's about consistently getting the best available price.
</p>
HTML;
    }

    private function getParlaysContent(): string
    {
        return <<<'HTML'
<h2 class="text-2xl font-semibold mt-8 mb-4">What Are Parlay Bets?</h2>
<p class="mb-4">
    A parlay combines multiple individual bets into one wager. All selections (called "legs") must win for the parlay to cash. While the potential payouts are attractive, the probability of winning decreases exponentially with each added leg.
</p>

<h3 class="text-xl font-bold mt-6 mb-2">Parlay Mathematics</h3>
<p class="mb-4">
    Let's examine a 3-team parlay where each team has -110 odds (approximately 50% win probability):
</p>
<ul class="list-disc list-inside mb-4">
    <li>Probability of winning: 0.5 × 0.5 × 0.5 = 12.5%</li>
    <li>True odds: +700</li>
    <li>Typical sportsbook payout: +600</li>
</ul>
<p class="mb-4">
    The difference between true odds and actual payout is the sportsbook's increased edge on parlays.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Why Sportsbooks Love Parlays</h2>
<p class="mb-4">
    Sportsbooks heavily promote parlays because they're extremely profitable. The house edge on a standard -110 spread bet is about 4.5%. On a 4-team parlay, that edge jumps to over 30%. This is why you see constant parlay promotions and boosts.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">When Parlays Make Sense</h2>
<p class="mb-4">
    Despite their poor expected value, there are limited situations where parlays can be reasonable:
</p>
<ul class="list-disc list-inside mb-4">
    <li><strong>Correlated parlays:</strong> When outcomes are related (e.g., team to win + over on team total)</li>
    <li><strong>Small recreational bets:</strong> Entertainment value with money you can afford to lose</li>
    <li><strong>Promotional offers:</strong> Using boost tokens or risk-free parlay promotions</li>
</ul>

<h2 class="text-2xl font-semibold mt-10 mb-4">The Parlay Trap</h2>
<p class="mb-4">
    Many bettors fall into the "parlay trap" - continuously betting parlays trying to hit a big score. Consider this: a bettor who goes 2-1 on three individual -110 bets profits 0.9 units. The same bettor loses everything if those picks were parlayed.
</p>

<h2 class="text-2xl font-semibold mt-10 mb-4">Alternatives to Traditional Parlays</h2>
<ul class="list-disc list-inside mb-4">
    <li><strong>Round robins:</strong> Multiple smaller parlays from a group of selections</li>
    <li><strong>Teasers:</strong> Parlays with adjusted spreads at reduced odds</li>
    <li><strong>Progressive betting:</strong> Reinvesting winnings on subsequent bets</li>
</ul>

<h2 class="text-2xl font-semibold mt-10 mb-4">The Bottom Line on Parlays</h2>
<p class="mb-4">
    While parlays offer exciting potential payouts, they're generally poor bets from an expected value perspective. Professional bettors rarely use them except in specific circumstances. For long-term success, focus on finding value in individual bets rather than chasing parlay paydays.
</p>
<p class="mb-4">
    Remember: sportsbooks don't build lavish casinos and sponsor major sports leagues by offering good value on parlays. They're designed to appeal to our desire for big wins while mathematically favoring the house significantly more than straight bets.
</p>
HTML;
    }
}
