import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface AppConfig {
    isSelfHosted: boolean;
    mailerConfigured: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    app: AppConfig;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export type DestinationType = 'slack' | 'discord' | 'email';

export interface DestinationConfig {
    webhook_url?: string;
    email?: string;
}

export interface Destination {
    id: number;
    user_id: number;
    type: DestinationType;
    name: string;
    config: DestinationConfig;
    is_enabled: boolean;
    created_at: string;
    updated_at: string;
}

export type DigestFrequency = 'daily' | 'weekly';

export interface Source {
    id: number;
    type: string;
    canonical_key: string;
    name: string;
    url: string | null;
    config: Record<string, unknown>;
    is_enabled: boolean;
    created_at: string;
    updated_at: string;
}

export interface Digest {
    id: number;
    user_id: number;
    name: string;
    frequency: DigestFrequency;
    timezone: string;
    send_time: string;
    send_day_of_week: number | null;
    is_enabled: boolean;
    ai_enabled: boolean;
    ai_prefs: Record<string, unknown> | null;
    last_successful_run_at: string | null;
    created_at: string;
    updated_at: string;
    sources?: Source[];
    destinations?: Destination[];
    sources_count?: number;
    destinations_count?: number;
}

export interface DestinationsByType {
    slack?: Destination[];
    discord?: Destination[];
    email?: Destination[];
}

export type DigestRunStatus = 'pending' | 'running' | 'completed' | 'failed';

export type DeliveryAttemptStatus = 'pending' | 'sent' | 'failed';

export type DigestItemSummaryStatus =
    | 'pending'
    | 'processing'
    | 'completed'
    | 'failed';

export interface DigestItemSummary {
    id: number;
    digest_id: number;
    source_item_id: number;
    summary_markdown: string | null;
    summary_json: Record<string, unknown> | null;
    provider: string | null;
    model: string | null;
    status: DigestItemSummaryStatus;
    error: string | null;
    created_at: string;
    updated_at: string;
}

export interface SourceItem {
    id: number;
    source_id: number;
    external_id: string;
    title: string;
    url: string | null;
    published_at: string;
    raw_content: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
    source?: Source;
    summaries?: DigestItemSummary[];
    pivot?: {
        position: number;
    };
}

export interface DeliveryAttempt {
    id: number;
    digest_run_id: number;
    destination_id: number;
    status: DeliveryAttemptStatus;
    sent_at: string | null;
    provider_message_id: string | null;
    error: string | null;
    created_at: string;
    updated_at: string;
    destination?: Destination;
}

export interface DigestRun {
    id: number;
    digest_id: number;
    period_start_at: string;
    period_end_at: string;
    status: DigestRunStatus;
    rendered_content: string | null;
    started_at: string | null;
    finished_at: string | null;
    error: string | null;
    created_at: string;
    updated_at: string;
    digest?: Digest;
    source_items?: SourceItem[];
    delivery_attempts?: DeliveryAttempt[];
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}
