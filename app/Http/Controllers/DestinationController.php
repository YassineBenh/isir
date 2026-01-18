<?php

namespace App\Http\Controllers;

use App\Actions\CreateDestination;
use App\Actions\DeleteDestination;
use App\Actions\UpdateDestination;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Models\Destination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    /**
     * Display a listing of destinations.
     */
    public function index(Request $request): Response
    {
        $destinations = $request->user()
            ->destinations()
            ->latest()
            ->get();

        return Inertia::render('destinations/index', [
            'destinations' => $destinations,
        ]);
    }

    /**
     * Show the form for creating a new destination.
     */
    public function create(): Response
    {
        return Inertia::render('destinations/create');
    }

    /**
     * Store a newly created destination.
     */
    public function store(StoreDestinationRequest $request, CreateDestination $action): RedirectResponse
    {
        $action($request->user(), $request->validated());

        return to_route('destinations.index')
            ->with('success', 'Destination created successfully.');
    }

    /**
     * Show the form for editing the specified destination.
     */
    public function edit(Request $request, Destination $destination): Response
    {
        $this->authorize($request, $destination);

        return Inertia::render('destinations/edit', [
            'destination' => $destination,
        ]);
    }

    /**
     * Update the specified destination.
     */
    public function update(UpdateDestinationRequest $request, Destination $destination, UpdateDestination $action): RedirectResponse
    {
        $action($destination, $request->validated());

        return to_route('destinations.index')
            ->with('success', 'Destination updated successfully.');
    }

    /**
     * Remove the specified destination.
     */
    public function destroy(Request $request, Destination $destination, DeleteDestination $action): RedirectResponse
    {
        $this->authorize($request, $destination);

        $action($destination);

        return to_route('destinations.index')
            ->with('success', 'Destination deleted successfully.');
    }

    /**
     * Toggle the enabled status of the specified destination.
     */
    public function toggle(Request $request, Destination $destination): RedirectResponse
    {
        $this->authorize($request, $destination);

        $destination->update([
            'is_enabled' => ! $destination->is_enabled,
        ]);

        $status = $destination->is_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Destination {$status} successfully.");
    }

    /**
     * Authorize that the user owns the destination.
     */
    private function authorize(Request $request, Destination $destination): void
    {
        abort_unless($destination->user_id === $request->user()->id, 403);
    }
}
