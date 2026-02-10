import { Head, router } from '@inertiajs/react';

import { DigestForm } from '@/components/digest-form';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type DestinationsByType } from '@/types';

import DigestController from '@/actions/App/Http/Controllers/DigestController';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Digests',
        href: DigestController.index().url,
    },
    {
        title: 'Create',
        href: DigestController.create().url,
    },
];

interface Props {
    destinations: DestinationsByType;
    timezones: string[];
    maxRepos: number;
    aiConfigured: boolean;
}

export default function DigestCreate({
    destinations,
    timezones,
    maxRepos,
    aiConfigured,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Digest" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Create Digest"
                    description="Set up a new release digest for your repositories."
                />

                <Card>
                    <CardContent className="pt-6">
                        <DigestForm
                            destinations={destinations}
                            timezones={timezones}
                            maxRepos={maxRepos}
                            aiConfigured={aiConfigured}
                            onCancel={() =>
                                router.visit(DigestController.index().url)
                            }
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
