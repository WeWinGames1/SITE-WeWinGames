# Google Analytics & Tag Manager Usage

## Configuration

Set your Google service IDs in the `.env` file:

```
GOOGLE_ANALYTICS_TAG_ID=G-ZTJTTQP72Q
GOOGLE_TAG_MANAGER_ID=GTM-PQDDCG6L
```

## Usage in Vue Components

```typescript
import { useGoogleAnalytics } from '@/composables/useGoogleAnalytics';

// In your component
const { trackEvent, trackPageView, trackEcommerce } = useGoogleAnalytics();

// Track a custom event
trackEvent('button_click', {
    event_category: 'engagement',
    event_label: 'header',
    value: 1
});

// Track an ecommerce event
trackEcommerce('purchase', {
    transaction_id: '12345',
    value: 25.42,
    currency: 'USD',
    items: [
        {
            item_name: 'Silver Subscription',
            price: 60.00,
            quantity: 1
        }
    ]
});

// Manually track a page view (automatic tracking is already enabled)
trackPageView('/custom-path');
```

## Automatic Page View Tracking

Page views are automatically tracked on every navigation. No additional code is needed.

## Google Tag Manager Usage

```typescript
import { useGoogleTagManager } from '@/composables/useGoogleTagManager';

// In your component
const { pushToDataLayer, trackEvent, trackEcommerce, trackUserData } = useGoogleTagManager();

// Push custom data to data layer
pushToDataLayer({
    event: 'custom_event',
    category: 'engagement',
    action: 'click',
    label: 'header_cta'
});

// Track user data
trackUserData({
    userId: '12345',
    userType: 'subscriber',
    subscriptionTier: 'gold'
});

// Track ecommerce events
trackEcommerce('purchase', {
    transaction_id: '12345',
    value: 60.00,
    currency: 'USD',
    items: [{
        item_name: 'Silver Subscription',
        item_category: 'Subscription',
        price: 60.00,
        quantity: 1
    }]
});
```

## Testing

To verify Google Analytics and Tag Manager are working:
1. Open your browser's developer console
2. Look for network requests to `google-analytics.com` or `googletagmanager.com`
3. Check the Google Analytics real-time dashboard
4. Use Google Tag Assistant Chrome extension
5. Check `window.dataLayer` in the console to see pushed events