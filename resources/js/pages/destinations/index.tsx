import { Head, Link } from '@inertiajs/react';
import { Mail, Plus } from 'lucide-react';

import DestinationController from '@/actions/App/Http/Controllers/DestinationController';
import { DestinationCard } from '@/components/destination-card';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Destination } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Destinations',
        href: DestinationController.index().url,
    },
];

interface Props {
    destinations: Destination[];
}

export default function DestinationsIndex({ destinations }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Destinations" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Destinations"
                        description="Manage where your digests are delivered."
                    />
                    <Button asChild>
                        <Link href={DestinationController.create().url}>
                            <Plus className="mr-2 size-4" />
                            Add Destination
                        </Link>
                    </Button>
                </div>

                {destinations.length === 0 ? (
                    <EmptyState
                        icon={Mail}
                        title="No destinations yet"
                        description="Add a destination to receive your digests. You can send to Slack, Discord, or email."
                        action={{
                            label: 'Add Your First Destination',
                            href: DestinationController.create().url,
                        }}
                    />
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {destinations.map((destination) => (
                            <DestinationCard
                                key={destination.id}
                                destination={destination}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
