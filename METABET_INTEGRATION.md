# MetaBet.io Live Odds Integration

## Overview
This integration connects your betting system with MetaBet.io to display live, real-time odds from major sportsbooks directly on your bet cards. Admins can link individual bets to MetaBet games through an intuitive modal interface.

## Features Implemented

### 1. Database Schema
**Migration**: `2025_10_14_182301_add_metabet_fields_to_bets_table.php`

Added three new columns to the `bets` table:
- `metabet_query_id` (VARCHAR, nullable) - The MetaBet game identifier
- `metabet_game_name` (VARCHAR, nullable) - Optional descriptive name
- `metabet_linked_at` (TIMESTAMP, nullable) - When the link was created

### 2. Backend Updates

#### Bet Model (`app/Models/Bet.php`)
- Added MetaBet fields to `$fillable` array
- Added `metabet_linked_at` to `$casts` array for automatic datetime handling

#### BetManagementController (`app/Http/Controllers/Admin/BetManagementController.php`)
- Added validation rules for MetaBet fields in `store()` and `update()` methods
- Automatically sets `metabet_linked_at` timestamp when linking/unlinking

### 3. Frontend Components

#### MetaBetLookupModal (`resources/js/components/MetaBetLookupModal.vue`)
**Purpose**: Admin interface for linking bets to MetaBet games

**Features**:
- Embedded iframe of MetaBet's hosted odds board
- Pre-filtered search based on team names
- Input fields for Query ID and Game Name
- Shows current link status with timestamp
- Ability to update or unlink existing connections
- Instructions for finding game IDs via browser inspect

**Usage**:
```vue
<MetaBetLookupModal
    :show="showModal"
    :bet-id="bet.id"
    :current-query-id="bet.metabet_query_id"
    :current-game-name="bet.metabet_game_name"
    :team-one="bet.team_one"
    :team-two="bet.team_two"
    :sport="bet.sports"
    @close="showModal = false"
    @linked="handleLinked"
/>
```

#### MetaBetOdds (`resources/js/components/MetaBetOdds.vue`)
**Purpose**: Reusable component for displaying live odds

**Features**:
- Automatically renders MetaBet's live odds using CSS classes
- Supports multiple markets (moneyLine, spread, total, futures)
- Supports team selection (home/away)
- Multiple display styles (classic, modern, decimal)
- Loading state indicator
- Responsive styling with dark mode support

**Props**:
- `queryId` (required) - The MetaBet query ID
- `market` (optional, default: 'moneyLine') - Betting market type
- `team` (optional, default: 'home') - Team selection
- `style` (optional, default: 'modern') - Display style
- `showLabel` (optional, default: true) - Show "Live Odds:" label
- `className` (optional) - Additional CSS classes

**Usage**:
```vue
<MetaBetOdds
    :query-id="bet.metabet_query_id"
    market="moneyLine"
    team="home"
    :show-label="false"
/>
```

### 4. Admin Interface Updates

#### Edit Bet Form (`resources/js/pages/Admin/Bets/Edit.vue`)
Added "MetaBet Live Odds Integration" card with:
- Current link status indicator
- Warning when not linked
- "Link to MetaBet" / "Update MetaBet Link" button
- Display of query ID, game name, and link timestamp
- Modal integration for lookup process

### 5. Public Display Updates

#### BetPickCard (`resources/js/components/BetPickCard.vue`)
Added live odds display section that:
- Shows only when `metabet_query_id` is present
- Displays "Live Odds from Top Sportsbooks" header
- Renders MetaBetOdds component
- Styled with blue-tinted background to highlight live data

#### App Layout (`resources/views/app.blade.php`)
Added MetaBet global script:
```blade
@if(!request()->is('admin*'))
    <script defer src="https://go.metabet.io/js/global.js?siteID=wewingames"></script>
@endif
```

## How It Works

### MetaBet CSS Class System
MetaBet's global script automatically populates elements with specific CSS classes:

```html
<span class="metabet-odds metabet-market-moneyLine-home metabet-query-594584"></span>
```

**Class breakdown**:
- `metabet-odds` - Base class to activate MetaBet
- `metabet-market-{market}-{team}` - Specifies what odds to show
- `metabet-query-{id}` - Links to specific MetaBet game
- `metabet-style-{style}` (optional) - Display format

### Admin Workflow

1. **Admin edits a bet** (`/admin/bets/{id}/edit`)
2. **Clicks "Link to MetaBet"** button
3. **Modal opens** with MetaBet odds board embedded
4. **Admin searches** for the game using filters/search
5. **Admin inspects HTML** to find the game's query ID
6. **Enters query ID** and optional game name
7. **Saves link** - timestamp recorded automatically
8. **Frontend displays live odds** on public bet pages

### End User Experience

When viewing bets on the public site:
- If a bet is linked to MetaBet → Live odds appear automatically
- If not linked → Regular static odds display
- Odds update in real-time as MetaBet receives new data
- No additional user interaction required

## Technical Details

### MetaBet Configuration
- **Site ID**: `wewingames`
- **Hosted Odds URL**: `https://www.metabet.io/products/hosted-odds?siteID=wewingames`
- **Futures URL**: `https://www.metabet.io/products/futures?siteID=wewingames`
- **Global Script**: `https://go.metabet.io/js/global.js?siteID=wewingames`

### Supported Markets
- `moneyLine` - Straight up winner
- `spread` - Point spread
- `total` - Over/Under
- `futures` - Season-long bets

### Team Options
- `home` - Home team odds
- `away` - Away team odds

### Display Styles
- `classic` - Traditional odds display
- `modern` - Contemporary styling (default)
- `decimal` - Decimal odds format

## Testing

### Test the Admin Interface
1. Navigate to `/admin/bets`
2. Click Edit on any bet
3. Scroll to "MetaBet Live Odds Integration"
4. Click "Link to MetaBet"
5. Verify modal opens with embedded odds board
6. Enter a test query ID (e.g., "594584")
7. Save and verify link is stored

### Test the Public Display
1. Link a bet in admin
2. Navigate to public picks page
3. Verify "Live Odds from Top Sportsbooks" appears
4. Check browser console for MetaBet script loading
5. Verify odds populate (may show "Loading..." briefly)

### Debug Issues
- **Odds not appearing**: Check browser console for script errors
- **"Loading..." persists**: Verify query ID is correct
- **Admin link not saving**: Check Laravel logs for validation errors
- **Script not loading**: Ensure URL is not on admin pages

## Database Queries

### Find all linked bets
```sql
SELECT id, team_one, team_two, metabet_query_id, metabet_game_name, metabet_linked_at
FROM bets
WHERE metabet_query_id IS NOT NULL;
```

### Find unlinked active bets
```sql
SELECT id, team_one, team_two, status
FROM bets
WHERE metabet_query_id IS NULL
AND status = 'pending';
```

### Link statistics
```sql
SELECT
    COUNT(*) as total_bets,
    COUNT(metabet_query_id) as linked_bets,
    ROUND(COUNT(metabet_query_id) * 100.0 / COUNT(*), 2) as link_percentage
FROM bets;
```

## Future Enhancements

### Potential Improvements
1. **Bulk Linking**: Link multiple bets at once via CSV
2. **Auto-Linking**: Automatic matching based on team names and game dates
3. **Odds Comparison**: Show odds differential from your picks
4. **Historical Tracking**: Store odds snapshots at bet creation time
5. **Multiple Markets**: Display spread and total alongside moneyline
6. **Sportsbook Logos**: Show which books have the best odds
7. **Deep Links**: Direct links to populated betslips at sportsbooks

### API Integration (Future)
Currently using script-based embedding. If MetaBet releases an API:
- Server-side odds fetching
- Odds caching to reduce load
- Historical odds data storage
- Advanced analytics and reporting

## Troubleshooting

### Common Issues

**1. Bet form validation errors**
- Ensure `metabet_query_id` is string, max 255 characters
- `metabet_game_name` is optional but also max 255 characters

**2. Modal not opening**
- Check Vue component import in Edit.vue
- Verify `showMetaBetModal` ref is properly initialized

**3. Odds not updating**
- Clear browser cache
- Check that MetaBet script loads (Network tab in DevTools)
- Verify query ID format matches MetaBet's system

**4. Admin permissions**
- Only admins can access `/admin/bets/{id}/edit`
- Verify user has admin role in database

## Support

For MetaBet-specific issues:
- Documentation: https://www.metabet.io/documentation
- Support: Contact MetaBet support team

For integration issues:
- Check Laravel logs: `storage/logs/laravel.log`
- Check browser console for JavaScript errors
- Verify database migration ran successfully

## Files Modified/Created

### Created
- `database/migrations/2025_10_14_182301_add_metabet_fields_to_bets_table.php`
- `resources/js/components/MetaBetLookupModal.vue`
- `resources/js/components/MetaBetOdds.vue`
- `METABET_INTEGRATION.md` (this file)

### Modified
- `app/Models/Bet.php`
- `app/Http/Controllers/Admin/BetManagementController.php`
- `resources/js/pages/Admin/Bets/Edit.vue`
- `resources/js/components/BetPickCard.vue`
- `resources/views/app.blade.php`

## Deployment Checklist

- [x] Run database migration
- [x] Clear application cache (`php artisan cache:clear`)
- [x] Rebuild frontend assets (`npm run build`)
- [x] Test admin linking interface
- [x] Test public odds display
- [ ] Train admin users on linking process
- [ ] Link existing active bets
- [ ] Monitor MetaBet script performance
- [ ] Document any custom query ID formats

---

**Integration completed**: October 14, 2025
**MetaBet Site ID**: wewingames
**Version**: 1.0
