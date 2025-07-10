# Bet to Team Mapping Documentation

## Overview

The bet to team mapping functionality allows you to connect imported bets (which only have team names as text) to actual team records in the database. This enables better data organization, team statistics, and logo management.

## Commands

### 1. Map All Bets (Recommended)

This is the main command that runs the complete mapping process:

```bash
# Dry run to see what would happen
php artisan bets:map-all --dry-run

# Actually perform the mapping
php artisan bets:map-all
```

This command:
- Maps regular bets to teams using the `team_one` and `team_two` text fields
- Identifies and processes parlay bets
- Provides a comprehensive report of the mapping results

### 2. Map Regular Bets Only

If you want to map only non-parlay bets:

```bash
# Map all bets
php artisan bets:map-teams

# Dry run mode
php artisan bets:map-teams --dry-run

# Limit to first 100 bets (for testing)
php artisan bets:map-teams --limit=100
```

### 3. Map Parlay Bets

To specifically handle parlay bets and extract individual teams:

```bash
# Map all parlays
php artisan parlays:map

# Dry run mode
php artisan parlays:map --dry-run

# Limit processing
php artisan parlays:map --limit=50
```

### 4. Generate Mapping Report

To see the current status of bet mapping without making changes:

```bash
# View report in console
php artisan bets:mapping-report

# Export unmapped teams to CSV
php artisan bets:mapping-report --export
```

The exported CSV will be saved to `storage/app/exports/unmapped_teams_[timestamp].csv`

## How Mapping Works

### Regular Bets

1. The system looks at the `team_one` and `team_two` text fields
2. It searches for teams by:
   - Exact name match
   - Team aliases
   - Sport-specific matches (if sport is known)
3. When a match is found, it sets `team_one_id` and/or `team_two_id`

### Parlay Bets

1. The system identifies parlays by:
   - `wager_type` containing "parlay"
   - Multiple teams in team name fields (e.g., "Team1 @ Team2 & Team3 @ Team4")
2. It extracts individual team names
3. Creates entries in the `bet_teams` pivot table
4. Sets `is_parlay = true` on the bet

### Team Matching Algorithm

The system uses a smart matching algorithm:

1. **Exact Match**: First tries exact name matching
2. **Alias Match**: Checks team_aliases table for alternative names
3. **Sport Filter**: If sport is known, limits search to teams in that sport
4. **Case Insensitive**: All matches are case-insensitive
5. **Cache Optimization**: Uses caching for frequently looked up teams

## Improving Match Rates

### 1. Add Team Aliases

For teams that aren't matching, add aliases:

```bash
# Generate common aliases automatically
php artisan teams:generate-aliases

# Or add manually in admin panel
/admin/teams/{id}/edit
```

### 2. Clean Team Names

Some bets may have prefixes or suffixes that need cleaning:

```bash
# Remove "1. " prefixes from team names
php artisan teams:clean-names

# Extract teams from complex parlay strings
php artisan teams:extract-parlays
```

### 3. Fix Data Quality Issues

```bash
# Remove invalid teams (e.g., odds in team fields)
php artisan teams:cleanup-invalid

# Fix bets with odds in team fields
php artisan bets:fix-odds-in-teams
```

## Troubleshooting

### Low Mapping Rate

If you're seeing a low mapping rate:

1. Run the mapping report to identify unmapped teams:
   ```bash
   php artisan bets:mapping-report --export
   ```

2. Review the exported CSV and create aliases for common unmapped teams

3. Check for data quality issues:
   - Team names with special characters
   - Abbreviated team names
   - Misspellings

### Parlay Issues

If parlays aren't being detected:

1. Check the `wager_type` field format
2. Review the parlay extraction logic in `ExtractTeamsFromParlay`
3. Look for unusual delimiter patterns in your data

### Performance

For large datasets:

1. Use the `--limit` option to process in batches
2. Run during off-peak hours
3. Monitor memory usage with `top` or `htop`

## Database Structure

### Bets Table
- `team_one`: Original text field (preserved)
- `team_one_id`: Foreign key to teams table
- `team_two`: Original text field (preserved)
- `team_two_id`: Foreign key to teams table
- `is_parlay`: Boolean flag for parlays

### bet_teams Table (for parlays)
- `bet_id`: Foreign key to bets
- `team_id`: Foreign key to teams

### teams Table
- `id`: Primary key
- `name`: Team name
- `sport_id`: Foreign key to sports
- `league_id`: Foreign key to leagues

### team_aliases Table
- `team_id`: Foreign key to teams
- `alias`: Alternative name for the team

## Best Practices

1. **Always run dry-run first**: Use `--dry-run` to preview changes
2. **Backup your database**: Before running large mapping operations
3. **Map incrementally**: Use `--limit` for testing
4. **Monitor unmapped teams**: Regularly check what's not matching
5. **Keep aliases updated**: Add new aliases as you import new data

## API Integration

The mapped teams are used in:
- Bet Edit/Create pages (Select2 dropdowns)
- Team statistics and reports
- Logo management
- Future features like team performance tracking