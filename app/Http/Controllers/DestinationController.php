<?php

namespace App\Http\Controllers;

use App\Actions\CreateDestination;
use App\Actions\DeleteDestination;
use App\Actions\UpdateDestination;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Models\Destination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of destinations.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Destination::class);

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
        $this->authorize('create', Destination::class);

        return Inertia::render('destinations/create');
    }

    /**
     * Store a newly created destination.
     */
    public function store(StoreDestinationRequest $request, CreateDestination $action): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Destination::class);

        $destination = $action($request->user(), $request->validated());

        if ($request->boolean('no_redirect')) {
            return response()->json([
                'destination' => $destination,
            ]);
        }

        return to_route('destinations.index')
            ->with('success', 'Destination created successfully.');
    }

    /**
     * Show the form for editing the specified destination.
     */
    public function edit(Destination $destination): Response
    {
        $this->authorize('update', $destination);

        return Inertia::render('destinations/edit', [
            'destination' => $destination,
        ]);
    }

    /**
     * Update the specified destination.
     */
    public function update(UpdateDestinationRequest $request, Destination $destination, UpdateDestination $action): RedirectResponse
    {
        $this->authorize('update', $destination);

        $action($destination, $request->validated());

        return to_route('destinations.index')
            ->with('success', 'Destination updated successfully.');
    }

    /**
     * Remove the specified destination.
     */
    public function destroy(Destination $destination, DeleteDestination $action): RedirectResponse
    {
        $this->authorize('delete', $destination);

        $action($destination);

        return to_route('destinations.index')
            ->with('success', 'Destination deleted successfully.');
    }

    /**
     * Toggle the enabled status of the specified destination.
     */
    public function toggle(Destination $destination): RedirectResponse
    {
        $this->authorize('toggle', $destination);

        $destination->update([
            'is_enabled' => ! $destination->is_enabled,
        ]);

        $status = $destination->is_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Destination {$status} successfully.");
    }
}
