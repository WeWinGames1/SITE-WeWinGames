# How to Find MetaBet Query IDs

## Quick Start Guide

### Step 1: Open MetaBet in New Tab
1. In the admin bet edit page, click "Link to MetaBet"
2. Click the **"Open MetaBet in New Tab"** button
3. This will open: `https://www.metabet.io/products/hosted-odds?siteID=wewingames`

### Step 2: Find Your Game
1. Use MetaBet's search and filters to locate your game
2. You can search by:
   - Team names
   - Sport
   - League
   - Date

### Step 3: Inspect the Game Row
1. **Right-click** on the game/event row
2. Select **"Inspect"** or **"Inspect Element"**
3. Your browser's Developer Tools will open

### Step 4: Find the Query ID
Look for HTML attributes in the highlighted element or its parents. Common patterns:

#### Option A: Data Attributes (Most Common)
```html
<div data-game-id="594584">
<tr data-event-id="123456789">
<div data-query="nba-lakers-celtics-2025">
```
**Copy the value**: `594584`, `123456789`, or `nba-lakers-celtics-2025`

#### Option B: ID Attribute
```html
<div id="game-594584">
<tr id="event-123456">
```
**Copy the numeric part**: `594584` or `123456`

#### Option C: Class Names
```html
<div class="game-row query-594584">
<tr class="event metabet-594584">
```
**Copy the numeric part after "query-" or "metabet-"**: `594584`

#### Option D: URL in Links
```html
<a href="/game/594584">
<a href="/events/nba-lakers-celtics">
```
**Copy the slug or ID from the URL**: `594584` or `nba-lakers-celtics`

## Common Query ID Formats

### 1. Numeric IDs (Most Common)
```
594584
123456789
987654
```
- Pure numbers
- Usually 5-9 digits
- No special characters

### 2. Slug Format
```
nba-lakers-celtics
nfl-chiefs-bills-2025
mlb-yankees-redsox-jun15
```
- Lowercase letters
- Words separated by hyphens
- May include sport, teams, and date

### 3. Prefixed IDs
```
game-594584
event-123456
match-987654
```
- Starts with a prefix
- Followed by a hyphen
- Then the numeric ID

## Tips & Tricks

### 🔍 **Can't Find It?**
Try inspecting:
1. The entire table row `<tr>`
2. Parent container `<div>` elements
3. Links `<a>` within the row
4. Team name elements

### 💡 **Still Stuck?**
- Look for any numeric values in the HTML
- Try different games to see the pattern
- Check multiple elements in the game row
- Look for repeated numbers (likely the ID)

### ⚠️ **Common Mistakes**
- ❌ Including the prefix: `data-game-id=` (don't copy this)
- ❌ Including quotes: `"594584"` (remove quotes)
- ❌ Copying the team name instead of ID
- ✅ Just copy the ID value: `594584`

## Example Walkthrough

### Visual Example
```html
<!-- What you see in Inspector: -->
<tr
  class="game-row"
  data-game-id="594584"
  data-sport="nba"
  data-home-team="Lakers"
  data-away-team="Celtics"
>
  <td>Lakers vs Celtics</td>
  <td class="odds">-110</td>
</tr>
```

**What to copy**: `594584` (just the number from `data-game-id`)

### Testing Your Query ID
After entering a query ID in the admin:
1. Save the bet
2. View it on the public site
3. Open browser console (F12)
4. Look for MetaBet script activity
5. If odds appear → Success! ✅
6. If "Loading..." persists → Try a different ID

## Browser Developer Tools Shortcuts

| Browser | Shortcut | Alternative |
|---------|----------|-------------|
| Chrome | F12 or Ctrl+Shift+I | Right-click → Inspect |
| Firefox | F12 or Ctrl+Shift+I | Right-click → Inspect Element |
| Safari | Cmd+Option+I | Enable in Preferences → Advanced |
| Edge | F12 or Ctrl+Shift+I | Right-click → Inspect |

## Still Need Help?

### Method 1: Ask MetaBet Support
- Contact: support@metabet.io
- Provide: Your site ID (`wewingames`) and game details
- Ask: "What query ID should I use for [game details]?"

### Method 2: Test Multiple IDs
1. Try the most obvious numeric ID you find
2. If it doesn't work, try another ID from the same element
3. Test on a low-traffic bet first

### Method 3: Check MetaBet Documentation
- Visit: https://www.metabet.io/documentation
- Look for: API reference, query parameters, or game IDs
- Search: "query ID" or "game ID"

## Quick Reference

| Attribute Name | Example Value | Use This? |
|----------------|---------------|-----------|
| `data-game-id` | 594584 | ✅ Yes |
| `data-event-id` | 123456 | ✅ Yes |
| `data-query` | nba-lakers | ✅ Yes |
| `id` | game-594584 | ✅ Yes (number only) |
| `class` | query-594584 | ✅ Yes (number only) |
| `data-team` | Lakers | ❌ No (team name) |
| `data-odds` | -110 | ❌ No (odds value) |

## Success Checklist

Before saving:
- [ ] Query ID contains only numbers or letters/hyphens
- [ ] No quotes, brackets, or special characters
- [ ] Not a team name or odds value
- [ ] Matches the format of other IDs you see
- [ ] Copied from a game-specific attribute (not generic)

After saving:
- [ ] Bet shows "MetaBet Linked" status
- [ ] Query ID is displayed in admin
- [ ] Public page shows live odds section
- [ ] Odds populate (may take a moment)

---

**Remember**: The query ID is MetaBet's unique identifier for each game. It's like a product SKU - every game has one, and finding it is just a matter of looking in the right place in the HTML!
