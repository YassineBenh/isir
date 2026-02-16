import { Head, router } from '@inertiajs/react';

import { DigestForm } from '@/components/digest-form';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import {
    type BreadcrumbItem,
    type DestinationsByType,
    type Digest,
} from '@/types';

import DigestController from '@/actions/App/Http/Controllers/DigestController';

interface Props {
    digest: Digest;
    destinations: DestinationsByType;
    timezones: string[];
    maxRepos: number;
    aiConfigured: boolean;
}

export default function DigestEdit({
    digest,
    destinations,
    timezones,
    maxRepos,
    aiConfigured,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Digests',
            href: DigestController.index().url,
        },
        {
            title: digest.name,
            href: DigestController.edit(digest).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${digest.name}`} />

            <div className="space-y-6 p-4">
                <Heading
                    title="Edit Digest"
                    description={`Update settings for ${digest.name}.`}
                />

                <Card>
                    <CardContent className="pt-6">
                        <DigestForm
                            digest={digest}
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
