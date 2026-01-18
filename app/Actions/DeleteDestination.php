<?php

namespace App\Actions;

use App\Models\Destination;

class DeleteDestination
{
    /**
     * Delete a destination.
     *
     * Detaches from all digests before deleting.
     */
    public function __invoke(Destination $destination): void
    {
        $destination->digests()->detach();

        $destination->delete();
    }
}
