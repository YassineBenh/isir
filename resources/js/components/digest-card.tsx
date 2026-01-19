import { Link, router } from '@inertiajs/react';
import {
    Brain,
    Calendar,
    GitBranch,
    Mail,
    MessageSquare,
    MoreHorizontal,
    Pencil,
    Slack,
    Trash2,
} from 'lucide-react';

import DigestController from '@/actions/App/Http/Controllers/DigestController';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { type Destination, type DestinationType, type Digest } from '@/types';

const DAYS_OF_WEEK = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const destinationIcons: Record<DestinationType, typeof Slack> = {
    slack: Slack,
    discord: MessageSquare,
    email: Mail,
};

interface DigestCardProps {
    digest: Digest;
}

export function DigestCard({ digest }: DigestCardProps) {
    const scheduleText =
        digest.frequency === 'weekly'
            ? `${DAYS_OF_WEEK[digest.send_day_of_week ?? 0]} at ${formatTime(digest.send_time)}`
            : `Daily at ${formatTime(digest.send_time)}`;

    const handleToggle = () => {
        router.patch(
            DigestController.toggle(digest).url,
            {},
            { preserveScroll: true },
        );
    };

    const handleDelete = () => {
        if (
            confirm(
                'Are you sure you want to delete this digest? This action cannot be undone.',
            )
        ) {
            router.delete(DigestController.destroy(digest).url, {
                preserveScroll: true,
            });
        }
    };

    // Get unique destination types from the digest
    const destinationTypes = new Set(
        digest.destinations?.map((d: Destination) => d.type) ?? [],
    );

    return (
        <Card className="relative">
            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-2">
                <div className="space-y-1">
                    <CardTitle className="flex items-center gap-1.5 text-base">
                        {digest.name}
                        {digest.ai_enabled && (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Brain className="size-4 text-muted-foreground" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>AI summarized</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </CardTitle>
                    <CardDescription className="flex items-center gap-1.5 text-xs">
                        <Calendar className="size-3" />
                        {scheduleText}
                    </CardDescription>
                </div>
                <div className="flex items-center gap-2">
                    <Badge
                        variant={digest.is_enabled ? 'default' : 'secondary'}
                    >
                        {digest.is_enabled ? 'Active' : 'Disabled'}
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
                                <Link href={DigestController.edit(digest).url}>
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
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <GitBranch className="size-4" />
                            <span>{digest.sources_count ?? 0} repos</span>
                        </div>

                        {destinationTypes.size > 0 && (
                            <TooltipProvider>
                                <div className="flex items-center gap-1">
                                    {Array.from(destinationTypes).map(
                                        (type) => {
                                            const Icon =
                                                destinationIcons[
                                                    type as DestinationType
                                                ];
                                            return (
                                                <Tooltip key={type}>
                                                    <TooltipTrigger asChild>
                                                        <div className="flex size-6 items-center justify-center rounded bg-muted">
                                                            <Icon className="size-3.5 text-muted-foreground" />
                                                        </div>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p className="capitalize">
                                                            {type}
                                                        </p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            );
                                        },
                                    )}
                                </div>
                            </TooltipProvider>
                        )}
                    </div>

                    <Switch
                        checked={digest.is_enabled}
                        onCheckedChange={handleToggle}
                        aria-label={`Toggle ${digest.name}`}
                    />
                </div>
            </CardContent>
        </Card>
    );
}

function formatTime(time: string): string {
    const [hours] = time.split(':');
    const hour = parseInt(hours, 10);
    const ampm = hour < 12 ? 'AM' : 'PM';
    const hour12 = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${hour12}:00 ${ampm}`;
}
