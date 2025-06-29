<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleBlogPostsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$admin) {
            $admin = User::first();
        }

        if (!$admin) {
            $this->command->warn('No users found. Please create a user first.');
            return;
        }

        $posts = [
            [
                'title' => 'Getting Started with Sports Betting: A Beginner\'s Guide',
                'excerpt' => 'New to sports betting? This comprehensive guide covers everything you need to know to get started, from understanding odds to managing your bankroll.',
                'content' => '<h2>Introduction to Sports Betting</h2>
<p>Sports betting has become increasingly popular, offering fans a way to add excitement to their favorite games. However, success requires more than just luck—it demands knowledge, strategy, and discipline.</p>

<h3>Understanding Betting Odds</h3>
<p>Odds represent the probability of an outcome and determine potential payouts. There are three main formats:</p>
<ul>
<li><strong>American Odds</strong>: Displayed as positive (+150) or negative (-200) numbers</li>
<li><strong>Decimal Odds</strong>: Shown as decimals (2.50)</li>
<li><strong>Fractional Odds</strong>: Expressed as fractions (3/2)</li>
</ul>

<h3>Types of Bets</h3>
<p>Understanding different bet types is crucial for success:</p>
<ul>
<li><strong>Moneyline</strong>: Simply picking the winner</li>
<li><strong>Point Spread</strong>: Betting on the margin of victory</li>
<li><strong>Over/Under</strong>: Wagering on total points scored</li>
<li><strong>Parlays</strong>: Combining multiple bets for higher payouts</li>
</ul>

<h3>Bankroll Management</h3>
<p>One of the most important aspects of successful betting is managing your money wisely. Never bet more than you can afford to lose, and consider using the unit system where you bet a consistent percentage of your bankroll.</p>

<h3>Research and Analysis</h3>
<p>Successful bettors don\'t rely on gut feelings. They analyze team statistics, player performances, injuries, weather conditions, and historical matchups to make informed decisions.</p>

<h3>Common Mistakes to Avoid</h3>
<ul>
<li>Chasing losses with bigger bets</li>
<li>Betting on too many games</li>
<li>Ignoring bankroll management</li>
<li>Letting emotions guide decisions</li>
</ul>

<p>Remember, sports betting should be enjoyable. Set limits, bet responsibly, and never gamble with money you need for essential expenses.</p>',
                'category' => 'beginners-guide',
                'tags' => ['beginner', 'odds', 'bankroll', 'basics', 'education'],
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'NFL Betting Strategies: How to Bet on Football Like a Pro',
                'excerpt' => 'Master NFL betting with our expert strategies. Learn about key numbers, line shopping, and advanced analytics to improve your football betting success.',
                'content' => '<h2>Advanced NFL Betting Strategies</h2>
<p>The NFL is the most popular sport for betting in America, and understanding its nuances can give you a significant edge.</p>

<h3>Understanding Key Numbers</h3>
<p>In NFL betting, certain numbers are more important than others due to common scoring patterns:</p>
<ul>
<li><strong>3 Points</strong>: The most common margin (field goal)</li>
<li><strong>7 Points</strong>: Second most common (touchdown + extra point)</li>
<li><strong>10 Points</strong>: Third most common (touchdown + field goal)</li>
</ul>

<h3>Line Shopping</h3>
<p>Different sportsbooks offer different lines. Even half-point differences can significantly impact your long-term profitability. Always compare lines across multiple books before placing your bet.</p>

<h3>Home Field Advantage</h3>
<p>Home field advantage in the NFL is typically worth about 3 points. However, this can vary based on:</p>
<ul>
<li>Travel distance for the visiting team</li>
<li>Weather conditions</li>
<li>Crowd noise and stadium atmosphere</li>
<li>Time zone changes</li>
</ul>

<h3>Weather Impact</h3>
<p>Weather conditions can dramatically affect game outcomes:</p>
<ul>
<li><strong>Wind</strong>: Impacts passing and kicking games</li>
<li><strong>Rain/Snow</strong>: Favors running games and unders</li>
<li><strong>Extreme Cold</strong>: Can limit offensive production</li>
</ul>

<h3>Advanced Metrics to Consider</h3>
<p>Modern NFL betting requires understanding advanced analytics:</p>
<ul>
<li><strong>DVOA</strong>: Defense-adjusted Value Over Average</li>
<li><strong>EPA</strong>: Expected Points Added</li>
<li><strong>Success Rate</strong>: Percentage of positive EPA plays</li>
</ul>

<p>By combining traditional handicapping with advanced metrics, you can identify value that the betting market may have missed.</p>',
                'category' => 'sports-analysis',
                'tags' => ['NFL', 'football', 'strategy', 'advanced', 'analytics'],
                'is_published' => true,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'The Psychology of Sports Betting: Managing Emotions and Bias',
                'excerpt' => 'Learn how to overcome psychological biases and emotional decision-making in sports betting. Master the mental game to become a more successful bettor.',
                'content' => '<h2>Mastering the Mental Game of Sports Betting</h2>
<p>Success in sports betting isn\'t just about statistics and analysis—it\'s equally about managing your psychology and emotions.</p>

<h3>Common Psychological Biases</h3>
<p>Understanding these biases is the first step to overcoming them:</p>

<h4>1. Confirmation Bias</h4>
<p>The tendency to seek information that confirms our existing beliefs while ignoring contradictory evidence. In betting, this might mean overvaluing stats that support your pick while dismissing those that don\'t.</p>

<h4>2. Recency Bias</h4>
<p>Giving too much weight to recent events. A team\'s last game shouldn\'t overshadow their entire season performance.</p>

<h4>3. Gambler\'s Fallacy</h4>
<p>Believing that past results influence future independent events. Each game is independent—a team being "due" for a win isn\'t real.</p>

<h4>4. Overconfidence Bias</h4>
<p>Overestimating your knowledge or ability to predict outcomes. Even the best bettors lose 40-45% of the time.</p>

<h3>Emotional Control Strategies</h3>
<ul>
<li><strong>Set Rules Before Betting</strong>: Establish criteria for your bets when you\'re calm and rational</li>
<li><strong>Take Breaks</strong>: Step away after losses to avoid tilt betting</li>
<li><strong>Keep Records</strong>: Track all bets to identify emotional patterns</li>
<li><strong>Practice Mindfulness</strong>: Stay present and avoid dwelling on past losses or future wins</li>
</ul>

<h3>Building a Professional Mindset</h3>
<p>Professional bettors approach betting like a business:</p>
<ul>
<li>Focus on long-term profitability, not short-term wins</li>
<li>View losses as business expenses</li>
<li>Continuously educate and improve</li>
<li>Maintain discipline even during winning streaks</li>
</ul>

<p>Remember: The goal isn\'t to win every bet—it\'s to make profitable decisions consistently over time.</p>',
                'category' => 'tips-strategies',
                'tags' => ['psychology', 'mindset', 'discipline', 'bias', 'emotions'],
                'is_published' => true,
                'published_at' => now()->subDays(1),
            ],
            [
                'title' => 'Live Betting: Strategies for In-Game Wagering Success',
                'excerpt' => 'Discover the fast-paced world of live betting. Learn strategies, tips, and best practices for making profitable in-game wagers.',
                'content' => '<h2>The Art of Live Betting</h2>
<p>Live betting, also known as in-play betting, has revolutionized sports wagering by allowing bettors to place wagers during the game.</p>

<h3>Advantages of Live Betting</h3>
<ul>
<li><strong>Real-Time Information</strong>: See how teams are actually performing</li>
<li><strong>Momentum Shifts</strong>: Capitalize on game flow changes</li>
<li><strong>Better Line Value</strong>: Find inefficiencies in rapidly changing odds</li>
<li><strong>Hedge Opportunities</strong>: Protect pre-game bets</li>
</ul>

<h3>Key Live Betting Strategies</h3>

<h4>1. The Momentum Fade</h4>
<p>When a team goes on a scoring run, the live odds often overreact. Betting against this momentum can provide value, especially in high-variance sports like basketball.</p>

<h4>2. Half-Time Adjustments</h4>
<p>Some coaches are known for excellent half-time adjustments. Research which teams historically perform better in second halves.</p>

<h4>3. Late Game Situations</h4>
<p>Understanding how teams perform in clutch situations can provide edges in live betting:</p>
<ul>
<li>Some teams excel in close games</li>
<li>Others struggle under pressure</li>
<li>Coaching decisions become crucial</li>
</ul>

<h3>Live Betting Best Practices</h3>
<ul>
<li><strong>Have Multiple Screens</strong>: Watch the game while monitoring odds</li>
<li><strong>Fast Internet is Crucial</strong>: Seconds matter in live betting</li>
<li><strong>Set Limits Beforehand</strong>: It\'s easy to get caught up in the action</li>
<li><strong>Focus on Specific Markets</strong>: Master a few rather than betting everything</li>
</ul>

<h3>Common Live Betting Mistakes</h3>
<ul>
<li>Chasing losses with increasingly risky live bets</li>
<li>Overreacting to early game events</li>
<li>Not accounting for the time delay in feeds</li>
<li>Ignoring pre-game research in favor of "feel"</li>
</ul>

<p>Live betting can be highly profitable but requires discipline, quick thinking, and solid pre-game preparation.</p>',
                'category' => 'advanced-betting',
                'tags' => ['live-betting', 'in-play', 'strategy', 'real-time', 'advanced'],
                'is_published' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($posts as $postData) {
            $postData['user_id'] = $admin->id;
            $postData['views_count'] = rand(100, 1000);
            
            Post::create($postData);
        }

        $this->command->info('Sample blog posts created successfully!');
    }
}