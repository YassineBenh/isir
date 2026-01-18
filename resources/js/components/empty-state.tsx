import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

import { Button } from '@/components/ui/button';

interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: {
        label: string;
        href: string;
    };
    children?: ReactNode;
}

export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    children,
}: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-8 text-center">
            <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                <Icon className="size-6 text-muted-foreground" />
            </div>
            <h3 className="mt-4 text-lg font-semibold">{title}</h3>
            <p className="mt-2 max-w-sm text-sm text-muted-foreground">
                {description}
            </p>
            {action && (
                <Button asChild className="mt-6">
                    <a href={action.href}>{action.label}</a>
                </Button>
            )}
            {children && <div className="mt-6">{children}</div>}
        </div>
    );
}
