import { Head, router } from '@inertiajs/react';

import { DestinationForm } from '@/components/destination-form';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

import DestinationController from '@/actions/App/Http/Controllers/DestinationController';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Destinations',
        href: DestinationController.index().url,
    },
    {
        title: 'Create',
        href: DestinationController.create().url,
    },
];

export default function DestinationCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Destination" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Create Destination"
                    description="Add a new destination for your digests."
                />

                <Card>
                    <CardContent className="pt-6">
                        <DestinationForm
                            onCancel={() =>
                                router.visit(DestinationController.index().url)
                            }
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
