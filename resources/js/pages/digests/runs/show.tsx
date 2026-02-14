import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    CheckCircle2,
    ChevronDown,
    Clock,
    ExternalLink,
    FileText,
    Loader2,
    Mail,
    MessageSquare,
    Slack,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import Markdown from 'react-markdown';

import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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
    type Source,
    type SourceItem,
} from '@/types';

import DigestController from '@/actions/App/Http/Controllers/DigestController';

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
    const hasAiSummary = Boolean(run.ai_summary?.trim());

    // Group source items by their source
    const itemsBySource = groupItemsBySource(run.source_items ?? []);

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

                {/* Content */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Content</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {digest.ai_enabled ? (
                            <Tabs
                                defaultValue={
                                    hasAiSummary ? 'rendered' : 'original'
                                }
                            >
                                <TabsList>
                                    <TabsTrigger value="rendered">
                                        <Sparkles className="size-4" />
                                        AI Summary
                                    </TabsTrigger>
                                    <TabsTrigger value="original">
                                        <FileText className="size-4" />
                                        Original
                                    </TabsTrigger>
                                </TabsList>

                                <TabsContent value="rendered" className="mt-4">
                                    {hasAiSummary ? (
                                        <div className="prose prose-sm dark:prose-invert max-w-none">
                                            <Markdown>
                                                {run.ai_summary}
                                            </Markdown>
                                        </div>
                                    ) : (
                                        <Empty>
                                            <EmptyHeader>
                                                <EmptyMedia variant="icon">
                                                    <Sparkles />
                                                </EmptyMedia>
                                                <EmptyTitle>
                                                    No AI summary
                                                </EmptyTitle>
                                                <EmptyDescription>
                                                    AI-generated summary will
                                                    appear here once the digest
                                                    is processed.
                                                </EmptyDescription>
                                            </EmptyHeader>
                                        </Empty>
                                    )}
                                </TabsContent>

                                <TabsContent value="original" className="mt-4">
                                    <OriginalContent
                                        itemsBySource={itemsBySource}
                                        digestId={digest.id}
                                    />
                                </TabsContent>
                            </Tabs>
                        ) : (
                            <OriginalContent
                                itemsBySource={itemsBySource}
                                digestId={digest.id}
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

interface OriginalContentProps {
    itemsBySource: { source: Source; items: SourceItem[] }[];
    digestId: number;
}

function OriginalContent({ itemsBySource, digestId }: OriginalContentProps) {
    if (itemsBySource.length === 0) {
        return (
            <Empty>
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <FileText />
                    </EmptyMedia>
                    <EmptyTitle>No source items</EmptyTitle>
                    <EmptyDescription>
                        No items were collected for this digest run.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div className="space-y-4">
            {itemsBySource.map(({ source, items }) => (
                <SourceCard
                    key={source.id}
                    source={source}
                    items={items}
                    digestId={digestId}
                />
            ))}
        </div>
    );
}

interface SourceCardProps {
    source: Source;
    items: SourceItem[];
    digestId: number;
}

function SourceCard({ source, items, digestId }: SourceCardProps) {
    return (
        <div className="rounded-lg border bg-card">
            <div className="flex items-center justify-between border-b px-4 py-3">
                <div className="flex items-center gap-3">
                    <div className="flex size-8 items-center justify-center rounded-md bg-muted">
                        <FileText className="size-4 text-muted-foreground" />
                    </div>
                    <div>
                        <h3 className="text-sm font-medium">{source.name}</h3>
                        <p className="text-xs text-muted-foreground">
                            {items.length} item{items.length !== 1 ? 's' : ''}
                        </p>
                    </div>
                </div>
                {source.url && (
                    <a
                        href={source.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ExternalLink className="size-4" />
                    </a>
                )}
            </div>
            <div className="divide-y">
                {items.map((item) => (
                    <SourceItemRow
                        key={item.id}
                        item={item}
                        digestId={digestId}
                    />
                ))}
            </div>
        </div>
    );
}

interface SourceItemRowProps {
    item: SourceItem;
    digestId: number;
}

function SourceItemRow({ item, digestId }: SourceItemRowProps) {
    const [isOpen, setIsOpen] = useState(true);

    // Find the summary for this digest
    const summary = item.summaries?.find((s) => s.digest_id === digestId);

    return (
        <Collapsible open={isOpen} onOpenChange={setIsOpen}>
            <div className="px-4 py-3">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            {item.url ? (
                                <a
                                    href={item.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm font-medium hover:underline"
                                >
                                    {item.title}
                                </a>
                            ) : (
                                <span className="text-sm font-medium">
                                    {item.title}
                                </span>
                            )}
                            {item.url && (
                                <ExternalLink className="size-3 shrink-0 text-muted-foreground" />
                            )}
                        </div>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {formatDateTime(item.published_at)}
                        </p>

                        {/* AI Summary */}
                        {summary?.summary_markdown && (
                            <div className="mt-2 rounded-md bg-muted/50 p-2">
                                <div className="mb-1 flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                    <Sparkles className="size-3" />
                                    AI Summary
                                </div>
                                <div className="prose prose-sm dark:prose-invert prose-p:my-0 max-w-none text-sm">
                                    <Markdown>
                                        {summary.summary_markdown}
                                    </Markdown>
                                </div>
                            </div>
                        )}
                    </div>

                    {item.raw_content && (
                        <CollapsibleTrigger asChild>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="shrink-0"
                            >
                                <ChevronDown
                                    className={`size-4 transition-transform ${isOpen ? 'rotate-180' : ''}`}
                                />
                                <span className="sr-only">
                                    {isOpen ? 'Hide' : 'Show'} original content
                                </span>
                            </Button>
                        </CollapsibleTrigger>
                    )}
                </div>

                <CollapsibleContent>
                    {item.raw_content && (
                        <div className="mt-3 rounded-md border bg-muted/30 p-3">
                            <p className="mb-2 text-xs font-medium text-muted-foreground">
                                Original Content
                            </p>
                            <div className="prose prose-sm dark:prose-invert max-w-none">
                                <Markdown>{item.raw_content}</Markdown>
                            </div>
                        </div>
                    )}
                </CollapsibleContent>
            </div>
        </Collapsible>
    );
}

function groupItemsBySource(
    items: SourceItem[],
): { source: Source; items: SourceItem[] }[] {
    const sourceMap = new Map<
        number,
        { source: Source; items: SourceItem[] }
    >();

    for (const item of items) {
        if (!item.source) continue;

        const existing = sourceMap.get(item.source.id);
        if (existing) {
            existing.items.push(item);
        } else {
            sourceMap.set(item.source.id, {
                source: item.source,
                items: [item],
            });
        }
    }

    return Array.from(sourceMap.values());
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
