# Betting Education Content Extraction

This document contains all the betting education content found in Vue components that should be moved to the database pages system.

## Main Education Pages

### 1. BettingEducation.vue
- Main education hub page
- Currently references undefined `sections` data
- Links to database-driven pages using the `pages` prop
- Intro text: "Resources to help you become a better sports bettor"
- Description: "When it comes to learning about sports betting it is hard to know who to trust. Often what may look like educational content is just a guise to funnel your attention to a sponsoring sportsbook. At We Win Games, we believe a more knowledgeable sports bettor is good for everyone. It provides a richer and more sustainable market for all to enjoy."

### 2. BetsWagersEducation.vue - "Types of Bets and Wagers"

**Content:**
- Introduction explaining common US sports betting types
- Images: `/images/blog/002-1.png`, `/images/blog/001.png`

**Most Common for US sports:**
- **Spread**: The 'line' that bookies think represents a 50/50 bet. Different terms like Puck Line (hockey), Run Line (baseball). Standard -110 odds if spread is fair.
- **Total or Over/Under**: The 'line' for expected goals scored. Usually 50/50 bet with -110 odds.
- **Moneyline**: Straight bet with no spread. Favorites have low odds, underdogs higher odds.
- **Futures**: Bets on future events like Stanley Cup or World Cup winners.

**More Complicated Types:**
- **Parlays**: Combines multiple bets into one. All legs must win. High risk/reward "lotto ticket" style bets.
- **Round Robins**: Select multiple bets and combine them in different ways.
- **Pointsbetting**: Unique to PointsBet. Leverages returns based on score differential from line.
- **In-Play or live bets**: Bets made after game starts. Fastest growing segment. Lines change during game.
- **Props**: Specialist bets like player stats, half/quarter bets, etc.
- **Teasers**: Football parlays where you adjust spread in your favor for lower odds.
- **Each-Way betting**: Common globally but not in USA. Combines winning bet with place bet.

### 3. BettingPredictionsExplained.vue - "Betting Odds Explained"

**Content:**
- Image: `/images/blog/betting-Odds-1.jpg`
- Introduction: "Betting Odds differ around the world's best betting prediction sites, but it is all just different ways of expressing fractions."

**American Odds:**
- Fraction of $100
- +100 = double your money
- +150 = earn 150% profit
- -150 = bet $150 to win $100
- Negative odds = favorites
- -110 most common (10% vig/juice)

**Fractional Odds (UK):**
- Proportion of 1
- 2/1 = earn $2 for every $1 bet
- Common: 5/2, 7/4, 100/1

**Continental Odds (Europe):**
- Decimal format (e.g., 2.25)
- Includes total payout, not just winnings

**Comparison Example:**
- American: +100
- Fractional: 1/1
- Decimal: 2/1

### 4. HowToBetOnFootball.vue - "How to bet on Football – best NFL tips and picks"

**Content:**
- Image: `/images/blog/football.jpg`
- Introduction: "NFL and College Football together account for almost half of US betting in football season."

**Betting Types:**
- **Spread**: Team -4 must win by 5+ points. College spreads can be 40-50 points.
- **Total**: Combined score. MAC conference known for 100+ point games.
- **Player Props**: Catches, yards, TDs, etc. Popular due to fantasy football.
- **Teasers**: Parlay with 6-point adjustment at -120 odds. Best when crossing key numbers (3, 7, 8).

**Teaser Tips:**
- Don't tease totals
- Tease through key numbers (3, 7, 8)
- Don't tease through zero
- Use for favorites under -3 or underdogs above +7

### 5. MoneyManagement.vue - "Money management"

**Content:**
- Image: `/images/blog/stack-money-coin-with-trading-graph-min.jpg`
- Introduction: "There is no point in having the best sports betting picks and tips if you cannot manage your money correctly."

**For Serious Bettors:**
1. Know your bankroll/investment capital
2. Diversify risk across sports/bet types
3. Never bet more than 5% on one bet
4. Unit system: 1% units
   - Average bets: 1 unit
   - Good bets: 2 units
   - Exceptional value: up to 5 units
5. Use expected value and ROI concepts
   - 20-30% annual ROI is good
   - ROI more important than win/loss ratio
6. Track and learn from your bets

**For Casual Bettors:**
1. Know weekly loss limit, bet max 10% per bet
2. Avoid betting with heart only
3. Remember losses, not just wins

## Common Elements

### Disclaimer (appears on all blog pages):
"DISCLAIMER: This site is 100% for entertainment purposes only and does not involve real money betting. Gambling can be addictive, please play responsibly. If you or someone you know has a gambling problem and wants help, call 1-800 GAMBLER in the U.S. This service is intended for adult users 21+ only.

The sports betting app world is taking off and we want you to enjoy it more by becoming a profitable sports bettor."

## Other Educational Pages Found

1. WhyWasAmericaLate.vue
2. WhereIsOnlineSportsBettingBiggest.vue
3. StatisticsVersusEmotionInBetting.vue
4. SportbooksEarnProfits.vue
5. IsBettingRiskierThanStockMarkets.vue
6. InPlayFastestMaturingAreas.vue
7. ImportanceOfLineShopping.vue
8. HowToBetOnSoccer.vue
9. HowToBetOnBaseball.vue
10. HowToBecomeMoreProfitable.vue
11. GolfBettingTips.vue
12. CanBettingBeProfitable.vue
13. BetPredictions.vue
14. BestSportsBettingPicksMeasures.vue
15. BestNHLBettingTips.vue
16. BestBettingSites.vue
17. BestBettingPicksTricks.vue
18. AreParlaysAGoodBet.vue

## Recommendations

1. All this static content should be moved to the database `pages` table
2. The BettingEducation.vue component is already set up to display database-driven pages
3. Each educational article should be created as a page entry with:
   - Appropriate title
   - Slug matching current routes
   - Full HTML content
   - Featured images where applicable
   - Published status
4. Internal links between articles should be updated to use the new page slugs
5. The disclaimer should be added as a reusable component or page footer