import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    CheckCircle2,
    Clock,
    Loader2,
    Mail,
    MessageSquare,
    Slack,
} from 'lucide-react';
import Markdown from 'react-markdown';

import DigestController from '@/actions/App/Http/Controllers/DigestController';
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
    type DeliveryAttemptStatus,
    type DestinationType,
    type Digest,
    type DigestRun,
    type DigestRunStatus,
} from '@/types';

const destinationIcons: Record<DestinationType, typeof Slack> = {
    slack: Slack,
    discord: MessageSquare,
    email: Mail,
};

const runStatusConfig: Record<
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

const deliveryStatusConfig: Record<
    DeliveryAttemptStatus,
    { label: string; variant: 'default' | 'secondary' | 'destructive' }
> = {
    pending: { label: 'Pending', variant: 'secondary' },
    sent: { label: 'Sent', variant: 'default' },
    failed: { label: 'Failed', variant: 'destructive' },
};

interface Props {
    digest: Digest;
    run: DigestRun;
}

export default function DigestRunShow({ digest, run }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Digests',
            href: DigestController.index().url,
        },
        {
            title: digest.name,
            href: DigestController.show(digest).url,
        },
        {
            title: `Run #${run.id}`,
            href: '#',
        },
    ];

    const status = runStatusConfig[run.status];
    const StatusIcon = status.icon;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${digest.name} - Run #${run.id}`} />

            <div className="space-y-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={DigestController.show(digest).url}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <Heading
                        title={`Run #${run.id}`}
                        description={`${digest.name} digest run details.`}
                    />
                </div>

                {/* Run Info Card */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Run Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Status
                                </p>
                                <Badge
                                    variant={status.variant}
                                    className="gap-1"
                                >
                                    <StatusIcon
                                        className={`size-3 ${run.status === 'running' ? 'animate-spin' : ''}`}
                                    />
                                    {status.label}
                                </Badge>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Period
                                </p>
                                <p className="flex items-center gap-1.5 text-sm font-medium">
                                    <Calendar className="size-3.5" />
                                    {formatDate(run.period_start_at)} -{' '}
                                    {formatDate(run.period_end_at)}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Started
                                </p>
                                <p className="text-sm font-medium">
                                    {run.started_at
                                        ? formatDateTime(run.started_at)
                                        : '-'}
                                </p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Finished
                                </p>
                                <p className="text-sm font-medium">
                                    {run.finished_at
                                        ? formatDateTime(run.finished_at)
                                        : '-'}
                                </p>
                            </div>
                        </div>

                        {run.error && (
                            <div className="mt-4 rounded-md bg-destructive/10 p-3">
                                <p className="text-sm font-medium text-destructive">
                                    Error
                                </p>
                                <p className="mt-1 text-sm text-destructive/80">
                                    {run.error}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Delivery Attempts */}
                {run.delivery_attempts && run.delivery_attempts.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Delivery Attempts
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Destination</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Sent At</TableHead>
                                        <TableHead>Error</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {run.delivery_attempts.map((attempt) => {
                                        const deliveryStatus =
                                            deliveryStatusConfig[
                                                attempt.status
                                            ];
                                        const Icon = attempt.destination
                                            ? destinationIcons[
                                                  attempt.destination
                                                      .type as DestinationType
                                              ]
                                            : null;
                                        return (
                                            <TableRow key={attempt.id}>
                                                <TableCell>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <div className="flex items-center gap-2">
                                                                    {Icon && (
                                                                        <Icon className="size-4 text-muted-foreground" />
                                                                    )}
                                                                    <span>
                                                                        {attempt
                                                                            .destination
                                                                            ?.name ??
                                                                            'Unknown'}
                                                                    </span>
                                                                </div>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p className="capitalize">
                                                                    {
                                                                        attempt
                                                                            .destination
                                                                            ?.type
                                                                    }
                                                                </p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={
                                                            deliveryStatus.variant
                                                        }
                                                    >
                                                        {deliveryStatus.label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {attempt.sent_at
                                                        ? formatDateTime(
                                                              attempt.sent_at,
                                                          )
                                                        : '-'}
                                                </TableCell>
                                                <TableCell className="max-w-xs truncate text-muted-foreground">
                                                    {attempt.error ?? '-'}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Rendered Content */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Content</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {run.rendered_content ? (
                            <div className="prose prose-sm dark:prose-invert max-w-none">
                                <Markdown>{run.rendered_content}</Markdown>
                            </div>
                        ) : (
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <AlertCircle />
                                    </EmptyMedia>
                                    <EmptyTitle>No content</EmptyTitle>
                                    <EmptyDescription>
                                        This run has no rendered content yet.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
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
