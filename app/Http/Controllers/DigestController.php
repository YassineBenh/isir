<?php

namespace App\Http\Controllers;

use App\Actions\CreateDigest;
use App\Actions\DeleteDigest;
use App\Actions\UpdateDigest;
use App\Http\Requests\StoreDigestRequest;
use App\Http\Requests\UpdateDigestRequest;
use App\Models\Digest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DigestController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of digests.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Digest::class);

        $digests = $request->user()
            ->digests()
            ->withCount(['sources', 'destinations'])
            ->with(['destinations:id,type'])
            ->latest()
            ->get();

        return Inertia::render('digests/index', [
            'digests' => $digests,
        ]);
    }

    /**
     * Show the form for creating a new digest.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Digest::class);

        return Inertia::render('digests/create', [
            'destinations' => fn () => $request->user()
                ->destinations()
                ->where('is_enabled', true)
                ->get()
                ->groupBy('type'),
            'timezones' => config('isir.timezones'),
            'maxRepos' => config('isir.limits.github_repos_per_digest'),
        ]);
    }

    /**
     * Store a newly created digest.
     */
    public function store(StoreDigestRequest $request, CreateDigest $action): RedirectResponse
    {
        $this->authorize('create', Digest::class);

        $action($request->user(), $request->validated());

        return to_route('digests.index')
            ->with('success', 'Digest created successfully.');
    }

    /**
     * Show the form for editing the specified digest.
     */
    public function edit(Request $request, Digest $digest): Response
    {
        $this->authorize('update', $digest);

        $digest->load(['sources', 'destinations']);

        return Inertia::render('digests/edit', [
            'digest' => $digest,
            'destinations' => fn () => $request->user()
                ->destinations()
                ->where('is_enabled', true)
                ->get()
                ->groupBy('type'),
            'timezones' => config('isir.timezones'),
            'maxRepos' => config('isir.limits.github_repos_per_digest'),
        ]);
    }

    /**
     * Update the specified digest.
     */
    public function update(UpdateDigestRequest $request, Digest $digest, UpdateDigest $action): RedirectResponse
    {
        $this->authorize('update', $digest);

        $action($digest, $request->validated());

        return to_route('digests.index')
            ->with('success', 'Digest updated successfully.');
    }

    /**
     * Remove the specified digest.
     */
    public function destroy(Digest $digest, DeleteDigest $action): RedirectResponse
    {
        $this->authorize('delete', $digest);

        $action($digest);

        return to_route('digests.index')
            ->with('success', 'Digest deleted successfully.');
    }

    /**
     * Toggle the enabled status of the specified digest.
     */
    public function toggle(Digest $digest): RedirectResponse
    {
        $this->authorize('toggle', $digest);

        $digest->update([
            'is_enabled' => ! $digest->is_enabled,
        ]);

        $status = $digest->is_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Digest {$status} successfully.");
    }
}
