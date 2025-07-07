<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            // Getting Started
            [
                'question' => 'What is WeWinGames?',
                'answer' => 'WeWinGames is a premium sports betting information service that provides expert picks, analysis, and betting recommendations. Our team of experienced handicappers analyzes games across multiple sports to help you make informed betting decisions.',
                'category' => 'Getting Started',
                'sort_order' => 1,
            ],
            [
                'question' => 'How do I get started with WeWinGames?',
                'answer' => 'Getting started is easy! Simply create a free account, choose a subscription plan that fits your needs (Silver, Gold, or Platinum), and you\'ll instantly gain access to our expert picks and analysis. You can start with a daily pass to try our service before committing to a longer subscription.',
                'category' => 'Getting Started',
                'sort_order' => 2,
            ],
            [
                'question' => 'What sports do you cover?',
                'answer' => 'We provide expert picks and analysis for all major sports including NFL, NBA, MLB, NHL, NCAA Football, NCAA Basketball, Soccer, Tennis, and more. Our coverage includes regular season games, playoffs, and major tournaments.',
                'category' => 'Getting Started',
                'sort_order' => 3,
            ],

            // Subscriptions & Pricing
            [
                'question' => 'What subscription plans are available?',
                'answer' => '<strong>Silver Tier:</strong> $60/month, $20/week, or $5/day - Access to basic picks and email notifications.<br><br><strong>Gold Tier (Best Value):</strong> $110/month, $39/week, or $10/day - Access to all picks, priority notifications, advanced analytics, and early access.<br><br><strong>Platinum Tier:</strong> $149/month, $49/week, or $12/day - Everything in Gold plus VIP support, exclusive insider tips, and personal betting consultant (monthly only).',
                'category' => 'Subscriptions & Pricing',
                'sort_order' => 4,
            ],
            [
                'question' => 'Can I change my subscription plan?',
                'answer' => 'Yes! You can upgrade or downgrade your subscription at any time from your account dashboard. When upgrading, you\'ll receive immediate access to the new tier\'s features. When downgrading, the change will take effect at the end of your current billing period.',
                'category' => 'Subscriptions & Pricing',
                'sort_order' => 5,
            ],
            [
                'question' => 'Do you offer refunds?',
                'answer' => 'Due to the nature of our service providing immediate access to valuable information, we do not offer refunds. However, you can cancel your subscription at any time, and you\'ll continue to have access until the end of your current billing period. We recommend starting with a daily pass if you\'re unsure.',
                'category' => 'Subscriptions & Pricing',
                'sort_order' => 6,
            ],
            [
                'question' => 'Are there any discount codes available?',
                'answer' => 'We occasionally offer promotional discount codes for new subscribers. These may be available through our email newsletter, social media channels, or special partnerships. Discount codes can be applied during checkout and may offer percentage or fixed amount discounts.',
                'category' => 'Subscriptions & Pricing',
                'sort_order' => 7,
            ],

            // Picks & Predictions
            [
                'question' => 'When are picks released?',
                'answer' => 'Picks are typically released 2-4 hours before game time to ensure you have enough time to place your bets while still having the most up-to-date information. Platinum and Gold members receive early access notifications. Times may vary for international sports.',
                'category' => 'Picks & Predictions',
                'sort_order' => 8,
            ],
            [
                'question' => 'How accurate are your picks?',
                'answer' => 'Our professional handicappers maintain a long-term winning percentage above 55%, with some specialists achieving even higher rates in specific sports. We provide full transparency with detailed tracking of all picks, including win/loss records and ROI for each sport and handicapper.',
                'category' => 'Picks & Predictions',
                'sort_order' => 9,
            ],
            [
                'question' => 'What types of bets do you recommend?',
                'answer' => 'We provide recommendations for various bet types including straight bets (spread, moneyline, totals), parlays, teasers, and prop bets. Each pick includes the recommended bet type, stake amount, and detailed reasoning. We focus on value bets with positive expected value.',
                'category' => 'Picks & Predictions',
                'sort_order' => 10,
            ],
            [
                'question' => 'Do you provide bankroll management advice?',
                'answer' => 'Yes! Proper bankroll management is crucial for long-term success. We recommend a unit-based system and provide guidance on bet sizing based on confidence levels. Platinum members receive personalized bankroll management strategies as part of their subscription.',
                'category' => 'Picks & Predictions',
                'sort_order' => 11,
            ],

            // Account & Technical
            [
                'question' => 'How do I reset my password?',
                'answer' => 'Click the "Forgot Password" link on the login page and enter your email address. We\'ll send you a secure link to reset your password. The link expires after 60 minutes for security reasons. If you don\'t receive the email, check your spam folder.',
                'category' => 'Account & Technical',
                'sort_order' => 12,
            ],
            [
                'question' => 'Can I access my account on multiple devices?',
                'answer' => 'Yes! Your WeWinGames account can be accessed from any device with an internet connection. You can be logged in on multiple devices simultaneously. Our website is fully responsive and optimized for desktop, tablet, and mobile devices.',
                'category' => 'Account & Technical',
                'sort_order' => 13,
            ],
            [
                'question' => 'How do I update my payment information?',
                'answer' => 'Log into your account and navigate to the "Subscription" section in your dashboard. Click on "Update Payment Method" to securely update your credit card information. Your subscription will continue uninterrupted with the new payment method.',
                'category' => 'Account & Technical',
                'sort_order' => 14,
            ],
            [
                'question' => 'Is my personal information secure?',
                'answer' => 'Absolutely! We use industry-standard encryption to protect your personal and payment information. All payment processing is handled securely through Stripe, and we never store your credit card details on our servers. We also use SSL encryption for all data transmission.',
                'category' => 'Account & Technical',
                'sort_order' => 15,
            ],

            // Betting Education
            [
                'question' => 'I\'m new to sports betting. Can you help?',
                'answer' => 'Yes! We have a comprehensive betting education section that covers everything from basic terminology to advanced strategies. Our guides explain different bet types, how to read odds, understanding line movements, and more. Gold and Platinum members also get access to exclusive educational webinars.',
                'category' => 'Betting Education',
                'sort_order' => 16,
            ],
            [
                'question' => 'What sportsbooks do you recommend?',
                'answer' => 'We maintain partnerships with reputable, licensed sportsbooks including DraftKings, FanDuel, BetMGM, and others. We recommend using multiple books to shop for the best lines. Always ensure you\'re betting with licensed operators in your jurisdiction.',
                'category' => 'Betting Education',
                'sort_order' => 17,
            ],
            [
                'question' => 'What is your responsible gambling policy?',
                'answer' => 'We strongly advocate for responsible gambling. Never bet more than you can afford to lose, set strict limits, and view sports betting as entertainment. If you feel you may have a gambling problem, please seek help through organizations like the National Council on Problem Gambling (1-800-522-4700).',
                'category' => 'Betting Education',
                'sort_order' => 18,
            ],

            // Support
            [
                'question' => 'How do I contact customer support?',
                'answer' => 'You can reach our support team through the in-app support system, available 24/7. Simply click on "Support" in your dashboard to submit a ticket. Platinum members receive priority support with faster response times. We typically respond to all inquiries within 24 hours.',
                'category' => 'Support',
                'sort_order' => 19,
            ],
            [
                'question' => 'Do you offer phone support?',
                'answer' => 'Phone support is available exclusively for Platinum members during business hours (9 AM - 6 PM EST, Monday-Friday). All members can access our comprehensive help center and submit support tickets for assistance with any issues.',
                'category' => 'Support',
                'sort_order' => 20,
            ],
            [
                'question' => 'What if I miss a pick notification?',
                'answer' => 'All picks remain available in your dashboard even after games start. While we recommend acting on picks when they\'re released for best value, you can always review past picks for learning purposes. Make sure to enable push notifications to never miss a pick!',
                'category' => 'Support',
                'sort_order' => 21,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'category' => $faq['category'],
                'sort_order' => $faq['sort_order'],
                'is_active' => true,
            ]);
        }
    }
}
