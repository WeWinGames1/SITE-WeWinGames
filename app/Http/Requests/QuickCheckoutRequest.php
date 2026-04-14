<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\ValidatesEmail;
use App\Models\User;
use App\Rules\ValidateTurnstile;
use Illuminate\Foundation\Http\FormRequest;

class QuickCheckoutRequest extends FormRequest
{
    use ValidatesEmail;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-zA-Z\s\-\.]+$/',
                'not_regex:/(.)\1{2,}/',
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
            'payment_method' => ['required', 'string'],
            'price_id' => ['required', 'string'],
            'coupon' => ['nullable', 'string', 'max:50'],
            'website' => 'present|max:0', // Honeypot
            'timestamp' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $timeTaken = time() - $value;
                    if ($timeTaken < 1) {
                        $fail('Please take your time to fill out the form.');
                    }
                    if ($timeTaken > 1800) {
                        $fail('This form has expired. Please refresh and try again.');
                    }
                },
            ],
            'cf-turnstile-response' => $this->getTurnstileRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'name.regex' => 'Name can only contain letters, spaces, hyphens, and periods.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'An account with this email already exists.',
            'phone.required' => 'Please enter your phone number.',
            'phone.regex' => 'Please enter a valid phone number.',
            'phone.unique' => 'This phone number is already registered.',
            'payment_method.required' => 'Please add a payment method.',
            'price_id.required' => 'Please select a subscription plan.',
            'cf-turnstile-response.required' => 'Please complete the security verification.',
        ];
    }

    private function getTurnstileRules(): array|string
    {
        if (! config('services.turnstile.enabled')) {
            return 'nullable';
        }

        if (class_exists(ValidateTurnstile::class)) {
            return ['required', 'string', new ValidateTurnstile];
        }

        return 'required|string';
    }
}
