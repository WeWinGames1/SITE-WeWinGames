<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Traits\ValidatesEmail;
use App\Models\User;
use App\Rules\ValidateTurnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    use ValidatesEmail;

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
                'email:rfc,dns',
                'max:255',
                'unique:'.User::class,
                $this->getEmailValidationClosure(),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^[+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/',
                'unique:users,phone',
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'discord_username' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Only validate format if a value is provided
                    if ($value) {
                        // Support both old format (username#1234) and new format (username)
                        // New format: 2-32 characters, letters (case-insensitive), numbers, underscores, periods
                        // Old format: username#1234
                        $newFormat = '/^[a-zA-Z0-9._]{2,32}$/';
                        $oldFormat = '/^[a-zA-Z0-9._-]+#[0-9]{4}$/';

                        if (! preg_match($newFormat, $value) && ! preg_match($oldFormat, $value)) {
                            $fail('Please enter a valid Discord username (e.g., username or username#1234).');
                        }
                    }
                },
            ],
            'favorite_team' => ['nullable', 'string', 'max:255'],
            'favorite_sport' => ['nullable', 'string', 'max:255'],
            'primary_betting_app' => ['nullable', 'string', 'max:255'],
            // Honeypot field - should be empty
            'website' => 'present|max:0',
            // Time-based validation
            'timestamp' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $timeTaken = time() - $value;
                    // Form filled too quickly (less than 1 second)
                    if ($timeTaken < 1) {
                        $fail('Please take your time to fill out the form.');
                    }
                    // Form took too long (more than 30 minutes)
                    if ($timeTaken > 1800) {
                        $fail('This form has expired. Please refresh and try again.');
                    }
                },
            ],
            // Cloudflare Turnstile token
            'cf-turnstile-response' => $this->getTurnstileRules(),
        ];
    }

    /**
     * Get Turnstile validation rules
     */
    private function getTurnstileRules(): array|string
    {
        if (! config('services.turnstile.enabled')) {
            return 'nullable';
        }

        // Check if ValidateTurnstile class exists
        if (class_exists(ValidateTurnstile::class)) {
            return ['required', 'string', new ValidateTurnstile];
        }

        // Fallback if class doesn't exist (for deployment issues)
        \Log::warning('ValidateTurnstile class not found, using basic validation only');

        return 'required|string';
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
            if (! $validator->errors()->isEmpty()) {
                // Log validation failures for monitoring
                $this->logSuspiciousActivity('validation_failed');
            }
        });
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
