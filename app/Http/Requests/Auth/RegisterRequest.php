<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Cache;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-zA-Z\s\-\.]+$/', // Only letters, spaces, hyphens, dots
                'not_regex:/(.)\1{2,}/', // No repeated characters more than twice
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns', // Stricter email validation with DNS check
                'max:255',
                'unique:'.User::class,
                function ($attribute, $value, $fail) {
                    // Check against disposable email domains
                    if ($this->isDisposableEmail($value)) {
                        $fail('Please use a permanent email address.');
                    }
                    
                    // Check for suspicious patterns
                    if ($this->hasSuspiciousEmailPattern($value)) {
                        $fail('This email address cannot be used for registration.');
                    }
                }
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Honeypot field - should be empty
            'website' => 'present|max:0',
            // Time-based validation
            'timestamp' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $timeTaken = time() - $value;
                    // Form filled too quickly (less than 3 seconds)
                    if ($timeTaken < 3) {
                        $fail('Please take your time to fill out the form.');
                    }
                    // Form took too long (more than 30 minutes)
                    if ($timeTaken > 1800) {
                        $fail('This form has expired. Please refresh and try again.');
                    }
                }
            ],
            // Cloudflare Turnstile token
            'cf-turnstile-response' => config('services.turnstile.enabled') ? 'required|string' : 'nullable',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => 'Please complete the security verification.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional checks after basic validation
            if (!$validator->errors()->isEmpty()) {
                // Log validation failures for monitoring
                $this->logSuspiciousActivity('validation_failed');
            }
        });
    }

    /**
     * Check if email is from a disposable email service
     */
    private function isDisposableEmail(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);
        
        // Common disposable email domains
        $disposableDomains = [
            'mailinator.com', 'guerrillamail.com', '10minutemail.com',
            'tempmail.com', 'throwaway.email', 'yopmail.com',
            'maildrop.cc', 'mintemail.com', 'temp-mail.org',
            'fakeinbox.com', 'sharklasers.com', 'guerrillamail.info',
            'spam4.me', 'grr.la', 'mailnesia.com', 'tempmailaddress.com',
            'getairmail.com', 'throwawaymail.com', 'tempmail.net'
        ];
        
        // Check against cached blacklist
        $cachedBlacklist = Cache::get('disposable_email_domains', []);
        $allBlacklisted = array_merge($disposableDomains, $cachedBlacklist);
        
        return in_array($domain, $allBlacklisted);
    }

    /**
     * Check for suspicious email patterns
     */
    private function hasSuspiciousEmailPattern(string $email): bool
    {
        $localPart = strstr($email, '@', true);
        
        // Check for excessive numbers in email
        if (preg_match('/\d{5,}/', $localPart)) {
            return true;
        }
        
        // Check for random character patterns
        if (preg_match('/^[a-z]{1,2}\d{6,}/', $localPart)) {
            return true;
        }
        
        // Check for known spam patterns
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
     * Log suspicious registration activity
     */
    private function logSuspiciousActivity(string $reason): void
    {
        $data = [
            'reason' => $reason,
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'email' => $this->input('email'),
            'timestamp' => now(),
        ];
        
        \Log::channel('security')->warning('Suspicious registration attempt', $data);
    }
}