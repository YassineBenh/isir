<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDigestRunJob;
use App\Models\Digest;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class DispatchDueDigestsCommand extends Command
{
    protected $signature = 'digests:dispatch-due';

    protected $description = 'Dispatch jobs for digests that are due to run';

    public function handle(): int
    {
        $now = now();
        $dispatched = 0;

        // Process daily digests
        $dispatched += $this->dispatchDailyDigests($now);

        // Process weekly digests
        $dispatched += $this->dispatchWeeklyDigests($now);

        $this->info("Dispatched {$dispatched} digest job(s).");

        return self::SUCCESS;
    }

    private function dispatchDailyDigests(Carbon $now): int
    {
        $dispatched = 0;

        Digest::query()
            ->where('is_enabled', true)
            ->where('frequency', 'daily')
            ->where(function (Builder $query) use ($now) {
                // Either never run, or last run was before today (in any timezone, rough filter)
                $query->whereNull('last_successful_run_at')
                    ->orWhere('last_successful_run_at', '<', $now->copy()->subDay());
            })
            ->each(function (Digest $digest) use ($now, &$dispatched) {
                if ($this->isWithinSendTimeWindow($digest, $now) && ! $this->hasRunToday($digest, $now)) {
                    ProcessDigestRunJob::dispatch($digest);
                    $dispatched++;
                    $this->line("Dispatched daily: {$digest->name} (ID: {$digest->id})");
                }
            });

        return $dispatched;
    }

    private function dispatchWeeklyDigests(Carbon $now): int
    {
        $dispatched = 0;

        // Get all possible day of week values for current time across all timezones
        // This is a rough filter - we'll do precise check per digest
        $possibleDays = $this->getPossibleDaysOfWeek($now);

        Digest::query()
            ->where('is_enabled', true)
            ->where('frequency', 'weekly')
            ->whereIn('send_day_of_week', $possibleDays)
            ->where(function (Builder $query) use ($now) {
                // Either never run, or last run was more than 6 days ago (rough filter)
                $query->whereNull('last_successful_run_at')
                    ->orWhere('last_successful_run_at', '<', $now->copy()->subDays(6));
            })
            ->each(function (Digest $digest) use ($now, &$dispatched) {
                if ($this->isWithinSendTimeWindow($digest, $now) && $this->isCorrectDayOfWeek($digest, $now) && ! $this->hasRunThisWeek($digest, $now)) {
                    ProcessDigestRunJob::dispatch($digest);
                    $dispatched++;
                    $this->line("Dispatched weekly: {$digest->name} (ID: {$digest->id})");
                }
            });

        return $dispatched;
    }

    /**
     * Get possible days of week across all timezones for the current moment.
     * UTC could be different day than UTC+14 or UTC-12.
     *
     * @return array<int>
     */
    private function getPossibleDaysOfWeek(Carbon $now): array
    {
        $days = [];

        // Check yesterday, today, tomorrow to cover all timezone possibilities
        $days[] = (int) $now->copy()->subDay()->format('w');
        $days[] = (int) $now->format('w');
        $days[] = (int) $now->copy()->addDay()->format('w');

        return array_unique($days);
    }

    private function isWithinSendTimeWindow(Digest $digest, Carbon $now): bool
    {
        $timezone = $digest->timezone ?? 'UTC';
        $nowInTimezone = $now->copy()->setTimezone($timezone);

        $sendDateTime = \DateTime::createFromFormat('H:i:s', $digest->send_time, new \DateTimeZone($timezone));
        $currentDateTime = \DateTime::createFromFormat('H:i', $nowInTimezone->format('H:i'), new \DateTimeZone($timezone));

        if (! $sendDateTime || ! $currentDateTime) {
            return false;
        }

        $diffMinutes = abs(($currentDateTime->getTimestamp() - $sendDateTime->getTimestamp()) / 60);

        return $diffMinutes <= 5;
    }

    private function isCorrectDayOfWeek(Digest $digest, Carbon $now): bool
    {
        $timezone = $digest->timezone ?? 'UTC';
        $nowInTimezone = $now->copy()->setTimezone($timezone);
        $currentDayOfWeek = (int) $nowInTimezone->format('w');

        return $digest->send_day_of_week === $currentDayOfWeek;
    }

    private function hasRunToday(Digest $digest, Carbon $now): bool
    {
        if (! $digest->last_successful_run_at) {
            return false;
        }

        $timezone = $digest->timezone ?? 'UTC';
        $nowInTimezone = $now->copy()->setTimezone($timezone);
        $lastRunInTimezone = $digest->last_successful_run_at->copy()->setTimezone($timezone);

        return $lastRunInTimezone->format('Y-m-d') === $nowInTimezone->format('Y-m-d');
    }

    private function hasRunThisWeek(Digest $digest, Carbon $now): bool
    {
        if (! $digest->last_successful_run_at) {
            return false;
        }

        $timezone = $digest->timezone ?? 'UTC';
        $nowInTimezone = $now->copy()->setTimezone($timezone);
        $lastRunInTimezone = $digest->last_successful_run_at->copy()->setTimezone($timezone);

        $daysSinceLastRun = $nowInTimezone->diffInDays($lastRunInTimezone);

        return $daysSinceLastRun < 6;
    }
}
