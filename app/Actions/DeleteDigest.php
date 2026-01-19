<?php

namespace App\Actions;

use App\Models\Digest;

class DeleteDigest
{
    /**
     * Delete a digest.
     *
     * Detaches sources and destinations before deleting.
     */
    public function __invoke(Digest $digest): void
    {
        $digest->sources()->detach();
        $digest->destinations()->detach();

        $digest->delete();
    }
}
