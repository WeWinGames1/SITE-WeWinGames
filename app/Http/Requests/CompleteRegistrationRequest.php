<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompleteRegistrationRequest extends FormRequest
{
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
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'discord_username' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $newFormat = '/^[a-zA-Z0-9._]{2,32}$/';
                        $oldFormat = '/^[a-zA-Z0-9._-]+#[0-9]{4}$/';

                        if (! preg_match($newFormat, $value) && ! preg_match($oldFormat, $value)) {
                            $fail('Please enter a valid Discord username (e.g., username or username#1234).');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Invalid or missing completion token.',
            'token.size' => 'Invalid completion token format.',
            'password.required' => 'Please create a password.',
            'password.confirmed' => 'The passwords do not match.',
        ];
    }
}
