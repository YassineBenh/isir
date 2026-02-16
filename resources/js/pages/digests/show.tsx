import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    CheckCircle2,
    Clock,
    Eye,
    GitBranch,
    Loader2,
    Mail,
    MessageSquare,
    Pencil,
    Slack,
} from 'lucide-react';

import DigestController from '@/actions/App/Http/Controllers/DigestController';
import DigestRunController from '@/actions/App/Http/Controllers/DigestRunController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import {
    type BreadcrumbItem,
    type Destination,
    type DestinationType,
    type Digest,
    type DigestRun,
    type DigestRunStatus,
    type PaginatedData,
} from '@/types';


const DAYS_OF_WEEK = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
];

const destinationIcons: Record<DestinationType, typeof Slack> = {
    slack: Slack,
    discord: MessageSquare,
    email: Mail,
};

const statusConfig: Record<
    DigestRunStatus,
    {
        label: string;
        variant: 'default' | 'secondary' | 'destructive';
        icon: typeof CheckCircle2;
    }
> = {
    pending: { label: 'Pending', variant: 'secondary', icon: Clock },
    running: { label: 'Running', variant: 'default', icon: Loader2 },
    completed: { label: 'Completed', variant: 'default', icon: CheckCircle2 },
    failed: { label: 'Failed', variant: 'destructive', icon: AlertCircle },
};

interface Props {
    digest: Digest;
    runs: PaginatedData<DigestRun>;
}

export default function DigestShow({ digest, runs }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Digests',
            href: DigestController.index().url,
        },
        {
            title: digest.name,
            href: DigestController.show(digest).url,
        },
    ];

    const scheduleText =
        digest.frequency === 'weekly'
            ? `${DAYS_OF_WEEK[digest.send_day_of_week ?? 0]} at ${formatTime(digest.send_time)}`
            : `Daily at ${formatTime(digest.send_time)}`;

    const destinationTypes = new Set(
        digest.destinations?.map((d: Destination) => d.type) ?? [],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={digest.name} />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={DigestController.index().url}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <Heading
                            title={digest.name}
                            description="View digest details and run history."
                        />
                    </div>
                    <Button asChild>
                        <Link href={DigestController.edit(digest).url}>
                            <Pencil className="mr-2 size-4" />
                            Edit
                        </Link>
                    </Button>
                </div>

                {/* Digest Info Card */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Digest Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Status
                                </p>
                                <Badge
                                    variant={
                                        digest.is_enabled
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {digest.is_enabled ? 'Active' : 'Disabled'}
                                </Badge>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Schedule
                                </p>
                                <p className="flex items-center gap-1.5 text-sm font-medium">
                                    <Calendar className="size-3.5" />
                                    {scheduleText}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Repositories
                                </p>
                                <p className="flex items-center gap-1.5 text-sm font-medium">
                                    <GitBranch className="size-3.5" />
                                    {digest.sources?.length ?? 0} repos
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Destinations
                                </p>
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
                                        {destinationTypes.size === 0 && (
                                            <span className="text-sm text-muted-foreground">
                                                None
                                            </span>
                                        )}
                                    </div>
                                </TooltipProvider>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Runs History */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Run History</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {runs.data.length === 0 ? (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Clock />
                                    </EmptyMedia>
                                    <EmptyTitle>No runs yet</EmptyTitle>
                                    <EmptyDescription>
                                        This digest hasn't run yet. Runs will
                                        appear here once the digest is executed.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Period</TableHead>
                                        <TableHead>Finished</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {runs.data.map((run) => {
                                        const status = statusConfig[run.status];
                                        const StatusIcon = status.icon;
                                        return (
                                            <TableRow key={run.id}>
                                                <TableCell>
                                                    <Badge
                                                        variant={status.variant}
                                                        className="gap-1"
                                                    >
                                                        <StatusIcon
                                                            className={`size-3 ${run.status === 'running' ? 'animate-spin' : ''}`}
                                                        />
                                                        {status.label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {formatDate(
                                                        run.period_start_at,
                                                    )}{' '}
                                                    -{' '}
                                                    {formatDate(
                                                        run.period_end_at,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {run.finished_at
                                                        ? formatDateTime(
                                                              run.finished_at,
                                                          )
                                                        : '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={
                                                                DigestRunController.show(
                                                                    {
                                                                        digest,
                                                                        run,
                                                                    },
                                                                ).url
                                                            }
                                                        >
                                                            <Eye className="mr-1.5 size-3.5" />
                                                            View
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        )}

                        {/* Pagination */}
                        {runs.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Showing {runs.from} to {runs.to} of{' '}
                                    {runs.total} runs
                                </p>
                                <div className="flex gap-2">
                                    {runs.links.map((link, index) => {
                                        if (
                                            link.label.includes('Previous') ||
                                            link.label.includes('Next')
                                        ) {
                                            return (
                                                <Button
                                                    key={index}
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={!link.url}
                                                    asChild={!!link.url}
                                                >
                                                    {link.url ? (
                                                        <Link href={link.url}>
                                                            {link.label.includes(
                                                                'Previous',
                                                            )
                                                                ? 'Previous'
                                                                : 'Next'}
                                                        </Link>
                                                    ) : (
                                                        <span>
                                                            {link.label.includes(
                                                                'Previous',
                                                            )
                                                                ? 'Previous'
                                                                : 'Next'}
                                                        </span>
                                                    )}
                                                </Button>
                                            );
                                        }
                                        return null;
                                    })}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function formatTime(time: string): string {
    const [hours] = time.split(':');
    const hour = parseInt(hours, 10);
    const ampm = hour < 12 ? 'AM' : 'PM';
    const hour12 = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${hour12}:00 ${ampm}`;
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}
