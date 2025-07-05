# Setting Up the 50% Off First Month Discount

## Steps to Activate the Discount

### 1. Run the Seeder
```bash
php artisan db:seed --class=FirstMonthDiscountSeeder
```

This will create a discount code `FIRSTMONTH50` that:
- Gives 50% off the first month
- Works on all plans (Silver, Gold, Platinum)
- Can be used once per customer
- Has no expiration date

### 2. The System is Already Updated

The following changes have been made:

1. **Pricing Display**: The pricing cards now show:
   - "50% Off First Month" badge on all monthly plans
   - Special pricing text:
     - Silver: "Special: $22.50 first month, then $45/month"
     - Gold: "Special: $32.50 first month, then $65/month"
     - Platinum: "Special: $40 first month, then $80/month"

2. **Automatic Application**: The checkout page automatically applies the `FIRSTMONTH50` coupon code when:
   - A monthly plan is selected
   - No other discount code is provided in the URL

### 3. Creating the Discount in Stripe (Optional)

If you want to sync this with Stripe, you can:

1. Go to the Stripe Dashboard
2. Navigate to Products > Coupons
3. Create a new coupon:
   - ID: `FIRSTMONTH50`
   - Type: Percentage discount
   - Percent off: 50%
   - Duration: Once
   - Apply to: All products

4. Update the discount code in the database with the Stripe coupon ID:
```sql
UPDATE discount_codes 
SET stripe_coupon_id = 'your_stripe_coupon_id' 
WHERE code = 'FIRSTMONTH50';
```

### 4. Testing

1. Visit the pricing page
2. Click on any monthly plan
3. You should see the coupon automatically applied on the checkout page
4. The discount should show 50% off the first month

## Notes

- The discount only applies to monthly plans
- Each customer can only use it once
- The discount is automatically applied - no need for customers to enter a code
- The old "PLATINUM10FIRST" code has been replaced with this universal 50% off code