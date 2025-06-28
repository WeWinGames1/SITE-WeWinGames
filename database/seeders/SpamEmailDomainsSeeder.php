<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpamEmailDomainsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = [
            // Most common disposable email services
            ['domain' => 'mailinator.com', 'reason' => 'Disposable email service'],
            ['domain' => 'guerrillamail.com', 'reason' => 'Disposable email service'],
            ['domain' => 'guerrillamail.info', 'reason' => 'Disposable email service'],
            ['domain' => 'guerrillamail.net', 'reason' => 'Disposable email service'],
            ['domain' => 'guerrillamail.org', 'reason' => 'Disposable email service'],
            ['domain' => '10minutemail.com', 'reason' => 'Disposable email service'],
            ['domain' => 'tempmail.com', 'reason' => 'Disposable email service'],
            ['domain' => 'temp-mail.org', 'reason' => 'Disposable email service'],
            ['domain' => 'throwaway.email', 'reason' => 'Disposable email service'],
            ['domain' => 'yopmail.com', 'reason' => 'Disposable email service'],
            ['domain' => 'maildrop.cc', 'reason' => 'Disposable email service'],
            ['domain' => 'mintemail.com', 'reason' => 'Disposable email service'],
            ['domain' => 'fakeinbox.com', 'reason' => 'Disposable email service'],
            ['domain' => 'sharklasers.com', 'reason' => 'Disposable email service'],
            ['domain' => 'spam4.me', 'reason' => 'Disposable email service'],
            ['domain' => 'grr.la', 'reason' => 'Disposable email service'],
            ['domain' => 'mailnesia.com', 'reason' => 'Disposable email service'],
            ['domain' => 'tempmailaddress.com', 'reason' => 'Disposable email service'],
            ['domain' => 'getairmail.com', 'reason' => 'Disposable email service'],
            ['domain' => 'throwawaymail.com', 'reason' => 'Disposable email service'],
            ['domain' => 'tempmail.net', 'reason' => 'Disposable email service'],
            ['domain' => 'trashmail.com', 'reason' => 'Disposable email service'],
            ['domain' => 'getnada.com', 'reason' => 'Disposable email service'],
            ['domain' => 'mohmal.com', 'reason' => 'Disposable email service'],
            ['domain' => 'dispostable.com', 'reason' => 'Disposable email service'],
            ['domain' => 'emailondeck.com', 'reason' => 'Disposable email service'],
            ['domain' => 'tempr.email', 'reason' => 'Disposable email service'],
            ['domain' => 'temporarymail.net', 'reason' => 'Disposable email service'],
            ['domain' => 'slippery.email', 'reason' => 'Disposable email service'],
            ['domain' => 'randommail.net', 'reason' => 'Disposable email service'],
        ];

        foreach ($domains as $domain) {
            DB::table('spam_email_domains')->updateOrInsert(
                ['domain' => $domain['domain']],
                array_merge($domain, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Spam email domains seeded successfully!');
    }
}