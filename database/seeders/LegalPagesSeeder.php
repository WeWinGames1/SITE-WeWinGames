<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class LegalPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Terms and Conditions
        Page::updateOrCreate(
            ['slug' => 'terms-and-conditions'],
            [
                'title' => 'Terms and Conditions',
                'content' => $this->getTermsContent(),
                'published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Privacy Policy
        Page::updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'content' => $this->getPrivacyContent(),
                'published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function getTermsContent(): string
    {
        return <<<HTML
<div class="legal-content">
    <p class="lead">Last Updated: January 1, 2025</p>

    <h2>1. Agreement to Terms</h2>
    <p>By accessing or using WeWinGames ("we," "our," or "us"), you agree to be bound by these Terms and Conditions ("Terms"). If you disagree with any part of these terms, then you may not access our service.</p>

    <h2>2. Description of Service</h2>
    <p>WeWinGames provides sports betting information, analysis, and picks for educational and entertainment purposes only. Our service includes:</p>
    <ul>
        <li>Daily sports betting picks and recommendations</li>
        <li>Statistical analysis and betting trends</li>
        <li>Educational content about sports betting</li>
        <li>Subscription-based access to premium picks</li>
    </ul>

    <h2>3. Disclaimer</h2>
    <p><strong>IMPORTANT:</strong> This site is 100% for entertainment purposes only and does not involve real money betting. We do not accept wagers, facilitate betting, or handle any monetary transactions related to gambling.</p>
    <p>Gambling can be addictive. Please play responsibly. If you or someone you know has a gambling problem and wants help, call 1-800-GAMBLER in the U.S.</p>

    <h2>4. Age Restrictions</h2>
    <p>You must be at least 21 years old to use our service. By using WeWinGames, you represent and warrant that you are at least 21 years of age.</p>

    <h2>5. User Accounts</h2>
    <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password and for all activities that occur under your account.</p>

    <h2>6. Subscriptions and Billing</h2>
    <ul>
        <li>Subscriptions are billed on a recurring basis (daily, weekly, or monthly)</li>
        <li>You can cancel your subscription at any time</li>
        <li>No refunds are provided for partial subscription periods</li>
        <li>Prices are subject to change with notice</li>
    </ul>

    <h2>7. Intellectual Property</h2>
    <p>All content on WeWinGames, including text, graphics, logos, and software, is the property of WeWinGames or its content suppliers and is protected by copyright laws.</p>

    <h2>8. Prohibited Uses</h2>
    <p>You may not:</p>
    <ul>
        <li>Use our service for any illegal purpose or to solicit others to perform illegal acts</li>
        <li>Reproduce, duplicate, copy, sell, resell, or exploit any portion of the service</li>
        <li>Use any robot, spider, or other automatic device to access the service</li>
        <li>Introduce any viruses, trojan horses, worms, or other material which is malicious</li>
    </ul>

    <h2>9. Disclaimer of Warranties</h2>
    <p>The information on WeWinGames is provided "as is" without any representations or warranties. We do not warrant that:</p>
    <ul>
        <li>The service will be uninterrupted, timely, secure, or error-free</li>
        <li>The results obtained from use of the service will be accurate or reliable</li>
        <li>Any errors in the service will be corrected</li>
    </ul>

    <h2>10. Limitation of Liability</h2>
    <p>In no event shall WeWinGames, its directors, employees, partners, agents, or affiliates, be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the service.</p>

    <h2>11. Indemnification</h2>
    <p>You agree to defend, indemnify, and hold harmless WeWinGames from any claim or demand made by any third party due to or arising out of your breach of these Terms or your violation of any law.</p>

    <h2>12. Termination</h2>
    <p>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>

    <h2>13. Governing Law</h2>
    <p>These Terms shall be governed by and construed in accordance with the laws of the United States, without regard to its conflict of law provisions.</p>

    <h2>14. Changes to Terms</h2>
    <p>We reserve the right to modify or replace these Terms at any time. If a revision is material, we will provide notice prior to any new terms taking effect.</p>

    <h2>15. Contact Information</h2>
    <p>If you have any questions about these Terms, please contact us at:</p>
    <p>
        WeWinGames<br>
        Email: support@wewingames.com<br>
        Website: www.wewingames.com
    </p>
</div>
HTML;
    }

    private function getPrivacyContent(): string
    {
        return <<<HTML
<div class="legal-content">
    <p class="lead">Last Updated: January 1, 2025</p>

    <h2>1. Introduction</h2>
    <p>WeWinGames ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services.</p>

    <h2>2. Information We Collect</h2>
    <h3>Personal Information</h3>
    <p>We may collect personal information that you provide to us, including but not limited to:</p>
    <ul>
        <li>Name and contact information (email address, phone number)</li>
        <li>Account credentials (username and password)</li>
        <li>Billing information (processed securely through Stripe)</li>
        <li>Communication preferences</li>
    </ul>

    <h3>Automatically Collected Information</h3>
    <p>When you visit our website, we automatically collect certain information:</p>
    <ul>
        <li>IP address and location data</li>
        <li>Browser type and version</li>
        <li>Device information</li>
        <li>Pages visited and time spent on pages</li>
        <li>Referring website addresses</li>
    </ul>

    <h2>3. How We Use Your Information</h2>
    <p>We use the information we collect to:</p>
    <ul>
        <li>Provide and maintain our services</li>
        <li>Process transactions and send transaction notifications</li>
        <li>Send you marketing and promotional communications (with your consent)</li>
        <li>Respond to your comments, questions, and customer service requests</li>
        <li>Monitor and analyze usage and trends to improve user experience</li>
        <li>Detect, prevent, and address technical issues</li>
        <li>Comply with legal obligations</li>
    </ul>

    <h2>4. Information Sharing and Disclosure</h2>
    <p>We do not sell, trade, or rent your personal information to third parties. We may share your information in the following situations:</p>
    <ul>
        <li><strong>Service Providers:</strong> With third-party vendors who perform services on our behalf (e.g., Stripe for payment processing)</li>
        <li><strong>Legal Requirements:</strong> If required by law or in response to valid requests by public authorities</li>
        <li><strong>Business Transfers:</strong> In connection with any merger, sale of company assets, or acquisition</li>
        <li><strong>Protection of Rights:</strong> To protect the rights, property, or safety of WeWinGames, our users, or others</li>
    </ul>

    <h2>5. Data Security</h2>
    <p>We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the Internet is 100% secure.</p>

    <h2>6. Payment Processing</h2>
    <p>All payment transactions are processed through Stripe. We do not store or have access to your full credit card details. Stripe's privacy policy can be found at: https://stripe.com/privacy</p>

    <h2>7. Cookies and Tracking Technologies</h2>
    <p>We use cookies and similar tracking technologies to:</p>
    <ul>
        <li>Keep track of your preferences and settings</li>
        <li>Authenticate users and prevent fraud</li>
        <li>Analyze site traffic and usage</li>
        <li>Personalize content and improve user experience</li>
    </ul>
    <p>You can control cookies through your browser settings, but disabling cookies may limit your ability to use certain features of our service.</p>

    <h2>8. Third-Party Links</h2>
    <p>Our website may contain links to third-party websites. We are not responsible for the privacy practices or content of these external sites.</p>

    <h2>9. Children's Privacy</h2>
    <p>Our service is not intended for individuals under the age of 21. We do not knowingly collect personal information from anyone under 21 years of age.</p>

    <h2>10. Your Rights and Choices</h2>
    <p>You have the right to:</p>
    <ul>
        <li>Access and receive a copy of your personal information</li>
        <li>Update or correct your personal information</li>
        <li>Delete your personal information</li>
        <li>Object to or restrict processing of your information</li>
        <li>Opt-out of marketing communications</li>
    </ul>
    <p>To exercise these rights, please contact us at support@wewingames.com.</p>

    <h2>11. California Privacy Rights</h2>
    <p>California residents have additional rights under the California Consumer Privacy Act (CCPA), including the right to know what personal information is collected, used, shared, or sold.</p>

    <h2>12. Data Retention</h2>
    <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this Privacy Policy, unless a longer retention period is required by law.</p>

    <h2>13. International Data Transfers</h2>
    <p>Your information may be transferred to and processed in countries other than your country of residence. These countries may have data protection laws different from those in your country.</p>

    <h2>14. Changes to This Privacy Policy</h2>
    <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.</p>

    <h2>15. Contact Us</h2>
    <p>If you have questions or concerns about this Privacy Policy, please contact us at:</p>
    <p>
        WeWinGames<br>
        Email: support@wewingames.com<br>
        Website: www.wewingames.com<br>
        Address: [Your Business Address]
    </p>

    <h2>16. Legal Basis for Processing (GDPR)</h2>
    <p>If you are from the European Economic Area (EEA), our legal basis for collecting and using your personal information depends on the information concerned and the context in which we collect it.</p>
</div>
HTML;
    }
}