import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

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
import { type Destination, type DestinationType } from '@/types';

interface DestinationFormProps {
    destination?: Destination;
    onCancel?: () => void;
}

interface FormData {
    type: DestinationType;
    name: string;
    webhook_url: string;
    email: string;
}

export function DestinationForm({
    destination,
    onCancel,
}: DestinationFormProps) {
    const isEditing = !!destination;

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        type: destination?.type ?? 'slack',
        name: destination?.name ?? '',
        webhook_url: destination?.config.webhook_url ?? '',
        email: destination?.config.email ?? '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        if (isEditing) {
            put(DestinationController.update(destination).url);
        } else {
            post(DestinationController.store().url);
        }
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
                <InputError message={errors.type} />
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
                <InputError message={errors.name} />
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
                    <InputError message={errors.webhook_url} />
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
                    <InputError message={errors.email} />
                </div>
            )}

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
                          ? 'Update Destination'
                          : 'Create Destination'}
                </Button>
            </div>
        </form>
    );
}
