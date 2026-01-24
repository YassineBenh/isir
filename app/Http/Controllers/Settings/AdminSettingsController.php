<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AdminSettingsUpdateRequest;
use App\Settings\AdminSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    /**
     * Show the admin settings page.
     */
    public function edit(AdminSettings $settings): Response
    {
        return Inertia::render('settings/admin', [
            'settings' => [
                'registration_enabled' => $settings->registration_enabled,
            ],
        ]);
    }

    /**
     * Update the admin settings.
     */
    public function update(AdminSettingsUpdateRequest $request, AdminSettings $settings): RedirectResponse
    {
        $settings->registration_enabled = $request->boolean('registration_enabled');
        $settings->save();

        return to_route('admin.edit');
    }
}
