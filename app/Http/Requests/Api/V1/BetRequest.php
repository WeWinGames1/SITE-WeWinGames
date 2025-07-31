<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BetRequest extends FormRequest
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
        $rules = [
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'operator_id' => ['required', 'integer', 'exists:operators,id'],
            'bet_type' => ['required', 'string', 'max:50'],
            'selection' => ['required', 'string', 'max:255'],
            'odds' => ['required', 'numeric', 'min:1.01', 'max:1000'],
            'stake' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'description' => ['nullable', 'string', 'max:500'],
        ];

        // Additional rules for updates
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Make fields optional for updates
            $rules = array_map(function ($rule) {
                $key = array_search('required', $rule);
                if ($key !== false) {
                    $rule[$key] = 'sometimes';
                }

                return $rule;
            }, $rules);

            // Add status validation for updates
            $rules['status'] = [
                'sometimes',
                Rule::in(['pending', 'won', 'loss', 'void', 'cashout', 'push']),
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'sport_id.exists' => 'The selected sport does not exist.',
            'game_id.exists' => 'The selected game does not exist.',
            'operator_id.exists' => 'The selected operator does not exist.',
            'odds.min' => 'Odds must be at least 1.01.',
            'odds.max' => 'Odds cannot exceed 1000.',
            'stake.min' => 'Minimum stake is 0.01.',
            'stake.max' => 'Maximum stake is 100,000.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert odds format if needed
        if ($this->has('odds')) {
            $odds = $this->input('odds');

            // Handle American odds format
            if (is_string($odds) && (str_starts_with($odds, '+') || str_starts_with($odds, '-'))) {
                $this->merge([
                    'odds' => $this->convertAmericanToDecimal($odds),
                ]);
            }
        }
    }

    /**
     * Convert American odds to decimal format
     */
    private function convertAmericanToDecimal(string $americanOdds): float
    {
        $odds = (int) $americanOdds;

        if ($odds > 0) {
            return ($odds / 100) + 1;
        } else {
            return (100 / abs($odds)) + 1;
        }
    }
}
