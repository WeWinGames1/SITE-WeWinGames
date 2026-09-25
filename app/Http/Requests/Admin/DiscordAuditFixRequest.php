<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DiscordAuditFixRequest extends FormRequest
{
    /**
     * Admin access is enforced by the route middleware
     */
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
            'scope' => ['required', 'in:linked,unlinked'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scope.required' => 'Choose which members to fix.',
            'scope.in' => 'Scope must be either linked or unlinked members.',
        ];
    }
}
