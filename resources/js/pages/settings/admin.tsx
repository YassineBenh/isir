import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import AdminSettingsController from '@/actions/App/Http/Controllers/Settings/AdminSettingsController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin settings',
        href: edit().url,
    },
];

interface AdminSettingsProps {
    settings: {
        registration_enabled: boolean;
    };
}

export default function Admin({ settings }: AdminSettingsProps) {
    const { data, setData, patch, processing, recentlySuccessful } = useForm({
        registration_enabled: settings.registration_enabled,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        patch(AdminSettingsController.update().url, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin settings" />

            <h1 className="sr-only">Admin Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Admin settings"
                        description="Manage application-wide settings"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div className="space-y-0.5">
                                <Label htmlFor="registration_enabled">
                                    Allow user registration
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    When disabled, new users cannot create
                                    accounts
                                </p>
                            </div>
                            <Switch
                                id="registration_enabled"
                                checked={data.registration_enabled}
                                onCheckedChange={(checked) =>
                                    setData('registration_enabled', checked)
                                }
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button
                                disabled={processing}
                                data-test="update-admin-settings-button"
                            >
                                Save
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">
                                    Saved
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
