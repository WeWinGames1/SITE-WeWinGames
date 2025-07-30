# Bet Import Guide - Complete Documentation

## Overview

The WeWinGames betting import system supports various bet types with automatic calculation capabilities. This guide covers all import scenarios, required fields, and optional enhancements for accurate profit/loss calculations.

## Table of Contents
1. [Basic Import Fields](#basic-import-fields)
2. [Standard Win/Loss Bets](#standard-winloss-bets)
3. [Each-Way Bets](#each-way-bets)
4. [Position & Dead Heat Fields](#position--dead-heat-fields)
5. [Special Bet Types](#special-bet-types)
6. [CSV Format Examples](#csv-format-examples)
7. [Import Best Practices](#import-best-practices)

## Basic Import Fields

### Required Fields (All Bet Types)
| Field | Description | Example Values |
|-------|-------------|----------------|
| `sport` | Sport name | `NFL`, `Golf`, `NBA`, `UFC` |
| `game` | Match/Event description | `Tom McKibbin`, `Chiefs @ Bills` |
| `bet_type` | Type of bet | `Moneyline`, `Spread`, `Outright`, `Top 40` |
| `wager_name` | Specific bet selection | `Chiefs -3.5`, `Tom McKibbin to Win` |
| `odds` | American odds format | `+2800`, `-150`, `250` |
| `wager_amount` | Stake amount | `30`, `100.50` |
| `game_date` | Date of event | `2024-02-11`, `2024-02-11 15:30:00` |
| `status` | Bet result | `won`, `lost`, `pending`, `placed` |

### Optional Fields (All Bet Types)
| Field | Description | Example Values | Default |
|-------|-------------|----------------|---------|
| `league` | League/Tour name | `PGA`, `DP World Tour` | null |
| `betting_date` | When bet was placed | `2024-02-10` | Uses game_date |
| `level` | Membership tier | `Bronze`, `Silver`, `Gold` | `Bronze` |
| `wager_type` | Wager category | `single`, `each way`, `parlay` | `single` |
| `operator` | Bookmaker | `DraftKings`, `FanDuel` | null |
| `winning_amount` | Total payout | `870`, `0` | Auto-calculated |
| `profit` | Net profit/loss | `840`, `-30` | Auto-calculated |

## Standard Win/Loss Bets

For typical team vs team or player vs player bets where the outcome is simply won or lost.

### Example: NFL Spread Bet
```csv
sport,game,bet_type,wager_name,odds,wager_amount,game_date,status
NFL,Chiefs @ Bills,Spread,Chiefs -3.5,-110,100,2024-01-15,won
```

**Calculation**: 
- Win: $100 stake × (100/110) = $90.91 profit, $190.91 total payout
- Loss: -$100 profit, $0 payout

### Example: NBA Moneyline
```csv
sport,game,bet_type,wager_name,odds,wager_amount,game_date,status
NBA,Lakers @ Celtics,Moneyline,Lakers,+150,50,2024-01-20,lost
```

**Note**: The new position fields are NOT required for standard bets. The system automatically handles these as simple win/loss calculations.

## Each-Way Bets

Each-Way bets split the stake between a win bet and a place bet. Common in Golf, Horse Racing, and other individual sports.

### Required Additional Field
| Field | Description | Example |
|-------|-------------|---------|
| `wager_type` | Must be set to `each way` | `each way` |

### Optional Each-Way Fields
| Field | Description | Example | Default |
|-------|-------------|---------|---------|
| `place_fraction` | Fraction of odds for place bet | `0.2` (1/5), `0.25` (1/4) | `0.2` |
| `place_terms` | Human-readable place terms | `1/5`, `1/4` | Derived from fraction |

### Example: Golf Each-Way Bet
```csv
sport,game,bet_type,wager_name,wager_type,odds,wager_amount,game_date,status,winning_amount,profit
Golf,Tom McKibbin,Outright,Tom McKibbin to Win,each way,+2800,30,2024-02-11,placed,99,69
```

**Important**: The import system trusts your CSV data. If you provide `winning_amount` and `profit`, these values will be used as-is without recalculation.

## Position & Dead Heat Fields

These optional fields provide additional context for your bets but do NOT trigger automatic calculations during import.

### Position Fields (Optional but Recommended for Each-Way)
| Field | Description | Example Values | Format Guide |
|-------|-------------|----------------|--------------|
| `finishing_position` | Actual finish position | `1`, `T5`, `2nd`, `MC`, `WD` | See formats below |
| `places_paid` | Number of places that pay | `8`, `10`, `6` | Tournament-specific |

### Position Format Examples
- **Numeric**: `1`, `2`, `3`, `10`, `21`
- **Tied**: `T5`, `T10`, `T21` (T = tied)
- **Ordinal**: `1st`, `2nd`, `3rd`, `4th`
- **Special**: `MC` (Missed Cut), `WD` (Withdrawn), `DQ` (Disqualified)

### Dead Heat Fields (Only When Applicable)
| Field | Description | Example | When to Use |
|-------|-------------|---------|-------------|
| `dead_heat_players` | Players tied for position | `4` | When position starts with 'T' |
| `dead_heat_spots` | Available paying spots | `2` | Based on tournament rules |

### Example: Golf Each-Way with Position
```csv
sport,game,bet_type,wager_name,wager_type,odds,wager_amount,game_date,status,winning_amount,profit,finishing_position,places_paid
Golf,Tom McKibbin,Outright,Tom McKibbin to Win,each way,+2800,30,2024-02-11,placed,99,69,T2,8
```

**Note**: Position and places_paid fields are stored for reference but do NOT affect the imported winning_amount or profit values. The CSV data is trusted as-is.

### Example: Golf Each-Way with Dead Heat
```csv
sport,game,bet_type,wager_name,wager_type,odds,wager_amount,game_date,status,winning_amount,profit,finishing_position,places_paid,dead_heat_players,dead_heat_spots
Golf,Sam Bairstow,Outright,Sam Bairstow to Win,each way,+12500,10,2024-03-24,placed,67.5,57.5,T7,7,2,1
```

**Dead Heat Information Stored**:
- T7 finish with 7 places paid
- 2 players tied for 7th place
- Only 1 spot available (7th place)
- The winning_amount and profit reflect the dead heat reduction already applied

## Special Bet Types

### Top-X Bets (Golf, NASCAR, etc.)
For bets on finishing within a certain range.

```csv
sport,game,bet_type,wager_name,odds,wager_amount,game_date,finishing_position,status
Golf,Mac Meissner,Top 40,Mac Meissner Top 40,+250,30,2024-03-31,T21,won
```

**Note**: Position data enables automatic win/loss determination for Top-X bets.

### Head-to-Head Matchups
```csv
sport,game,bet_type,wager_name,odds,wager_amount,game_date,status
Golf,McIlroy vs Scheffler,H2H,McIlroy,-120,100,2024-04-10,won
```

## CSV Format Examples

### Mixed Bet Types CSV
```csv
sport,game,bet_type,wager_name,wager_type,odds,wager_amount,game_date,status,finishing_position,places_paid,dead_heat_players,dead_heat_spots
NFL,Chiefs @ Bills,Spread,Chiefs -3.5,single,-110,100,2024-01-15,won,,,,
Golf,Viktor Hovland,Outright,Hovland to Win,each way,+1200,20,2024-02-18,placed,T3,8,,
Golf,Tommy Fleetwood,Outright,Fleetwood to Win,each way,+3300,25,2024-02-18,lost,MC,8,,
Golf,Max Homa,Top 20,Homa Top 20,single,+150,50,2024-02-18,won,T15,,,
NBA,Lakers @ Celtics,Moneyline,Lakers,single,+150,75,2024-02-20,lost,,,,
Golf,Jordan Spieth,Outright,Spieth to Win,each way,+5000,15,2024-03-10,placed,T5,6,4,2
```

### Minimal CSV (Standard Bets Only)
```csv
sport,game,bet_type,wager_name,odds,wager_amount,game_date,status
NFL,Rams @ 49ers,Spread,Rams +7,-110,100,2024-01-20,won
NBA,Heat @ Knicks,Total,Over 215,-105,50,2024-01-21,lost
```

## Import Best Practices

### 1. Data Validation
- Always verify odds format (American: +150, -110)
- Ensure dates are in supported format (YYYY-MM-DD preferred)
- Check status values are lowercase: `won`, `lost`, `placed`, `pending`

### 2. Each-Way Guidelines
- Set `wager_type` to `each way` for all Each-Way bets
- Include `finishing_position` and `places_paid` when available
- Default place fraction is 1/5 (0.2) if not specified

### 3. Dead Heat Detection
- System auto-detects from 'T' positions but manual entry is more accurate
- Always provide both `dead_heat_players` and `dead_heat_spots` together
- Common scenarios:
  - T7 with 8 places: No dead heat
  - T7 with 7 places, 2 tied: Dead heat (2 players, 1 spot)
  - T5 with 6 places, 4 tied: Dead heat (4 players, 2 spots)

### 4. Performance Tips
- Use `skip_errors` option for large imports with potential issues
- Batch imports in 1000-5000 row chunks for optimal performance
- Include bet IDs if updating existing records

### 5. Common Mistakes to Avoid
- ❌ Using decimal odds instead of American
- ❌ Forgetting to set `wager_type` for Each-Way bets
- ❌ Including currency symbols in amounts ($100 → 100)
- ❌ Using uppercase status values (Won → won)
- ❌ Missing required fields for bet type

## Import Command Examples

### Basic Import
```bash
php artisan bet:import bets.csv
```

### Import with Error Skipping
```bash
php artisan bet:import bets.csv --skip-errors
```

### Dry Run (Preview Only)
```bash
php artisan bet:import bets.csv --dry-run
```

### With Custom Mapping
```bash
php artisan bet:import bets.csv --map=sport:Sport_Name,odds:American_Odds
```

## Troubleshooting

### Common Errors and Solutions

1. **"Each Way validation failed"**
   - Ensure `wager_type` is set to `each way`
   - Check if settled Each-Way bets have position data

2. **"Invalid odds format"**
   - Use American format: +150, -110 (not 2.50, 1.91)

3. **"Date parsing error"**
   - Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS format

4. **"Unknown status"**
   - Use lowercase: `won`, `lost`, `placed`, `pending`

## Summary

The import system is flexible and handles various scenarios:

- **Standard bets**: Only basic fields required
- **Each-Way bets**: Add `wager_type = "each way"`
- **Position tracking**: Add `finishing_position` and `places_paid` for historical reference
- **Dead heats**: Add player/spot counts to document tied positions

**Important**: The CSV import trusts your data. If you provide `winning_amount` and `profit` values, they will be imported as-is without recalculation. This allows you to import historical data with accurate payouts that may have been calculated using specific rules or conditions at the time of settlement.

Position and dead heat fields are optional and serve as valuable reference data but do not trigger automatic recalculation during import.