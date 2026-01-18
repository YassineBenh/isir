<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDestinationRequest extends FormRequest
{
    public const int MAX_DESTINATIONS_PER_USER = 10;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->destinations()->count() < self::MAX_DESTINATIONS_PER_USER;
    }

    /**
     * Get the authorization failure message.
     */
    protected function failedAuthorization(): void
    {
        abort(403, 'You have reached the maximum number of destinations ('.self::MAX_DESTINATIONS_PER_USER.').');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['slack', 'discord', 'email'])],
            'name' => ['required', 'string', 'max:255'],
            'webhook_url' => [
                Rule::requiredIf(fn () => in_array($this->input('type'), ['slack', 'discord'])),
                'nullable',
                'url:https',
                'max:2048',
            ],
            'email' => [
                Rule::requiredIf(fn () => $this->input('type') === 'email'),
                'nullable',
                'email',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'webhook_url.required_if' => 'The webhook URL is required for Slack and Discord destinations.',
            'webhook_url.url' => 'The webhook URL must be a valid HTTPS URL.',
            'email.required_if' => 'The email address is required for email destinations.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'webhook_url' => 'webhook URL',
        ];
    }
}
