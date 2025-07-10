<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Team;
use App\Models\Sport;
use App\Models\League;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BetMappingReport extends Command
{
    protected $signature = 'bets:mapping-report {--export : Export unmapped teams to CSV}';
    protected $description = 'Generate a detailed report of bet to team mapping status';

    public function handle()
    {
        $this->info('Generating Bet Mapping Report...');
        $this->newLine();
        
        // Overall Statistics
        $this->generateOverallStats();
        
        // Sport-by-sport breakdown
        $this->generateSportBreakdown();
        
        // Unmapped teams analysis
        $this->analyzeUnmappedTeams();
        
        // Export if requested
        if ($this->option('export')) {
            $this->exportUnmappedTeams();
        }
        
        return Command::SUCCESS;
    }
    
    private function generateOverallStats(): void
    {
        $this->info('Overall Mapping Statistics');
        $this->info('=========================');
        
        $totalBets = Bet::count();
        
        // Regular bets
        $regularBets = Bet::where('is_parlay', false)->orWhereNull('is_parlay')->count();
        $fullyMappedRegular = Bet::whereNotNull('team_one_id')
            ->whereNotNull('team_two_id')
            ->where(function($q) {
                $q->where('is_parlay', false)->orWhereNull('is_parlay');
            })
            ->count();
        $partiallyMappedRegular = Bet::where(function($q) {
                $q->whereNotNull('team_one_id')
                    ->orWhereNotNull('team_two_id');
            })
            ->where(function($q) {
                $q->whereNull('team_one_id')
                    ->orWhereNull('team_two_id');
            })
            ->where(function($q) {
                $q->where('is_parlay', false)->orWhereNull('is_parlay');
            })
            ->count();
        
        // Parlay bets
        $parlayBets = Bet::where('is_parlay', true)->count();
        $mappedParlays = Bet::where('is_parlay', true)->whereHas('parlayTeams')->count();
        
        $this->table(
            ['Category', 'Total', 'Mapped', 'Percentage'],
            [
                ['Regular Bets', $regularBets, $fullyMappedRegular, $this->percentage($fullyMappedRegular, $regularBets)],
                ['Parlay Bets', $parlayBets, $mappedParlays, $this->percentage($mappedParlays, $parlayBets)],
                ['All Bets', $totalBets, $fullyMappedRegular + $mappedParlays, $this->percentage($fullyMappedRegular + $mappedParlays, $totalBets)],
            ]
        );
        
        $this->newLine();
    }
    
    private function generateSportBreakdown(): void
    {
        $this->info('Mapping by Sport');
        $this->info('================');
        
        $sports = Sport::withCount([
            'bets',
            'bets as mapped_bets_count' => function ($query) {
                $query->whereNotNull('team_one_id')
                    ->whereNotNull('team_two_id');
            }
        ])->orderBy('bets_count', 'desc')->get();
        
        $tableData = [];
        foreach ($sports as $sport) {
            $percentage = $sport->bets_count > 0 
                ? round(($sport->mapped_bets_count / $sport->bets_count) * 100, 2) 
                : 0;
                
            $tableData[] = [
                $sport->name,
                number_format($sport->bets_count),
                number_format($sport->mapped_bets_count),
                $percentage . '%'
            ];
        }
        
        // Add unmapped sport bets
        $unmappedSportBets = Bet::where(function($q) {
            $q->whereNull('sports')
                ->orWhere('sports', '');
        })->count();
        
        if ($unmappedSportBets > 0) {
            $tableData[] = [
                '(No Sport)',
                number_format($unmappedSportBets),
                '0',
                '0%'
            ];
        }
        
        $this->table(['Sport', 'Total Bets', 'Mapped Bets', 'Mapping %'], $tableData);
        $this->newLine();
    }
    
    private function analyzeUnmappedTeams(): void
    {
        $this->info('Top 25 Unmapped Teams');
        $this->info('=====================');
        
        // Get unmapped teams from bets
        $unmappedTeams = [];
        
        // Query for team_one
        $teamOnes = Bet::whereNull('team_one_id')
            ->whereNotNull('team_one')
            ->where('team_one', '!=', '')
            ->select('team_one as team_name', 'sports', DB::raw('COUNT(*) as count'))
            ->groupBy('team_one', 'sports')
            ->get();
        
        foreach ($teamOnes as $team) {
            $key = $team->team_name . '|' . ($team->sports ?? 'Unknown');
            $unmappedTeams[$key] = [
                'team' => $team->team_name,
                'sport' => $team->sports ?? 'Unknown',
                'count' => $team->count
            ];
        }
        
        // Query for team_two
        $teamTwos = Bet::whereNull('team_two_id')
            ->whereNotNull('team_two')
            ->where('team_two', '!=', '')
            ->select('team_two as team_name', 'sports', DB::raw('COUNT(*) as count'))
            ->groupBy('team_two', 'sports')
            ->get();
        
        foreach ($teamTwos as $team) {
            $key = $team->team_name . '|' . ($team->sports ?? 'Unknown');
            if (isset($unmappedTeams[$key])) {
                $unmappedTeams[$key]['count'] += $team->count;
            } else {
                $unmappedTeams[$key] = [
                    'team' => $team->team_name,
                    'sport' => $team->sports ?? 'Unknown',
                    'count' => $team->count
                ];
            }
        }
        
        // Sort by count
        uasort($unmappedTeams, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        // Display top 25
        $tableData = [];
        $count = 0;
        foreach ($unmappedTeams as $data) {
            if ($count >= 25) break;
            
            $tableData[] = [
                substr($data['team'], 0, 40),
                $data['sport'],
                number_format($data['count']),
                $this->suggestExistingTeam($data['team'], $data['sport'])
            ];
            $count++;
        }
        
        $this->table(['Team Name', 'Sport', 'Occurrences', 'Suggested Match'], $tableData);
        
        $this->newLine();
        $this->info('Total unique unmapped teams: ' . count($unmappedTeams));
        
        // Store for export
        $this->unmappedTeams = $unmappedTeams;
    }
    
    private function suggestExistingTeam(string $teamName, ?string $sport): string
    {
        // Clean the team name
        $cleanName = preg_replace('/^\d+\.\s+/', '', $teamName);
        $cleanName = trim($cleanName);
        
        // Try to find similar team
        $query = Team::query();
        
        if ($sport) {
            $sportModel = Sport::where('name', $sport)->first();
            if ($sportModel) {
                $query->where('sport_id', $sportModel->id);
            }
        }
        
        // Look for similar names
        $teams = $query->where('name', 'LIKE', '%' . substr($cleanName, 0, 10) . '%')
            ->limit(1)
            ->get();
        
        if ($teams->isNotEmpty()) {
            return $teams->first()->name;
        }
        
        return '-';
    }
    
    private function exportUnmappedTeams(): void
    {
        if (!isset($this->unmappedTeams) || empty($this->unmappedTeams)) {
            $this->warn('No unmapped teams to export.');
            return;
        }
        
        $filename = 'unmapped_teams_' . date('Y-m-d_His') . '.csv';
        $path = storage_path('app/exports/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }
        
        $handle = fopen($path, 'w');
        
        // Header
        fputcsv($handle, ['Team Name', 'Sport', 'Occurrences', 'Suggested Alias For']);
        
        // Data
        foreach ($this->unmappedTeams as $data) {
            $suggestion = $this->suggestExistingTeam($data['team'], $data['sport']);
            fputcsv($handle, [
                $data['team'],
                $data['sport'],
                $data['count'],
                $suggestion !== '-' ? $suggestion : ''
            ]);
        }
        
        fclose($handle);
        
        $this->newLine();
        $this->info('Unmapped teams exported to: ' . $path);
        $this->info('You can use this file to bulk create team aliases.');
    }
    
    private function percentage($value, $total): string
    {
        if ($total == 0) return '0%';
        return round(($value / $total) * 100, 2) . '%';
    }
    
    private $unmappedTeams = [];
}