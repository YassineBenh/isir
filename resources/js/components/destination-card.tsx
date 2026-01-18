import { Link, router } from '@inertiajs/react';
import {
    Mail,
    MessageSquare,
    MoreHorizontal,
    Pencil,
    Slack,
    Trash2,
} from 'lucide-react';

import DestinationController from '@/actions/App/Http/Controllers/DestinationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Switch } from '@/components/ui/switch';
import { type Destination, type DestinationType } from '@/types';

const typeConfig: Record<
    DestinationType,
    { icon: typeof Slack; label: string; color: string }
> = {
    slack: {
        icon: Slack,
        label: 'Slack',
        color: 'bg-[#4A154B] text-white',
    },
    discord: {
        icon: MessageSquare,
        label: 'Discord',
        color: 'bg-[#5865F2] text-white',
    },
    email: {
        icon: Mail,
        label: 'Email',
        color: 'bg-blue-600 text-white',
    },
};

interface DestinationCardProps {
    destination: Destination;
}

export function DestinationCard({ destination }: DestinationCardProps) {
    const config = typeConfig[destination.type];
    const Icon = config.icon;

    const target =
        destination.type === 'email'
            ? destination.config.email
            : destination.config.webhook_url
                  ?.replace(/https?:\/\//, '')
                  .split('/')[0];

    const handleToggle = () => {
        router.patch(
            DestinationController.toggle(destination).url,
            {},
            { preserveScroll: true },
        );
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this destination?')) {
            router.delete(DestinationController.destroy(destination).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <Card className="relative">
            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-2">
                <div className="flex items-center gap-3">
                    <div
                        className={`flex size-10 items-center justify-center rounded-lg ${config.color}`}
                    >
                        <Icon className="size-5" />
                    </div>
                    <div>
                        <CardTitle className="text-base">
                            {destination.name}
                        </CardTitle>
                        <CardDescription className="mt-1 text-xs">
                            {target}
                        </CardDescription>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Badge
                        variant={
                            destination.is_enabled ? 'default' : 'secondary'
                        }
                    >
                        {destination.is_enabled ? 'Active' : 'Disabled'}
                    </Badge>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                            >
                                <MoreHorizontal className="size-4" />
                                <span className="sr-only">Open menu</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link
                                    href={
                                        DestinationController.edit(destination)
                                            .url
                                    }
                                >
                                    <Pencil className="mr-2 size-4" />
                                    Edit
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onClick={handleDelete}
                                className="text-destructive focus:text-destructive"
                            >
                                <Trash2 className="mr-2 size-4" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </CardHeader>
            <CardContent>
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                        {config.label} destination
                    </span>
                    <Switch
                        checked={destination.is_enabled}
                        onCheckedChange={handleToggle}
                        aria-label={`Toggle ${destination.name}`}
                    />
                </div>
            </CardContent>
        </Card>
    );
}
