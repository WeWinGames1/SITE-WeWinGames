<?php

namespace Tests\Unit;

use App\Services\CsvImportService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvImportServiceTest extends TestCase
{
    private CsvImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CsvImportService;
        Storage::fake('local');
    }

    public function test_detects_game_column_and_maps_to_teams()
    {
        // Create a CSV file with a "Game" column
        // Includes every column validateRow() requires, so the rows are valid and
        // the assertions below exercise the team extraction from the Game column.
        $csvContent = <<<'CSV'
Date,Sport,League,Month,Game,Wager Type,Wager Name,Bet Type,Selection,Odds,Membership,Code,Stake,Wager,Status,Operator
2024-01-15,MLB,MLB,January,Miami Marlins @ Arizona Diamondbacks,Single Wager,Marlins ML,Moneyline,Marlins ML,235,Gold,Test,100,100,won,DraftKings
2024-01-16,MLB,MLB,January,Washington Nationals @ Los Angeles Angels,Single Wager,Nationals +1.5,Spread,Nationals +1.5,-115,Gold,Test,50,50,lost,FanDuel
CSV;

        $path = 'temp/test.csv';
        Storage::disk('local')->put($path, $csvContent);
        $fullPath = Storage::disk('local')->path($path);

        // Analyze the CSV
        $analysis = $this->service->analyzeCsv($fullPath);

        // Assert the analysis was successful
        $this->assertTrue($analysis['success']);

        // Assert the game column was detected
        $this->assertArrayHasKey('game', $analysis['detected_mappings']);
        $this->assertEquals('Game', $analysis['detected_mappings']['game']);

        // Test the validation with column mappings
        $columnMappings = [
            'game_date' => 'Date',
            'sport' => 'Sport',
            'league' => 'League',
            'month' => 'Month',
            'game' => 'Game', // This will be used to extract both teams
            'wager_type' => 'Wager Type',
            'wager_name' => 'Wager Name',
            'bet_type' => 'Bet Type',
            'selection' => 'Selection',
            'odds' => 'Odds',
            'membership' => 'Membership',
            'code' => 'Code',
            'stake' => 'Stake',
            'wager' => 'Wager',
            'status' => 'Status',
            'operator' => 'Operator',
        ];

        $validation = $this->service->validateImport($fullPath, $columnMappings);

        // Check the first valid row
        $firstRow = $validation['valid_rows'][0]['data'] ?? null;
        $this->assertNotNull($firstRow);

        // Assert the teams were extracted correctly
        $this->assertEquals('Miami Marlins', $firstRow['away_team']);
        $this->assertEquals('Arizona Diamondbacks', $firstRow['home_team']);

        // Check the second row
        $secondRow = $validation['valid_rows'][1]['data'] ?? null;
        $this->assertNotNull($secondRow);
        $this->assertEquals('Washington Nationals', $secondRow['away_team']);
        $this->assertEquals('Los Angeles Angels', $secondRow['home_team']);
    }

    public function test_handles_various_game_column_formats()
    {
        $service = new CsvImportService;

        // Test various formats privately
        $parseMethod = new \ReflectionMethod($service, 'parseGameColumn');
        $parseMethod->setAccessible(true);

        // Test @ separator
        $result = $parseMethod->invoke($service, 'New York Yankees @ Boston Red Sox');
        $this->assertEquals('New York Yankees', $result['away']);
        $this->assertEquals('Boston Red Sox', $result['home']);

        // Test with extra spaces
        $result = $parseMethod->invoke($service, 'Los Angeles Lakers  @  Golden State Warriors');
        $this->assertEquals('Los Angeles Lakers', $result['away']);
        $this->assertEquals('Golden State Warriors', $result['home']);

        // Test vs separator
        $result = $parseMethod->invoke($service, 'Team A vs Team B');
        $this->assertEquals('Team A', $result['away']);
        $this->assertEquals('Team B', $result['home']);

        // Test v separator
        $result = $parseMethod->invoke($service, 'Team X v Team Y');
        $this->assertEquals('Team X', $result['away']);
        $this->assertEquals('Team Y', $result['home']);
    }
}
