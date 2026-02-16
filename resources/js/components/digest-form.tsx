import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { type FormEvent, type KeyboardEvent, useState } from 'react';

import DigestController from '@/actions/App/Http/Controllers/DigestController';
import { DestinationModal } from '@/components/destination-modal';
import InputError from '@/components/input-error';
import { RepoChip } from '@/components/repo-chip';
import { ScheduleSelector } from '@/components/schedule-selector';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    type Destination,
    type DestinationsByType,
    type Digest,
    type DigestFrequency,
} from '@/types';


interface DigestFormProps {
    digest?: Digest;
    destinations: DestinationsByType;
    timezones: string[];
    maxRepos: number;
    aiConfigured?: boolean;
    onCancel?: () => void;
}

interface FormData {
    name: string;
    frequency: DigestFrequency;
    timezone: string;
    send_time: string;
    send_day_of_week: number | null;
    is_enabled: boolean;
    ai_enabled: boolean;
    include_versions_summary: boolean;
    source_urls: string[];
    slack_destination_id: number | '';
    discord_destination_id: number | '';
    email_destination_id: number | '';
}

// GitHub URL/repo pattern
const GITHUB_PATTERN =
    /^(?:(?:https?:\/\/)?github\.com\/)?([a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?)\/([a-zA-Z0-9._-]+?)(?:\.git)?$/;

function parseGitHubUrl(input: string): string | null {
    const match = input.trim().match(GITHUB_PATTERN);
    if (match) {
        return `${match[1]}/${match[2]}`;
    }
    return null;
}

function getDestinationIdForType(
    digest: Digest | undefined,
    type: 'slack' | 'discord' | 'email',
): number | '' {
    const dest = digest?.destinations?.find((d) => d.type === type);
    return dest?.id ?? '';
}

export function DigestForm({
    digest,
    destinations,
    timezones,
    maxRepos,
    aiConfigured = false,
    onCancel,
}: DigestFormProps) {
    const isEditing = !!digest;
    const [repoInput, setRepoInput] = useState('');
    const [repoError, setRepoError] = useState<string | null>(null);

    const { data, setData, post, put, processing, errors, clearErrors } =
        useForm<FormData>({
            name: digest?.name ?? '',
            frequency: digest?.frequency ?? 'daily',
            timezone: digest?.timezone ?? 'UTC',
            send_time: digest?.send_time?.slice(0, 5) ?? '09:00',
            send_day_of_week: digest?.send_day_of_week ?? 1,
            is_enabled: digest?.is_enabled ?? true,
            ai_enabled: digest?.ai_enabled ?? true,
            include_versions_summary: digest?.include_versions_summary ?? false,
            source_urls: digest?.sources?.map((s) => s.name) ?? [],
            slack_destination_id: getDestinationIdForType(digest, 'slack'),
            discord_destination_id: getDestinationIdForType(digest, 'discord'),
            email_destination_id: getDestinationIdForType(digest, 'email'),
        });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        if (data.source_urls.length === 0) {
            setRepoError('At least one repository is required.');
            return;
        }

        if (isEditing) {
            put(DigestController.update(digest).url);
        } else {
            post(DigestController.store().url);
        }
    }

    function handleAddRepo() {
        const normalized = parseGitHubUrl(repoInput);

        if (!normalized) {
            setRepoError('Invalid format. Use owner/repo or a GitHub URL.');
            return;
        }

        if (data.source_urls.includes(normalized)) {
            setRepoError('This repository is already added.');
            return;
        }

        if (maxRepos !== -1 && data.source_urls.length >= maxRepos) {
            setRepoError(`You cannot add more than ${maxRepos} repositories.`);
            return;
        }

        setData('source_urls', [...data.source_urls, normalized]);
        setRepoInput('');
        setRepoError(null);
        // Clear any previous server-side validation errors for repos
        clearErrors(
            ...(Object.keys(errors).filter((key) =>
                key.startsWith('source_urls'),
            ) as (keyof typeof errors)[]),
        );
    }

    function handleRepoKeyDown(e: KeyboardEvent<HTMLInputElement>) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAddRepo();
        }
    }

    function handleRemoveRepo(repo: string) {
        setData(
            'source_urls',
            data.source_urls.filter((r) => r !== repo),
        );
        // Clear any previous server-side validation errors for repos
        clearErrors(
            ...(Object.keys(errors).filter((key) =>
                key.startsWith('source_urls'),
            ) as (keyof typeof errors)[]),
        );
    }

    const slackDestinations = destinations.slack ?? [];
    const discordDestinations = destinations.discord ?? [];
    const emailDestinations = destinations.email ?? [];

    const hasNoDestinations =
        slackDestinations.length === 0 &&
        discordDestinations.length === 0 &&
        emailDestinations.length === 0;

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {/* Basic Info */}
            <div className="grid gap-2">
                <Label htmlFor="name">Digest Name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="My Release Digest"
                    required
                />
                <InputError message={errors.name} />
            </div>

            <Separator />

            {/* Schedule */}
            <div className="space-y-4">
                <h3 className="text-sm font-medium">Schedule</h3>
                <ScheduleSelector
                    frequency={data.frequency}
                    sendTime={data.send_time}
                    sendDayOfWeek={data.send_day_of_week}
                    timezone={data.timezone}
                    timezones={timezones}
                    onFrequencyChange={(value) => setData('frequency', value)}
                    onSendTimeChange={(value) => setData('send_time', value)}
                    onSendDayOfWeekChange={(value) =>
                        setData('send_day_of_week', value)
                    }
                    onTimezoneChange={(value) => setData('timezone', value)}
                    errors={errors}
                />
            </div>

            <Separator />

            {/* Repositories */}
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h3 className="text-sm font-medium">GitHub Repositories</h3>
                    {maxRepos !== -1 && (
                        <span className="text-xs text-muted-foreground">
                            {data.source_urls.length}/{maxRepos}
                        </span>
                    )}
                </div>
                <p className="text-xs text-muted-foreground">
                    Add repositories to track. Paste a GitHub URL or use
                    owner/repo format.
                </p>

                <div className="flex gap-2">
                    <Input
                        value={repoInput}
                        onChange={(e) => {
                            setRepoInput(e.target.value);
                            setRepoError(null);
                        }}
                        onKeyDown={handleRepoKeyDown}
                        placeholder="owner/repo or https://github.com/owner/repo"
                        className="flex-1"
                    />
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={handleAddRepo}
                        disabled={
                            !repoInput.trim() ||
                            (maxRepos !== -1 &&
                                data.source_urls.length >= maxRepos)
                        }
                    >
                        <Plus className="mr-1 size-4" />
                        Add
                    </Button>
                </div>
                <InputError message={repoError ?? errors.source_urls} />
                {/* Show summary if there are individual repo errors */}
                {!repoError &&
                    !errors.source_urls &&
                    data.source_urls.some(
                        (_, index) =>
                            errors[
                                `source_urls.${index}` as keyof typeof errors
                            ],
                    ) && (
                        <InputError message="One or more repositories could not be verified. Hover over red badges for details." />
                    )}

                {data.source_urls.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                        {data.source_urls.map((repo, index) => (
                            <RepoChip
                                key={repo}
                                repo={repo}
                                onRemove={() => handleRemoveRepo(repo)}
                                disabled={processing}
                                error={
                                    errors[
                                        `source_urls.${index}` as keyof typeof errors
                                    ]
                                }
                            />
                        ))}
                    </div>
                )}
            </div>

            <Separator />

            {/* Destinations */}
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h3 className="text-sm font-medium">Destinations</h3>
                    <DestinationModal />
                </div>
                <p className="text-xs text-muted-foreground">
                    Select where to deliver your digest. You can choose one
                    destination per type.
                </p>

                {hasNoDestinations ? (
                    <div className="rounded-lg border border-dashed p-4 text-center">
                        <p className="text-sm text-muted-foreground">
                            No destinations configured yet.
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Click "Add New" above to create your first
                            destination.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-3">
                        <DestinationSelect
                            label="Slack"
                            destinations={slackDestinations}
                            value={data.slack_destination_id}
                            onChange={(value) =>
                                setData('slack_destination_id', value)
                            }
                            error={errors.slack_destination_id}
                        />
                        <DestinationSelect
                            label="Discord"
                            destinations={discordDestinations}
                            value={data.discord_destination_id}
                            onChange={(value) =>
                                setData('discord_destination_id', value)
                            }
                            error={errors.discord_destination_id}
                        />
                        <DestinationSelect
                            label="Email"
                            destinations={emailDestinations}
                            value={data.email_destination_id}
                            onChange={(value) =>
                                setData('email_destination_id', value)
                            }
                            error={errors.email_destination_id}
                        />
                    </div>
                )}
            </div>

            <Separator />

            {/* AI Settings */}
            <div className="space-y-4">
                <h3 className="text-sm font-medium">AI Summary</h3>
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="ai_enabled"
                        checked={aiConfigured && data.ai_enabled}
                        disabled={!aiConfigured}
                        onCheckedChange={(checked) =>
                            setData('ai_enabled', checked === true)
                        }
                    />
                    <Label
                        htmlFor="ai_enabled"
                        className="text-sm leading-none font-normal peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                        Generate AI summaries for releases
                    </Label>
                </div>
                {aiConfigured ? (
                    <p className="text-xs text-muted-foreground">
                        When enabled, each release will include an AI-generated
                        summary of the changes.
                    </p>
                ) : (
                    <p className="text-xs text-muted-foreground">
                        AI is not configured. Set a provider key like{' '}
                        <code className="rounded bg-muted px-1 py-0.5 text-xs">
                            OPENAI_API_KEY
                        </code>{' '}
                        and choose a default provider via{' '}
                        <code className="rounded bg-muted px-1 py-0.5 text-xs">
                            AI_DEFAULT_PROVIDER
                        </code>
                        . You can also use provider-specific keys like{' '}
                        <code className="rounded bg-muted px-1 py-0.5 text-xs">
                            ANTHROPIC_API_KEY
                        </code>
                        .{' '}
                        <a
                            href="https://github.com/yassinebenh/isir#ai-summaries"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-primary underline underline-offset-2"
                        >
                            Learn more
                        </a>
                    </p>
                )}
            </div>

            <Separator />

            {/* Notification Content */}
            <div className="space-y-4">
                <h3 className="text-sm font-medium">Notification Content</h3>
                <div className="flex items-start space-x-2">
                    <Checkbox
                        id="include_versions_summary"
                        checked={data.include_versions_summary}
                        onCheckedChange={(checked) =>
                            setData(
                                'include_versions_summary',
                                checked === true,
                            )
                        }
                    />
                    <div className="grid gap-1.5 leading-none">
                        <Label
                            htmlFor="include_versions_summary"
                            className="text-sm leading-none font-normal"
                        >
                            Include a summary of versions released in the
                            notification
                        </Label>
                        <p className="text-xs text-muted-foreground">
                            When enabled, notifications include a bullet list of
                            release titles from this digest run.
                        </p>
                        <InputError message={errors.include_versions_summary} />
                    </div>
                </div>
            </div>

            {/* Submit */}
            <div className="flex justify-end gap-3">
                {onCancel && (
                    <Button type="button" variant="outline" onClick={onCancel}>
                        Cancel
                    </Button>
                )}
                <Button type="submit" disabled={processing}>
                    {processing
                        ? 'Saving...'
                        : isEditing
                          ? 'Update Digest'
                          : 'Create Digest'}
                </Button>
            </div>
        </form>
    );
}

interface DestinationSelectProps {
    label: string;
    destinations: Destination[];
    value: number | '';
    onChange: (value: number | '') => void;
    error?: string;
}

function DestinationSelect({
    label,
    destinations,
    value,
    onChange,
    error,
}: DestinationSelectProps) {
    if (destinations.length === 0) {
        return (
            <div className="grid gap-2">
                <Label className="text-muted-foreground">{label}</Label>
                <p className="text-xs text-muted-foreground">
                    No {label.toLowerCase()} destinations
                </p>
            </div>
        );
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor={`${label.toLowerCase()}_destination`}>
                {label}
            </Label>
            <Select
                value={value === '' ? 'none' : value.toString()}
                onValueChange={(val) =>
                    onChange(val === 'none' ? '' : parseInt(val, 10))
                }
            >
                <SelectTrigger id={`${label.toLowerCase()}_destination`}>
                    <SelectValue placeholder={`Select ${label}`} />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">None</SelectItem>
                    {destinations.map((dest) => (
                        <SelectItem key={dest.id} value={dest.id.toString()}>
                            {dest.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} />
        </div>
    );
}
