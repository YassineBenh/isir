import { Head, Link } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';

import DigestController from '@/actions/App/Http/Controllers/DigestController';
import { DigestCard } from '@/components/digest-card';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Digest } from '@/types';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Digests',
        href: DigestController.index().url,
    },
];

interface Props {
    digests: Digest[];
}

export default function DigestsIndex({ digests }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Digests" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Digests"
                        description="Manage your release digest schedules."
                    />
                    <Button asChild>
                        <Link href={DigestController.create().url}>
                            <Plus className="mr-2 size-4" />
                            New Digest
                        </Link>
                    </Button>
                </div>

                {digests.length === 0 ? (
                    <Empty className="border">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FileText />
                            </EmptyMedia>
                            <EmptyTitle>No digests yet</EmptyTitle>
                            <EmptyDescription>
                                Create a digest to automatically receive
                                summaries of releases from your favorite GitHub
                                repositories.
                            </EmptyDescription>
                        </EmptyHeader>
                        <Button asChild>
                            <Link href={DigestController.create().url}>
                                Create Your First Digest
                            </Link>
                        </Button>
                    </Empty>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {digests.map((digest) => (
                            <DigestCard key={digest.id} digest={digest} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
