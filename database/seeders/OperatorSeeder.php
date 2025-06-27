<?php

namespace Database\Seeders;
use App\Models\Operator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Operator::create([
            'name' => 'Barstool'
        ]);
        Operator::create([
            'name' => 'DraftKings'
        ]);

        Operator::create([
            'name' => 'FanDuel'
        ]);
        Operator::create([
            'name' => 'BetMGM'
        ]);
        Operator::create([
            'name' => 'BetFair'
        ]);
        Operator::create([
            'name' => 'BetOnlineAG'
        ]);
        Operator::create([
            'name' => 'BetRivers'
        ]);
        Operator::create([
            'name' => 'BetUS'
        ]);
        Operator::create([
            'name' => 'Bovada'
        ]);
        Operator::create([
            'name' => 'CircaSports'
        ]);
        Operator::create([
            'name' => 'PointsBetUS'
        ]);

        Operator::create([
            'name' => 'Unibet'
        ]);

        Operator::create([
            'name' => 'William Hill'
        ]);
        Operator::create([
            'name' => 'FoxBet'
        ]);
        Operator::create([
            'name' => 'SugarHouse'
        ]);
        Operator::create([
            'name' => 'SuperBook'
        ]);
        Operator::create([
            'name' => 'TwinSpires'
        ]);
        Operator::create([
            'name' => 'WynnBET'
        ]);
        Operator::create([
            'name' => 'GTBets'
        ]);
        Operator::create([
            'name' => 'MyBookieAG'
        ]);
        Operator::create([
            'name' => 'LowVig'
        ]);
        Operator::create([
            'name' => 'Intertops'
        ]);
    }
}
