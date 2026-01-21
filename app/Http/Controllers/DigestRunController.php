<?php

namespace App\Http\Controllers;

use App\Models\Digest;
use App\Models\DigestRun;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class DigestRunController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the specified digest run.
     */
    public function show(Digest $digest, DigestRun $run): Response
    {
        $this->authorize('view', $run);

        $run->load([
            'deliveryAttempts.destination',
            'sourceItems.source',
            'sourceItems.summaries' => fn ($query) => $query->where('digest_id', $digest->id),
        ]);

        return Inertia::render('digests/runs/show', [
            'digest' => $digest,
            'run' => $run,
        ]);
    }
}
