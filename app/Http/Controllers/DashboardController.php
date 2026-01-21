<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAttempt;
use App\Models\Digest;
use App\Models\DigestRun;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        // Get user's digest IDs for filtering
        $digestIds = $user->digests()->pluck('id');

        // Stats
        $activeDigestsCount = $user->digests()->where('is_enabled', true)->count();

        $runsLast7Days = DigestRun::query()
            ->whereIn('digest_id', $digestIds)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        // Delivery success rate (last 7 days)
        $deliveryStats = DeliveryAttempt::query()
            ->whereHas('digestRun', fn ($q) => $q->whereIn('digest_id', $digestIds))
            ->where('created_at', '>=', $sevenDaysAgo)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent")
            )
            ->first();

        $deliverySuccessRate = $deliveryStats->total > 0
            ? round(($deliveryStats->sent / $deliveryStats->total) * 100)
            : null;

        // Recent runs (last 5)
        $recentRuns = DigestRun::query()
            ->whereIn('digest_id', $digestIds)
            ->with('digest:id,name')
            ->latest()
            ->limit(5)
            ->get(['id', 'digest_id', 'status', 'finished_at', 'created_at']);

        // Upcoming digests (enabled digests that haven't run today, ordered by send_time)
        $upcomingDigests = $user->digests()
            ->where('is_enabled', true)
            ->withCount('sources')
            ->orderBy('send_time')
            ->limit(5)
            ->get(['id', 'name', 'frequency', 'send_time', 'send_day_of_week', 'timezone', 'last_successful_run_at']);

        return Inertia::render('dashboard', [
            'stats' => [
                'activeDigests' => $activeDigestsCount,
                'runsLast7Days' => $runsLast7Days,
                'deliverySuccessRate' => $deliverySuccessRate,
            ],
            'recentRuns' => $recentRuns,
            'upcomingDigests' => $upcomingDigests,
        ]);
    }
}
