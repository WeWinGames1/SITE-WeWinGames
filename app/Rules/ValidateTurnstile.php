<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValidateTurnstile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if Turnstile is disabled
        if (!config('services.turnstile.enabled')) {
            return;
        }

        // Skip if no value provided
        if (empty($value)) {
            $fail('Please complete the security verification.');
            return;
        }

        try {
            // Verify the token with Cloudflare
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            $result = $response->json();

            // Log the response for debugging
            Log::info('Turnstile verification response', [
                'success' => $result['success'] ?? false,
                'error-codes' => $result['error-codes'] ?? [],
                'challenge_ts' => $result['challenge_ts'] ?? null,
                'hostname' => $result['hostname'] ?? null,
            ]);

            if (!($result['success'] ?? false)) {
                $errorCodes = $result['error-codes'] ?? [];
                
                // Map error codes to user-friendly messages
                $errorMessages = [
                    'missing-input-secret' => 'Security configuration error. Please contact support.',
                    'invalid-input-secret' => 'Security configuration error. Please contact support.',
                    'missing-input-response' => 'Security verification failed. Please try again.',
                    'invalid-input-response' => 'Security verification failed. Please try again.',
                    'bad-request' => 'Invalid security request. Please refresh and try again.',
                    'timeout-or-duplicate' => 'Security verification expired. Please try again.',
                    'internal-error' => 'Security service temporarily unavailable. Please try again.',
                ];

                $message = 'Security verification failed.';
                foreach ($errorCodes as $code) {
                    if (isset($errorMessages[$code])) {
                        $message = $errorMessages[$code];
                        break;
                    }
                }

                $fail($message);
            }
        } catch (\Exception $e) {
            Log::error('Turnstile verification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Don't block registration if Turnstile service is down
            // But log the issue for monitoring
            Log::warning('Turnstile verification failed, allowing registration', [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}