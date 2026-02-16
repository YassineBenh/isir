import { Head, router } from '@inertiajs/react';

import { DestinationForm } from '@/components/destination-form';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Destination } from '@/types';

import DestinationController from '@/actions/App/Http/Controllers/DestinationController';

interface Props {
    destination: Destination;
}

export default function DestinationEdit({ destination }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Destinations',
            href: DestinationController.index().url,
        },
        {
            title: destination.name,
            href: DestinationController.edit(destination).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${destination.name}`} />

            <div className="space-y-6 p-4">
                <Heading
                    title="Edit Destination"
                    description={`Update settings for ${destination.name}.`}
                />

                <Card className="max-w-2xl">
                    <CardContent className="pt-6">
                        <DestinationForm
                            destination={destination}
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
