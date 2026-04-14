<?php

namespace App\Http\Requests\Traits;

use Illuminate\Support\Facades\Cache;

trait ValidatesEmail
{
    protected function isDisposableEmail(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);

        $disposableDomains = [
            'mailinator.com', 'guerrillamail.com', '10minutemail.com',
            'tempmail.com', 'throwaway.email', 'yopmail.com',
            'maildrop.cc', 'mintemail.com', 'temp-mail.org',
            'fakeinbox.com', 'sharklasers.com', 'guerrillamail.info',
            'spam4.me', 'grr.la', 'mailnesia.com', 'tempmailaddress.com',
            'getairmail.com', 'throwawaymail.com', 'tempmail.net',
        ];

        $cachedBlacklist = Cache::get('disposable_email_domains', []);
        $allBlacklisted = array_merge($disposableDomains, $cachedBlacklist);

        return in_array($domain, $allBlacklisted);
    }

    protected function hasSuspiciousEmailPattern(string $email): bool
    {
        $localPart = strstr($email, '@', true);

        // Too many consecutive digits
        if (preg_match('/\d{5,}/', $localPart)) {
            return true;
        }

        // Pattern like a1234567@
        if (preg_match('/^[a-z]{1,2}\d{6,}/', $localPart)) {
            return true;
        }

        $spamPatterns = [
            '/^test\d+@/',
            '/^user\d+@/',
            '/^temp\d+@/',
            '/^spam/',
            '/^fake/',
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get email validation closure for use in rules
     */
    protected function getEmailValidationClosure(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($this->isDisposableEmail($value)) {
                $fail('Please use a permanent email address.');
            }

            if ($this->hasSuspiciousEmailPattern($value)) {
                $fail('This email address cannot be used for registration.');
            }
        };
    }
}
