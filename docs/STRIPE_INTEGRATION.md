# Stripe Product Management System

## Overview
WeWinGames now features a comprehensive Stripe product management system that allows administrators to dynamically manage subscription products without touching code or environment variables.

## Key Features
- **Dynamic Product Management**: Create and manage products through the admin interface
- **Flexible Integration**: Connect to existing Stripe products or create new ones
- **Tier Support**: Bronze, Silver, Gold, and Platinum tiers
- **Billing Periods**: Daily, Weekly, and Monthly subscriptions
- **Feature Management**: Define features for each tier
- **Price Control**: Update prices anytime (affects new subscriptions only)

## Access
Navigate to `/admin/stripe-products` (requires admin role)

## How It Works

### 1. Initial Setup
1. Create product configurations for each tier/billing period combination
2. Set prices and define features for each product
3. Add optional badge text (e.g., "Best Value")

### 2. Stripe Connection Options

#### Option A: Connect to Existing Stripe Products
1. Click "Connect to Stripe" on a product
2. Select the Stripe product from your account
3. Select the corresponding price
4. System validates and saves the connection

#### Option B: Create New Products in Stripe
1. Click "Create in Stripe" on a product
2. System automatically creates product and price in Stripe
3. Connection is saved automatically

### 3. Usage
- Connected products are automatically used at checkout
- The system falls back to environment variables if products aren't connected
- Prices and features can be updated anytime

## Database Structure

### stripe_products table
```sql
- id
- name (e.g., "Gold Monthly")
- tier (Bronze, Silver, Gold, Platinum)
- billing_period (daily, weekly, monthly)
- price (in dollars)
- stripe_product_id (Stripe product ID)
- stripe_price_id (Stripe price ID)
- is_active (boolean)
- features (JSON array)
- badge_text (optional)
- sort_order
```

## API Integration

### StripeService Methods
- `fetchProducts()` - Get all products from Stripe
- `fetchPricesForProduct($productId)` - Get prices for a product
- `createProduct($data)` - Create product in Stripe
- `createPrice($data)` - Create price in Stripe
- `connectToStripe($product, $stripeIds)` - Connect local to Stripe

## Implementation Details

### Models
- `StripeProduct` - Local product configuration model
- Includes scopes: `active()`, `byTier()`, `byBillingPeriod()`

### Controllers
- `StripeProductController` - Full CRUD operations and Stripe integration

### Frontend
- Vue 3 component with modal-based UI
- Real-time Stripe product fetching
- Inline instructions for admins

## Best Practices

1. **Create in Stripe First** if you need:
   - Trial periods
   - Custom metadata
   - Complex pricing models

2. **Use Local Creation** for:
   - Simple subscription products
   - Standard recurring pricing
   - Quick setup

3. **Price Updates**:
   - Only affect new subscriptions
   - Existing subscriptions continue at their original price
   - Consider creating new products for major price changes

## Migration from Environment Variables

The system maintains backward compatibility:
1. If no products are connected, falls back to env variables
2. Once products are connected, they take precedence
3. Can gradually migrate by connecting products one at a time

## Security Considerations

- Only admins can access product management
- Stripe API calls use secure authentication
- Price changes don't affect existing subscriptions
- All actions are logged

## Troubleshooting

### Product Not Showing at Checkout
- Ensure product is active (`is_active = true`)
- Verify Stripe connection (both product and price IDs)
- Check that tier/period combination exists

### Connection Failed
- Verify Stripe API keys in `.env`
- Ensure price belongs to selected product
- Check Stripe dashboard for product status

### Price Not Updating
- Remember prices only affect new subscriptions
- Clear any caches after updating
- Verify the change in Stripe dashboard

## Seeder

Run the seeder to create initial products:
```bash
php artisan db:seed --class=StripeProductSeeder
```

This creates all 9 products (3 tiers × 3 periods) with default pricing and features.