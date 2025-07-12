<?php

namespace Database\Seeders;

use App\Models\KnowledgebaseArticle;
use Illuminate\Database\Seeder;

class KnowledgebaseSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing articles
        KnowledgebaseArticle::truncate();

        // Frontend Documentation
        $this->createFrontendDocs();

        // Admin Documentation
        $this->createAdminDocs();
    }

    private function createFrontendDocs(): void
    {
        $frontendDocs = [
            [
                'page_identifier' => 'home',
                'title' => 'Home Page',
                'content' => 'Welcome to WeWinGames! The home page is your gateway to sports betting success.',
                'sections' => [
                    [
                        'title' => 'Hero Section',
                        'content' => 'The main banner showcases our latest picks and success rate. Click "Get Started" to view subscription plans or "View Today\'s Picks" to see our latest recommendations.'
                    ],
                    [
                        'title' => 'Subscription Plans',
                        'content' => 'Compare our Silver, Gold, and Platinum tiers. Each tier offers different benefits and access levels to our expert picks. Click "Subscribe Now" to start your subscription.'
                    ],
                    [
                        'title' => 'Recent Betting Results',
                        'content' => 'View our latest winning picks and performance metrics. This section auto-updates with our most recent results to showcase our success rate.'
                    ]
                ],
                'order' => 1,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'about-us',
                'title' => 'About Us Page',
                'content' => 'Learn about WeWinGames and our mission to help you win at sports betting.',
                'sections' => [
                    [
                        'title' => 'Our Story',
                        'content' => 'Discover how WeWinGames started and our journey to becoming a trusted name in sports betting picks.'
                    ],
                    [
                        'title' => 'Our Team',
                        'content' => 'Meet our expert handicappers and analysts who work tirelessly to provide you with winning picks.'
                    ],
                    [
                        'title' => 'Our Approach',
                        'content' => 'Learn about our data-driven methodology and how we analyze games to maximize your winning potential.'
                    ]
                ],
                'order' => 2,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'todays-bets',
                'title' => 'Today\'s Bets Page',
                'content' => 'View all of today\'s recommended bets and picks.',
                'sections' => [
                    [
                        'title' => 'Active Picks',
                        'content' => 'See all active picks for today\'s games. Premium picks are blurred for non-subscribers. Click on any pick to see detailed analysis.'
                    ],
                    [
                        'title' => 'Filtering Options',
                        'content' => 'Filter picks by sport, bet type, or confidence level. Use the search bar to find specific teams or games.'
                    ],
                    [
                        'title' => 'Pick Details',
                        'content' => 'Each pick shows the game, bet type, odds, and our confidence level. Subscribers can see full analysis and reasoning.'
                    ],
                    [
                        'title' => 'Subscription Required',
                        'content' => 'Premium picks require an active subscription. Click "Subscribe Now" to unlock all picks and analysis.'
                    ]
                ],
                'order' => 3,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'betting-results',
                'title' => 'Betting Results Page',
                'content' => 'Track our historical performance and see detailed results of past picks.',
                'sections' => [
                    [
                        'title' => 'Results Overview',
                        'content' => 'View our overall win rate, ROI, and profit metrics. Results are updated daily after games conclude.'
                    ],
                    [
                        'title' => 'Filtering Results',
                        'content' => 'Filter results by date range, sport, or outcome. Use the calendar picker to select specific time periods.'
                    ],
                    [
                        'title' => 'Detailed Statistics',
                        'content' => 'See breakdowns by sport, bet type, and odds range. Export results to CSV for your own analysis.'
                    ],
                    [
                        'title' => 'Transparency',
                        'content' => 'All results are tracked automatically and cannot be altered. We believe in full transparency with our performance.'
                    ]
                ],
                'order' => 4,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'buy-our-picks',
                'title' => 'Buy Our Picks Page',
                'content' => 'Choose the perfect subscription plan for your betting needs.',
                'sections' => [
                    [
                        'title' => 'Subscription Tiers',
                        'content' => 'Compare Silver, Gold, and Platinum tiers. Each tier offers different pick quantities and exclusive features.'
                    ],
                    [
                        'title' => 'Billing Options',
                        'content' => 'Choose from daily, weekly, or monthly billing cycles. Longer commitments come with better rates.'
                    ],
                    [
                        'title' => 'Discount Codes',
                        'content' => 'Enter any promotional codes you have to receive discounts on your subscription.'
                    ],
                    [
                        'title' => 'Secure Checkout',
                        'content' => 'All payments are processed securely through Stripe. Your payment information is never stored on our servers.'
                    ]
                ],
                'order' => 5,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'blog.index',
                'title' => 'Blog Homepage',
                'content' => 'Stay informed with our latest sports betting insights and strategies.',
                'sections' => [
                    [
                        'title' => 'Latest Articles',
                        'content' => 'Browse our most recent blog posts covering betting strategies, sports analysis, and industry news.'
                    ],
                    [
                        'title' => 'Categories',
                        'content' => 'Filter articles by category such as NFL, NBA, MLB, betting strategies, or news.'
                    ],
                    [
                        'title' => 'Search Function',
                        'content' => 'Use the search bar to find articles on specific topics or teams.'
                    ],
                    [
                        'title' => 'Popular Posts',
                        'content' => 'Check out our most-read articles in the sidebar for proven strategies and tips.'
                    ]
                ],
                'order' => 6,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'support',
                'title' => 'Support Page',
                'content' => 'Get help with your account or submit questions to our support team.',
                'sections' => [
                    [
                        'title' => 'Contact Form',
                        'content' => 'Fill out the form with your name, email, and message. Our team typically responds within 24 hours.'
                    ],
                    [
                        'title' => 'Common Issues',
                        'content' => 'Check the FAQ section first for quick answers to common questions.'
                    ],
                    [
                        'title' => 'Account Support',
                        'content' => 'For account-specific issues, log in first to access your support ticket history.'
                    ],
                    [
                        'title' => 'Emergency Contact',
                        'content' => 'For urgent matters, use the priority support option available to Gold and Platinum members.'
                    ]
                ],
                'order' => 7,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'dashboard',
                'title' => 'Customer Dashboard',
                'content' => 'Your personal command center for managing your WeWinGames account.',
                'sections' => [
                    [
                        'title' => 'Subscription Status',
                        'content' => 'View your current subscription tier, renewal date, and payment method. Upgrade or cancel anytime.'
                    ],
                    [
                        'title' => 'Recent Picks',
                        'content' => 'Quick access to today\'s picks and recent betting recommendations tailored to your subscription level.'
                    ],
                    [
                        'title' => 'Performance Tracking',
                        'content' => 'Track your personal betting results if you\'ve been logging your bets with us.'
                    ],
                    [
                        'title' => 'Account Settings',
                        'content' => 'Update your profile information, change password, and manage email preferences.'
                    ]
                ],
                'order' => 8,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'subscription.checkout',
                'title' => 'Subscription Checkout',
                'content' => 'Complete your subscription purchase securely.',
                'sections' => [
                    [
                        'title' => 'Selected Plan',
                        'content' => 'Review your selected tier and billing cycle. You can go back to change your selection if needed.'
                    ],
                    [
                        'title' => 'Payment Information',
                        'content' => 'Enter your credit card details. All payments are processed securely through Stripe.'
                    ],
                    [
                        'title' => 'Promo Codes',
                        'content' => 'Apply any discount codes before completing your purchase. Codes are validated in real-time.'
                    ],
                    [
                        'title' => 'Terms & Conditions',
                        'content' => 'Review and accept our terms of service and refund policy before completing your purchase.'
                    ]
                ],
                'order' => 9,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'faq',
                'title' => 'FAQ Page',
                'content' => 'Find answers to frequently asked questions about WeWinGames.',
                'sections' => [
                    [
                        'title' => 'General Questions',
                        'content' => 'Learn about how WeWinGames works, our track record, and what makes us different.'
                    ],
                    [
                        'title' => 'Subscription Questions',
                        'content' => 'Understand billing cycles, tier differences, and how to manage your subscription.'
                    ],
                    [
                        'title' => 'Betting Questions',
                        'content' => 'Get clarity on how to use our picks, recommended bankroll management, and betting strategies.'
                    ],
                    [
                        'title' => 'Technical Support',
                        'content' => 'Find solutions to common technical issues and account access problems.'
                    ]
                ],
                'order' => 10,
                'type' => 'frontend'
            ],
            [
                'page_identifier' => 'betting-education',
                'title' => 'Betting Education',
                'content' => 'Learn the fundamentals of sports betting and improve your skills.',
                'sections' => [
                    [
                        'title' => 'Betting Basics',
                        'content' => 'Understand different bet types, odds formats, and how to read betting lines.'
                    ],
                    [
                        'title' => 'Bankroll Management',
                        'content' => 'Learn proper bankroll management strategies to maximize long-term profitability.'
                    ],
                    [
                        'title' => 'Advanced Strategies',
                        'content' => 'Explore advanced concepts like value betting, line shopping, and hedging strategies.'
                    ],
                    [
                        'title' => 'Sports-Specific Tips',
                        'content' => 'Get specialized advice for betting on different sports like NFL, NBA, MLB, and more.'
                    ]
                ],
                'order' => 11,
                'type' => 'frontend'
            ]
        ];

        foreach ($frontendDocs as $doc) {
            KnowledgebaseArticle::create($doc);
        }
    }

    private function createAdminDocs(): void
    {
        $adminDocs = [
            [
                'page_identifier' => 'admin.dashboard',
                'title' => 'Admin Dashboard',
                'content' => 'The admin dashboard provides a comprehensive overview of your platform\'s performance.',
                'sections' => [
                    [
                        'title' => 'Statistics Overview',
                        'content' => 'View key metrics including total users, active subscriptions, MRR, and recent betting performance. Charts show trends over time.'
                    ],
                    [
                        'title' => 'Recent Activity',
                        'content' => 'Monitor recent user registrations, subscription changes, and support tickets. Click any item for details.'
                    ],
                    [
                        'title' => 'Quick Actions',
                        'content' => 'Access frequently used features like creating new bets, managing users, or sending notifications.'
                    ],
                    [
                        'title' => 'System Health',
                        'content' => 'Check database size, storage usage, and queue status. Red indicators require immediate attention.'
                    ]
                ],
                'order' => 1,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.bets.index',
                'title' => 'Manage Bets',
                'content' => 'Central hub for managing all betting picks and recommendations.',
                'sections' => [
                    [
                        'title' => 'Bet List',
                        'content' => 'View all bets with filtering by status, sport, date range. Click any bet to edit. Use checkboxes for bulk actions.'
                    ],
                    [
                        'title' => 'Bulk Actions',
                        'content' => 'Select multiple bets and update their status (Win/Loss/Push) in bulk. Great for updating results after games.'
                    ],
                    [
                        'title' => 'Import/Export',
                        'content' => 'Use the Import button to bulk upload bets via CSV. Export current view to CSV for analysis or backup.'
                    ],
                    [
                        'title' => 'Statistics',
                        'content' => 'View performance metrics by sport, date range, and bet type. Helps track which strategies work best.'
                    ]
                ],
                'order' => 2,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.bets.import.index',
                'title' => 'Import Bets Wizard',
                'content' => 'Multi-step wizard for bulk importing bets from CSV files.',
                'sections' => [
                    [
                        'title' => 'Step 1: Upload File',
                        'content' => 'Upload a CSV file with bet data. Download the template for the correct format. Files are validated for structure.'
                    ],
                    [
                        'title' => 'Step 2: Map Columns',
                        'content' => 'Map your CSV columns to database fields. System auto-detects common patterns. Required fields are marked with asterisks.'
                    ],
                    [
                        'title' => 'Step 3: Preview & Validate',
                        'content' => 'Review parsed data and fix any validation errors. Invalid rows are highlighted in red with specific error messages.'
                    ],
                    [
                        'title' => 'Step 4: Process Import',
                        'content' => 'Large imports are processed in the background. Monitor progress and download error reports if any rows fail.'
                    ]
                ],
                'order' => 3,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.customers.index',
                'title' => 'Customer Management',
                'content' => 'Manage user accounts, subscriptions, and permissions.',
                'sections' => [
                    [
                        'title' => 'User List',
                        'content' => 'Search and filter users by name, email, subscription status. Click any user to view/edit their details.'
                    ],
                    [
                        'title' => 'User Actions',
                        'content' => 'Impersonate users to see their view, reset passwords, grant ambassador status, or cancel subscriptions.'
                    ],
                    [
                        'title' => 'Bulk Export',
                        'content' => 'Export user data to CSV for marketing campaigns or analysis. Includes subscription and activity data.'
                    ],
                    [
                        'title' => 'Special Privileges',
                        'content' => 'Grant ambassador or gifted status to give users free access. Override subscription checks for special cases.'
                    ]
                ],
                'order' => 4,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.stripe-products.index',
                'title' => 'Stripe Product Management',
                'content' => 'Manage subscription products and pricing synchronized with Stripe.',
                'sections' => [
                    [
                        'title' => 'Product List',
                        'content' => 'View all subscription tiers and billing periods. Green check indicates sync with Stripe. Configure features and limits.'
                    ],
                    [
                        'title' => 'Stripe Sync',
                        'content' => 'Fetch products from Stripe or create new ones. Connect local products to Stripe products for billing.'
                    ],
                    [
                        'title' => 'Price Management',
                        'content' => 'Update prices and create new price versions. Historical prices are preserved for existing subscriptions.'
                    ],
                    [
                        'title' => 'Feature Configuration',
                        'content' => 'Set pick limits, features, and descriptions for each tier. These display on the pricing page.'
                    ]
                ],
                'order' => 5,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.blog-posts.index',
                'title' => 'Blog Management',
                'content' => 'Create and manage blog content for SEO and user engagement.',
                'sections' => [
                    [
                        'title' => 'Post List',
                        'content' => 'View all blog posts with status indicators. Filter by published, draft, or scheduled. Sort by date or popularity.'
                    ],
                    [
                        'title' => 'Rich Text Editor',
                        'content' => 'Use TinyMCE editor with image upload, formatting, and media embedding. Preview before publishing.'
                    ],
                    [
                        'title' => 'SEO Settings',
                        'content' => 'Set meta titles, descriptions, and keywords for each post. Automatic slug generation with manual override.'
                    ],
                    [
                        'title' => 'Publishing Options',
                        'content' => 'Publish immediately, save as draft, or schedule for future. Feature posts to highlight on homepage.'
                    ]
                ],
                'order' => 6,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.discounts.index',
                'title' => 'Discount Code Management',
                'content' => 'Create and manage promotional discount codes.',
                'sections' => [
                    [
                        'title' => 'Code List',
                        'content' => 'View all discount codes with usage statistics. Active codes show redemption count and revenue impact.'
                    ],
                    [
                        'title' => 'Create Discounts',
                        'content' => 'Set percentage or fixed amount discounts. Configure usage limits, validity periods, and applicable products.'
                    ],
                    [
                        'title' => 'Redemption Tracking',
                        'content' => 'See who used each code and when. Track total discount given and impact on revenue.'
                    ],
                    [
                        'title' => 'Stripe Integration',
                        'content' => 'Discounts automatically sync with Stripe coupons. Applies at checkout without manual intervention.'
                    ]
                ],
                'order' => 7,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.teams.index',
                'title' => 'Team Management',
                'content' => 'Manage sports teams, logos, and aliases for bet matching.',
                'sections' => [
                    [
                        'title' => 'Team Database',
                        'content' => 'Comprehensive list of all teams by sport and league. Search by name or browse by category.'
                    ],
                    [
                        'title' => 'Logo Management',
                        'content' => 'Upload team logos for better visual presentation. Supports multiple formats and automatic resizing.'
                    ],
                    [
                        'title' => 'Alias System',
                        'content' => 'Add team aliases (e.g., "LA Lakers" and "Lakers") for better bet import matching. Prevents duplicates.'
                    ],
                    [
                        'title' => 'League Association',
                        'content' => 'Assign teams to correct leagues and conferences. Maintains hierarchy for reporting.'
                    ]
                ],
                'order' => 8,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.support-tickets.index',
                'title' => 'Support Ticket Management',
                'content' => 'Handle customer support requests efficiently.',
                'sections' => [
                    [
                        'title' => 'Ticket Queue',
                        'content' => 'View open tickets sorted by priority and age. Color coding indicates urgency. Assign to team members.'
                    ],
                    [
                        'title' => 'Ticket Actions',
                        'content' => 'Reply to tickets, change status/priority, or assign to other admins. Use templates for common responses.'
                    ],
                    [
                        'title' => 'Bulk Operations',
                        'content' => 'Select multiple tickets to close, assign, or update priority. Useful for cleaning up old tickets.'
                    ],
                    [
                        'title' => 'Statistics',
                        'content' => 'Track response times, resolution rates, and customer satisfaction. Identify common issues.'
                    ]
                ],
                'order' => 9,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.pages.index',
                'title' => 'CMS Page Management',
                'content' => 'Manage static pages like Terms of Service, Privacy Policy, etc.',
                'sections' => [
                    [
                        'title' => 'Page List',
                        'content' => 'View all CMS pages with edit/delete options. Some pages like Terms are protected from deletion.'
                    ],
                    [
                        'title' => 'Page Editor',
                        'content' => 'Rich text editor for page content. Support for HTML and dynamic content insertion.'
                    ],
                    [
                        'title' => 'URL Management',
                        'content' => 'Set custom URLs for each page. System prevents duplicate URLs and validates format.'
                    ],
                    [
                        'title' => 'Access Control',
                        'content' => 'Set pages as public or require authentication. Control visibility in navigation menus.'
                    ]
                ],
                'order' => 10,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.faqs.index',
                'title' => 'FAQ Management',
                'content' => 'Manage frequently asked questions displayed on the FAQ page.',
                'sections' => [
                    [
                        'title' => 'Question List',
                        'content' => 'Drag and drop to reorder questions. Toggle visibility without deleting. Group by category.'
                    ],
                    [
                        'title' => 'Rich Answers',
                        'content' => 'Format answers with lists, links, and styling. Preview how they\'ll appear to users.'
                    ],
                    [
                        'title' => 'Categories',
                        'content' => 'Organize questions into categories for better user navigation. Categories display as sections.'
                    ],
                    [
                        'title' => 'Search Keywords',
                        'content' => 'Add keywords to improve FAQ search functionality. Helps users find relevant answers quickly.'
                    ]
                ],
                'order' => 11,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.notifications.email-templates.index',
                'title' => 'Email Template Management',
                'content' => 'Customize system email templates for various notifications.',
                'sections' => [
                    [
                        'title' => 'Template List',
                        'content' => 'View all system email templates. Edit to customize branding and messaging. Preview before saving.'
                    ],
                    [
                        'title' => 'Variable System',
                        'content' => 'Use template variables like {{user.name}} for dynamic content. Variables list shown in editor.'
                    ],
                    [
                        'title' => 'Testing',
                        'content' => 'Send test emails to verify formatting. Tests use sample data to show real appearance.'
                    ],
                    [
                        'title' => 'Reset Option',
                        'content' => 'Reset any template to default if needed. Useful if customization causes issues.'
                    ]
                ],
                'order' => 12,
                'type' => 'admin'
            ],
            [
                'page_identifier' => 'admin.under-construction.index',
                'title' => 'Under Construction Mode',
                'content' => 'Control site availability during maintenance or development.',
                'sections' => [
                    [
                        'title' => 'Enable/Disable',
                        'content' => 'Toggle maintenance mode on/off. When enabled, only admins can access the full site.'
                    ],
                    [
                        'title' => 'Custom Message',
                        'content' => 'Set a custom message displayed to visitors. Use rich text editor for formatting and branding.'
                    ],
                    [
                        'title' => 'Access Control',
                        'content' => 'Admins always have access. Login page remains accessible for admin authentication.'
                    ],
                    [
                        'title' => 'SEO Considerations',
                        'content' => 'Returns 503 status code to search engines. Prevents indexing of maintenance message.'
                    ]
                ],
                'order' => 13,
                'type' => 'admin'
            ]
        ];

        foreach ($adminDocs as $doc) {
            KnowledgebaseArticle::create($doc);
        }
    }
}