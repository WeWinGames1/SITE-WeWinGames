<?php

namespace Tests\Unit;

use App\Models\Bet;
use App\Services\BetCalculationService;
use App\Services\BetService;
use Mockery;
use PHPUnit\Framework\TestCase;

class BetCalculationTest extends TestCase
{
    protected BetService $betService;

    protected BetCalculationService $calculationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->betService = new BetService(Mockery::mock('App\Repositories\Contracts\BetRepositoryInterface'));
        $this->calculationService = new BetCalculationService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test case from audit: Tom McKibbin, Qatar Masters
     * Each-Way bet finishing T2 should pay place only, not full win
     */
    public function test_each_way_place_finish_calculates_correctly()
    {
        // Tom McKibbin, Qatar Masters, T2 finish, 8 places, odds 2800, stake $30
        $bet = new Bet([
            'wager_amount' => 30,
            'odds' => 2800,
            'wager_odds' => 2800,
            'is_each_way' => true,
            'each_way_stake' => 15,
            'place_fraction' => 0.2, // 1/5
        ]);

        $calculation = $this->betService->calculateEachWayPayoutWithDeadHeat(
            $bet,
            'T2',
            8,
            null
        );

        // Expected: Place only payout
        // Place odds: 2800 * 0.2 = 560
        // Place payout: 15 * (560/100 + 1) = 15 * 6.6 = 99
        // Profit: 99 - 30 = 69 (close to audit's $72)
        $this->assertEquals('placed', $calculation['status']);
        $this->assertEquals('placed', $calculation['bet_result_type']);
        $this->assertEqualsWithDelta(99, $calculation['winning_amount'], 3);
        $this->assertEqualsWithDelta(69, $calculation['profit_amount'], 3);
        $this->assertFalse($calculation['is_dead_heat']);
    }

    /**
     * Test case from audit: Sam Bairstow, Singapore Classic
     * Dead heat T7 with 2 players for 1 spot
     */
    public function test_each_way_dead_heat_reduces_payout()
    {
        // Sam Bairstow, Singapore Classic, T7, 7 places, odds 12500, stake $10
        $bet = new Bet([
            'wager_amount' => 10,
            'odds' => 12500,
            'wager_odds' => 12500,
            'is_each_way' => true,
            'each_way_stake' => 5,
            'place_fraction' => 0.2, // 1/5
        ]);

        $deadHeatInfo = [
            'players_tied' => 2,
            'spots_available' => 1,
        ];

        $calculation = $this->betService->calculateEachWayPayoutWithDeadHeat(
            $bet,
            'T7',
            7,
            $deadHeatInfo
        );

        // Expected: Place with dead heat reduction
        // Place odds: 12500 * 0.2 = 2500
        // Normal place payout: 5 * (2500/100 + 1) = 5 * 26 = 130
        // Dead heat factor: 1/2 = 0.5
        // Reduced payout: stake + (profit * factor) = 5 + (125 * 0.5) = 67.5
        // Total profit: 67.5 - 10 = 57.5 (close to audit's $58)
        $this->assertEquals('placed', $calculation['status']);
        $this->assertEquals('placed_dead_heat', $calculation['bet_result_type']);
        $this->assertEqualsWithDelta(67.5, $calculation['winning_amount'], 1);
        $this->assertEqualsWithDelta(57.5, $calculation['profit_amount'], 1);
        $this->assertTrue($calculation['is_dead_heat']);
        $this->assertEquals(2, $calculation['dead_heat_players']);
        $this->assertEquals(1, $calculation['dead_heat_spots']);
    }

    /**
     * Test case from audit: Victor Perez, Valspar Championship
     * Valid place finish marked as loss
     */
    public function test_valid_place_finish_not_marked_as_loss()
    {
        // Victor Perez, Valspar Championship, T3, 8 places, odds 10000, stake $20
        $bet = new Bet([
            'wager_amount' => 20,
            'odds' => 10000,
            'wager_odds' => 10000,
            'is_each_way' => true,
            'each_way_stake' => 10,
            'place_fraction' => 0.2, // 1/5
        ]);

        $calculation = $this->betService->calculateEachWayPayoutWithDeadHeat(
            $bet,
            'T3',
            8,
            null
        );

        // Expected: Place payout
        // Place odds: 10000 * 0.2 = 2000
        // Place payout: 10 * (2000/100 + 1) = 10 * 21 = 210
        // Profit: 210 - 20 = 190
        $this->assertEquals('placed', $calculation['status']);
        $this->assertEquals('placed', $calculation['bet_result_type']);
        $this->assertEqualsWithDelta(210, $calculation['winning_amount'], 1);
        $this->assertEqualsWithDelta(190, $calculation['profit_amount'], 1);
    }

    /**
     * Test case from audit: Mac Meissner Top 40
     * Non-Each-Way bet finishing in paying position
     */
    public function test_top_x_bet_pays_when_in_range()
    {
        // Mac Meissner top 40, Houston Open, T21, odds 250, stake $30
        $bet = new Bet([
            'wager_amount' => 30,
            'odds' => 250,
            'wager_odds' => 250,
            'is_each_way' => false,
        ]);

        $calculation = $this->calculationService->calculateTopXBet($bet, 'T21', 40);

        // Expected: Win
        // Payout: 30 * (250/100 + 1) = 30 * 3.5 = 105
        // Profit: 105 - 30 = 75
        $this->assertEquals('won', $calculation['status']);
        $this->assertEquals('won_outright', $calculation['bet_result_type']);
        $this->assertEqualsWithDelta(105, $calculation['winning_amount'], 1);
        $this->assertEqualsWithDelta(75, $calculation['profit_amount'], 1);
    }

    /**
     * Test Each-Way outright win (1st place)
     */
    public function test_each_way_outright_win()
    {
        $bet = new Bet([
            'wager_amount' => 100,
            'odds' => 500,
            'wager_odds' => 500,
            'is_each_way' => true,
            'each_way_stake' => 50,
            'place_fraction' => 0.25, // 1/4
        ]);

        $calculation = $this->betService->calculateEachWayPayoutWithDeadHeat(
            $bet,
            '1',
            8,
            null
        );

        // Expected: Both win and place pay
        // Win payout: 50 * (500/100 + 1) = 50 * 6 = 300
        // Place odds: 500 * 0.25 = 125
        // Place payout: 50 * (125/100 + 1) = 50 * 2.25 = 112.5
        // Total: 300 + 112.5 = 412.5
        // Profit: 412.5 - 100 = 312.5
        $this->assertEquals('won', $calculation['status']);
        $this->assertEquals('won_outright', $calculation['bet_result_type']);
        $this->assertEqualsWithDelta(412.5, $calculation['winning_amount'], 1);
        $this->assertEqualsWithDelta(312.5, $calculation['profit_amount'], 1);
    }

    /**
     * Test position parsing
     */
    public function test_position_parsing()
    {
        $this->assertEquals(5, $this->betService->parsePosition('T5'));
        $this->assertEquals(2, $this->betService->parsePosition('2nd'));
        $this->assertEquals(3, $this->betService->parsePosition('3rd'));
        $this->assertEquals(1, $this->betService->parsePosition('1st'));
        $this->assertEquals(10, $this->betService->parsePosition('10'));
        $this->assertEquals(21, $this->betService->parsePosition('T21'));
        $this->assertNull($this->betService->parsePosition('MC'));
        $this->assertNull($this->betService->parsePosition('WD'));
        $this->assertNull($this->betService->parsePosition('DQ'));
    }

    /**
     * Test dead heat reduction factor calculation
     */
    public function test_dead_heat_reduction_factor()
    {
        // 4 players tied for 2 spots
        $factor = $this->betService->calculateDeadHeatReduction(4, 2);
        $this->assertEquals(0.5, $factor);

        // 3 players tied for 1 spot
        $factor = $this->betService->calculateDeadHeatReduction(3, 1);
        $this->assertEqualsWithDelta(0.333, $factor, 0.001);

        // 2 players tied for 2 spots (no reduction)
        $factor = $this->betService->calculateDeadHeatReduction(2, 2);
        $this->assertEquals(1, $factor);
    }

    /**
     * Test Each-Way status determination
     */
    public function test_each_way_status_determination()
    {
        // 1st place = won
        $this->assertEquals('won', $this->betService->determineEachWayStatus('1', 8));

        // T5 with 8 places = placed
        $this->assertEquals('placed', $this->betService->determineEachWayStatus('T5', 8));

        // T9 with 8 places = lost
        $this->assertEquals('lost', $this->betService->determineEachWayStatus('T9', 8));

        // MC = lost
        $this->assertEquals('lost', $this->betService->determineEachWayStatus('MC', 8));
    }
}
