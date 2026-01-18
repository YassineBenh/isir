<?php

namespace App\Http\Requests;

use App\Rules\DiscordWebhookUrl;
use App\Rules\SlackWebhookUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDestinationRequest extends FormRequest
{
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
                'max:2048',
                Rule::when(
                    $this->input('type') === 'slack',
                    [new SlackWebhookUrl]
                ),
                Rule::when(
                    $this->input('type') === 'discord',
                    [new DiscordWebhookUrl]
                ),
            ],
            'email' => [
                Rule::requiredIf(fn () => $this->input('type') === 'email'),
                'nullable',
                'email:rfc',
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
            'email.required_if' => 'The email address is required for email destinations.',
            'email.email' => 'The email address must be a valid email address.',
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
