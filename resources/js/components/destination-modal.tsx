import { router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

import { DestinationForm } from '@/components/destination-form';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { type DestinationType } from '@/types';

interface DestinationModalProps {
    triggerLabel?: string;
    defaultType?: DestinationType;
}

export function DestinationModal({
    triggerLabel = 'Add New',
    defaultType,
}: DestinationModalProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="ghost" size="sm">
                    <Plus className="mr-1 size-4" />
                    {triggerLabel}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Destination</DialogTitle>
                    <DialogDescription>
                        Create a new destination to deliver your digests.
                    </DialogDescription>
                </DialogHeader>

                <DestinationForm
                    defaultType={defaultType}
                    onCancel={() => setOpen(false)}
                    onSuccess={() => {
                        setOpen(false);
                        router.reload({ only: ['destinations'] });
                    }}
                />
            </DialogContent>
        </Dialog>
    );
}
