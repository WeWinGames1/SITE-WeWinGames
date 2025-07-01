# Admin Portal Structure - WeWinGames

## Overview
This document outlines the complete structure of the WeWinGames admin portal, including all routes, pages, and features.

## Admin Portal Access
- **Login URL**: `/admin/login`
- **Dashboard URL**: `/admin`
- **Authentication**: Requires admin role via `AdminMiddleware`

## Navigation Structure

### 1. Dashboard
- **Route**: `admin.dashboard`
- **Path**: `/admin`
- **Component**: `Dashboard.vue`
- **Status**: ✅ Implemented

### 2. Betting Management
- **Bets Management**
  - Route: `admin.bets.index`
  - Path: `/admin/bets`
  - Component: `Bets/Index.vue`
  - Features: List, Create, Edit, Delete, Bulk Update Status
  - Status: ✅ Implemented

- **Import Bets**
  - Route: `admin.bets.import.index`
  - Path: `/admin/bets/import`
  - Component: `BetImport/Index.vue`
  - Features: CSV Upload, Validation, Mapping, Progress Tracking
  - Status: ✅ Implemented

- **Export Bets**
  - Route: `admin.bets.export`
  - Path: `/admin/bets/export-csv`
  - Features: CSV Export
  - Status: ✅ Implemented

- **Games Management**
  - Status: ❌ TODO - Controller and views need implementation

- **Teams Management**
  - Status: ❌ TODO - Controller and views need implementation

- **Sports Management**
  - Status: ❌ TODO - Controller and views need implementation

- **Operators Management**
  - Status: ❌ TODO - Controller and views need implementation

### 3. User Management
- **Customers**
  - Route: `admin.customers.index`
  - Path: `/admin/customers`
  - Component: `CustomersIndex.vue`
  - Features: List, Edit, Impersonate, Manage Ambassador/Gifted Status
  - Status: ✅ Implemented

- **Subscriptions**
  - Route: `admin.subscriptions.index`
  - Path: `/admin/subscriptions`
  - Component: `Subscriptions/Dashboard.vue`
  - Features: View, Export, Grant, Cancel Subscriptions
  - Status: ✅ Implemented

- **Admin Users**
  - Route: `admin.admins.index`
  - Path: `/admin/admins`
  - Component: `AdminsIndex.vue`
  - Features: Add/Remove Admin Privileges
  - Status: ✅ Implemented

### 4. Content Management
- **Blog Posts**
  - Route: `admin.blog-posts.index`
  - Path: `/admin/blog-posts`
  - Component: `BlogPosts/Index.vue`
  - Features: CRUD, Rich Text Editor, SEO, Image Upload
  - Status: ✅ Implemented

- **Pages**
  - Route: `admin.pages.index`
  - Path: `/admin/pages`
  - Component: `PagesIndex.vue`
  - Features: CRUD for Static Pages
  - Status: ✅ Implemented

- **Landing Pages**
  - Route: `admin.landing-pages.index`
  - Path: `/admin/landing-pages`
  - Component: `LandingPagesIndex.vue`
  - Features: CRUD for Marketing Landing Pages
  - Status: ✅ Implemented

### 5. E-commerce
- **Stripe Products**
  - Route: `admin.stripe-products.index`
  - Path: `/admin/stripe-products`
  - Component: `StripeProducts/Index.vue`
  - Features: Manage Products, Connect to Stripe, Create Prices
  - Status: ✅ Implemented

- **Discount Codes**
  - Route: `admin.discounts.index`
  - Path: `/admin/discounts`
  - Component: `Discounts/Index.vue`
  - Features: Create, Manage, Track Usage
  - Status: ✅ Implemented

### 6. Support System
- **Support Tickets**
  - Route: `admin.support-tickets.index`
  - Path: `/admin/support-tickets`
  - Component: `SupportTickets/Index.vue`
  - Features: View, Reply, Manage Status, Assign, Bulk Actions
  - Status: ✅ Implemented

- **Ticket Categories**
  - Status: ❌ TODO - Needs implementation

### 7. Communications
- **Send Notification**
  - Status: ❌ TODO - Needs UI implementation (backend exists)
  - Backend: `AdminToolsController::notifyAll()` method exists

- **Email Templates**
  - Status: ❌ TODO - Needs implementation

### 8. Settings
- **System Settings**
  - Status: ❌ TODO - Needs implementation

## Available Controllers
The following admin controllers are implemented:
- `AdminAuthController` - Admin authentication
- `AdminDashboardController` - Dashboard stats
- `AdminToolsController` - Utility functions (notify, export)
- `AdminUserController` - Admin user management
- `BetImportWizardController` - CSV import wizard
- `BetManagementController` - Bet CRUD operations
- `BlogPostController` - Blog management
- `CustomerController` - Customer management
- `DiscountCodeController` - Discount code management
- `ImpersonationController` - User impersonation
- `LandingPageController` - Landing page management
- `PageController` - Static page management
- `StripeProductController` - Stripe product management
- `SubscriptionDashboardController` - Subscription analytics
- `SupportTicketController` - Support ticket management

## Implementation Priority
Based on the TODO items, here's a suggested implementation priority:

1. **High Priority**
   - Send Notification UI (backend exists)
   - Games Management (core betting feature)
   - Teams Management (core betting feature)
   - Sports Management (core betting feature)

2. **Medium Priority**
   - Operators Management
   - Email Templates
   - System Settings

3. **Low Priority**
   - Ticket Categories

## Technical Notes
- All admin routes use the `AdminMiddleware` for authorization
- The admin layout uses a dark sidebar with hierarchical navigation
- Bootstrap Icons are used throughout the admin interface
- The sidebar is responsive with mobile support
- Routes are organized in `routes/web.php` with admin routes grouped together
- Admin pages are located in `resources/js/pages/admin/`