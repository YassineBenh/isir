<?php

namespace App\Http\Requests;

use App\Rules\GitHubRepoExists;
use App\Rules\GitHubRepoUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDigestRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'frequency' => ['required', 'string', Rule::in(['daily', 'weekly'])],
            'timezone' => ['required', 'string', Rule::in(config('isir.timezones'))],
            'send_time' => ['required', 'date_format:H:i'],
            'send_day_of_week' => [
                Rule::requiredIf(fn () => $this->input('frequency') === 'weekly'),
                'nullable',
                'integer',
                'between:0,6',
            ],
            'is_enabled' => ['boolean'],
            'ai_enabled' => ['boolean'],
            'include_versions_summary' => ['boolean'],
            'source_urls' => [
                'required',
                'array',
                'min:1',
                ...($this->maxGithubRepos() !== -1 ? ['max:'.$this->maxGithubRepos()] : []),
            ],
            'source_urls.*' => ['required', 'string', new GitHubRepoUrl, new GitHubRepoExists],
            'slack_destination_id' => [
                'nullable',
                'integer',
                Rule::exists('destinations', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id)
                        ->where('type', 'slack');
                }),
            ],
            'discord_destination_id' => [
                'nullable',
                'integer',
                Rule::exists('destinations', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id)
                        ->where('type', 'discord');
                }),
            ],
            'email_destination_id' => [
                'nullable',
                'integer',
                Rule::exists('destinations', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id)
                        ->where('type', 'email');
                }),
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
            'source_urls.required' => 'At least one GitHub repository is required.',
            'source_urls.min' => 'At least one GitHub repository is required.',
            'source_urls.max' => 'You cannot add more than :max repositories.',
            'send_day_of_week.required_if' => 'The day of week is required for weekly digests.',
            'slack_destination_id.exists' => 'The selected Slack destination is invalid.',
            'discord_destination_id.exists' => 'The selected Discord destination is invalid.',
            'email_destination_id.exists' => 'The selected email destination is invalid.',
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
            'source_urls' => 'repositories',
            'source_urls.*' => 'repository URL',
            'send_time' => 'send time',
            'send_day_of_week' => 'day of week',
            'include_versions_summary' => 'version summary in notification',
            'slack_destination_id' => 'Slack destination',
            'discord_destination_id' => 'Discord destination',
            'email_destination_id' => 'email destination',
        ];
    }

    /**
     * Get the destination IDs as an array (filtering out nulls).
     *
     * @return array<int>
     */
    public function destinationIds(): array
    {
        return array_filter([
            $this->validated('slack_destination_id'),
            $this->validated('discord_destination_id'),
            $this->validated('email_destination_id'),
        ]);
    }

    /**
     * Get the maximum number of GitHub repos per digest.
     */
    public function maxGithubRepos(): int
    {
        return config('isir.limits.github_repos_per_digest');
    }
}
