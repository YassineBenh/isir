import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    AlertCircle,
    ArrowRight,
    Calendar,
    CheckCircle2,
    Clock,
    FileText,
    GitBranch,
    Loader2,
    Plus,
    Send,
    Zap,
} from 'lucide-react';

import DigestController from '@/actions/App/Http/Controllers/DigestController';
import DigestRunController from '@/actions/App/Http/Controllers/DigestRunController';
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
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type DigestRunStatus } from '@/types';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardStats {
    activeDigests: number;
    runsLast7Days: number;
    deliverySuccessRate: number | null;
}

interface RecentRun {
    id: number;
    digest_id: number;
    status: DigestRunStatus;
    finished_at: string | null;
    created_at: string;
    digest: {
        id: number;
        name: string;
    };
}

interface UpcomingDigest {
    id: number;
    name: string;
    frequency: 'daily' | 'weekly';
    send_time: string;
    send_day_of_week: number | null;
    timezone: string;
    last_successful_run_at: string | null;
    sources_count: number;
}

interface Props {
    stats: DashboardStats;
    recentRuns: RecentRun[];
    upcomingDigests: UpcomingDigest[];
}

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

const DAYS_OF_WEEK = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export default function Dashboard({
    stats,
    recentRuns,
    upcomingDigests,
}: Props) {
    const hasDigests = stats.activeDigests > 0 || upcomingDigests.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard
                        title="Active Digests"
                        value={stats.activeDigests}
                        description="Currently enabled"
                        icon={<Zap className="size-4 text-muted-foreground" />}
                    />
                    <StatCard
                        title="Runs (7 Days)"
                        value={stats.runsLast7Days}
                        description="Digest executions"
                        icon={
                            <Activity className="size-4 text-muted-foreground" />
                        }
                    />
                    <StatCard
                        title="Delivery Rate"
                        value={
                            stats.deliverySuccessRate !== null
                                ? `${stats.deliverySuccessRate}%`
                                : '-'
                        }
                        description="Last 7 days"
                        icon={<Send className="size-4 text-muted-foreground" />}
                    />
                </div>

                {/* Main Content */}
                {!hasDigests ? (
                    <GettingStartedCard />
                ) : (
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Recent Runs */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <div>
                                    <CardTitle className="text-base">
                                        Recent Runs
                                    </CardTitle>
                                    <CardDescription>
                                        Latest digest executions
                                    </CardDescription>
                                </div>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={DigestController.index().url}>
                                        View All
                                        <ArrowRight className="ml-1.5 size-3.5" />
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {recentRuns.length === 0 ? (
                                    <Empty>
                                        <EmptyHeader>
                                            <EmptyMedia variant="icon">
                                                <Clock />
                                            </EmptyMedia>
                                            <EmptyTitle>No runs yet</EmptyTitle>
                                            <EmptyDescription>
                                                Your digests haven't run yet.
                                                They'll appear here once
                                                executed.
                                            </EmptyDescription>
                                        </EmptyHeader>
                                    </Empty>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Digest</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead className="text-right">
                                                    Time
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {recentRuns.map((run) => {
                                                const status =
                                                    statusConfig[run.status];
                                                const StatusIcon = status.icon;
                                                return (
                                                    <TableRow key={run.id}>
                                                        <TableCell>
                                                            <Link
                                                                href={
                                                                    DigestRunController.show(
                                                                        {
                                                                            digest: run.digest,
                                                                            run,
                                                                        },
                                                                    ).url
                                                                }
                                                                className="font-medium hover:underline"
                                                            >
                                                                {
                                                                    run.digest
                                                                        .name
                                                                }
                                                            </Link>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={
                                                                    status.variant
                                                                }
                                                                className="gap-1"
                                                            >
                                                                <StatusIcon
                                                                    className={`size-3 ${run.status === 'running' ? 'animate-spin' : ''}`}
                                                                />
                                                                {status.label}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="text-right text-muted-foreground">
                                                            {formatRelativeTime(
                                                                run.finished_at ??
                                                                    run.created_at,
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>

                        {/* Upcoming Digests */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <div>
                                    <CardTitle className="text-base">
                                        Upcoming Digests
                                    </CardTitle>
                                    <CardDescription>
                                        Scheduled to run next
                                    </CardDescription>
                                </div>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={DigestController.create().url}>
                                        <Plus className="mr-1.5 size-3.5" />
                                        New
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {upcomingDigests.length === 0 ? (
                                    <Empty>
                                        <EmptyHeader>
                                            <EmptyMedia variant="icon">
                                                <Calendar />
                                            </EmptyMedia>
                                            <EmptyTitle>
                                                No active digests
                                            </EmptyTitle>
                                            <EmptyDescription>
                                                Enable a digest to see upcoming
                                                schedules.
                                            </EmptyDescription>
                                        </EmptyHeader>
                                    </Empty>
                                ) : (
                                    <div className="space-y-3">
                                        {upcomingDigests.map((digest) => (
                                            <Link
                                                key={digest.id}
                                                href={
                                                    DigestController.show(
                                                        digest,
                                                    ).url
                                                }
                                                className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                            >
                                                <div className="space-y-1">
                                                    <p className="leading-none font-medium">
                                                        {digest.name}
                                                    </p>
                                                    <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                        <GitBranch className="size-3" />
                                                        {digest.sources_count}{' '}
                                                        repos
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                                        <Calendar className="size-3.5" />
                                                        {formatSchedule(digest)}
                                                    </p>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function StatCard({
    title,
    value,
    description,
    icon,
}: {
    title: string;
    value: string | number;
    description: string;
    icon: React.ReactNode;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                {icon}
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                <p className="text-xs text-muted-foreground">{description}</p>
            </CardContent>
        </Card>
    );
}

function GettingStartedCard() {
    return (
        <Card className="flex flex-1 flex-col items-center justify-center">
            <Empty className="py-12">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <FileText />
                    </EmptyMedia>
                    <EmptyTitle>Welcome to your Dashboard</EmptyTitle>
                    <EmptyDescription>
                        Create your first digest to start receiving automated
                        release summaries from your favorite GitHub
                        repositories.
                    </EmptyDescription>
                </EmptyHeader>
                <Button asChild>
                    <Link href={DigestController.create().url}>
                        <Plus className="mr-2 size-4" />
                        Create Your First Digest
                    </Link>
                </Button>
            </Empty>
        </Card>
    );
}

function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) {
        return 'Just now';
    }
    if (diffMins < 60) {
        return `${diffMins}m ago`;
    }
    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }
    if (diffDays < 7) {
        return `${diffDays}d ago`;
    }
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatSchedule(digest: UpcomingDigest): string {
    const time = formatTime(digest.send_time);
    if (digest.frequency === 'weekly') {
        return `${DAYS_OF_WEEK[digest.send_day_of_week ?? 0]} ${time}`;
    }
    return `Daily ${time}`;
}

function formatTime(time: string): string {
    const [hours] = time.split(':');
    const hour = parseInt(hours, 10);
    const ampm = hour < 12 ? 'AM' : 'PM';
    const hour12 = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${hour12}:00 ${ampm}`;
}
