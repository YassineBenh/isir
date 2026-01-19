import { useForm } from '@inertiajs/react';
import { AxiosError } from 'axios';
import { type FormEvent, useState } from 'react';

import DestinationController from '@/actions/App/Http/Controllers/DestinationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import axios from '@/lib/axios';
import { type Destination, type DestinationType } from '@/types';

interface DestinationFormProps {
    destination?: Destination;
    defaultType?: DestinationType;
    onCancel?: () => void;
    onSuccess?: (destination: Destination) => void;
}

interface FormData {
    type: DestinationType;
    name: string;
    webhook_url: string;
    email: string;
}

export function DestinationForm({
    destination,
    defaultType,
    onCancel,
    onSuccess,
}: DestinationFormProps) {
    const isEditing = !!destination;
    const [fetchErrors, setFetchErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, post, put, processing, errors, reset } =
        useForm<FormData>({
            type: destination?.type ?? defaultType ?? 'slack',
            name: destination?.name ?? '',
            webhook_url: destination?.config.webhook_url ?? '',
            email: destination?.config.email ?? '',
        });

    const allErrors = { ...errors, ...fetchErrors };
    const isProcessing = processing || isSubmitting;

    async function handleSubmit(e: FormEvent) {
        e.preventDefault();
        e.stopPropagation();
        setFetchErrors({});

        if (isEditing) {
            put(DestinationController.update(destination).url);
            return;
        }

        // Modal mode: use axios with no_redirect
        if (onSuccess) {
            setIsSubmitting(true);

            try {
                const response = await axios.post(
                    DestinationController.store().url,
                    { ...data, no_redirect: true },
                );

                reset();
                onSuccess(response.data.destination);
            } catch (error) {
                if (
                    error instanceof AxiosError &&
                    error.response?.status === 422
                ) {
                    setFetchErrors(error.response.data.errors ?? {});
                }
            } finally {
                setIsSubmitting(false);
            }
            return;
        }

        // Normal mode: use Inertia post with redirect
        post(DestinationController.store().url);
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid gap-2">
                <Label htmlFor="type">Type</Label>
                <Select
                    value={data.type}
                    onValueChange={(value) =>
                        setData('type', value as DestinationType)
                    }
                    disabled={isEditing}
                >
                    <SelectTrigger id="type">
                        <SelectValue placeholder="Select a type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="slack">Slack</SelectItem>
                        <SelectItem value="discord">Discord</SelectItem>
                        <SelectItem value="email">Email</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={allErrors.type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder={
                        data.type === 'email'
                            ? 'Personal Email'
                            : '#channel-name'
                    }
                    required
                />
                <InputError message={allErrors.name} />
            </div>

            {data.type !== 'email' && (
                <div className="grid gap-2">
                    <Label htmlFor="webhook_url">Webhook URL</Label>
                    <Input
                        id="webhook_url"
                        type="url"
                        value={data.webhook_url}
                        onChange={(e) => setData('webhook_url', e.target.value)}
                        placeholder={
                            data.type === 'slack'
                                ? 'https://hooks.slack.com/services/...'
                                : 'https://discord.com/api/webhooks/...'
                        }
                        required
                    />
                    <p className="text-xs text-muted-foreground">
                        {data.type === 'slack'
                            ? 'Create an incoming webhook in your Slack workspace settings.'
                            : 'Create a webhook in your Discord server settings.'}
                    </p>
                    <InputError message={allErrors.webhook_url} />
                </div>
            )}

            {data.type === 'email' && (
                <div className="grid gap-2">
                    <Label htmlFor="email">Email Address</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="you@example.com"
                        required
                    />
                    <InputError message={allErrors.email} />
                </div>
            )}

            <div className="flex justify-end gap-3">
                {onCancel && (
                    <Button type="button" variant="outline" onClick={onCancel}>
                        Cancel
                    </Button>
                )}
                <Button type="submit" disabled={isProcessing}>
                    {isProcessing
                        ? 'Saving...'
                        : isEditing
                          ? 'Update Destination'
                          : 'Create Destination'}
                </Button>
            </div>
        </form>
    );
}
