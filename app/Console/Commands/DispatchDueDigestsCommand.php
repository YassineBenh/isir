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
        $now = Carbon::instance(now());
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
            ->each(function (Digest $digest) use ($now, &$dispatched) {
                $nowInTimezone = $this->nowInDigestTimezone($digest, $now);

                if (! $this->isWithinSendTimeWindow($digest, $nowInTimezone)) {
                    return;
                }

                if ($this->hasDispatchedToday($digest, $nowInTimezone)) {
                    return;
                }

                $dayStartUtc = $nowInTimezone->copy()->startOfDay()->setTimezone('UTC');

                if (! $this->markAsDispatched($digest, $dayStartUtc, $now)) {
                    return;
                }

                ProcessDigestRunJob::dispatch($digest);
                $dispatched++;
                $this->line("Dispatched daily: {$digest->name} (ID: {$digest->id})");
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
            ->each(function (Digest $digest) use ($now, &$dispatched) {
                $nowInTimezone = $this->nowInDigestTimezone($digest, $now);

                if (! $this->isWithinSendTimeWindow($digest, $nowInTimezone)) {
                    return;
                }

                if (! $this->isCorrectDayOfWeek($digest, $nowInTimezone)) {
                    return;
                }

                if ($this->hasDispatchedThisWeek($digest, $nowInTimezone)) {
                    return;
                }

                $weekStartUtc = $nowInTimezone->copy()->startOfWeek(Carbon::SUNDAY)->setTimezone('UTC');

                if (! $this->markAsDispatched($digest, $weekStartUtc, $now)) {
                    return;
                }

                ProcessDigestRunJob::dispatch($digest);
                $dispatched++;
                $this->line("Dispatched weekly: {$digest->name} (ID: {$digest->id})");
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

    private function isWithinSendTimeWindow(Digest $digest, Carbon $nowInTimezone): bool
    {
        $sendDateTime = $this->sendDateTime($digest, $nowInTimezone);

        if (! $sendDateTime) {
            return false;
        }

        $diffMinutes = $sendDateTime->diffInMinutes($nowInTimezone, false);

        return $diffMinutes >= 0 && $diffMinutes <= 5;
    }

    private function isCorrectDayOfWeek(Digest $digest, Carbon $nowInTimezone): bool
    {
        $currentDayOfWeek = (int) $nowInTimezone->format('w');

        return $digest->send_day_of_week === $currentDayOfWeek;
    }

    private function hasDispatchedToday(Digest $digest, Carbon $nowInTimezone): bool
    {
        $lastDispatch = $this->lastDispatchReference($digest);

        if (! $lastDispatch) {
            return false;
        }

        $timezone = $digest->timezone ?? 'UTC';
        $lastRunInTimezone = $lastDispatch->copy()->setTimezone($timezone);

        return $lastRunInTimezone->format('Y-m-d') === $nowInTimezone->format('Y-m-d');
    }

    private function hasDispatchedThisWeek(Digest $digest, Carbon $nowInTimezone): bool
    {
        $lastDispatch = $this->lastDispatchReference($digest);

        if (! $lastDispatch) {
            return false;
        }

        $timezone = $digest->timezone ?? 'UTC';
        $lastRunInTimezone = $lastDispatch->copy()->setTimezone($timezone);

        $weekStart = $nowInTimezone->copy()->startOfWeek(Carbon::SUNDAY);
        $lastWeekStart = $lastRunInTimezone->copy()->startOfWeek(Carbon::SUNDAY);

        return $weekStart->isSameDay($lastWeekStart);
    }

    private function nowInDigestTimezone(Digest $digest, Carbon $now): Carbon
    {
        $timezone = $digest->timezone ?? 'UTC';

        return $now->copy()->setTimezone($timezone);
    }

    private function sendDateTime(Digest $digest, Carbon $nowInTimezone): ?Carbon
    {
        $sendTime = $this->normalizeSendTime($digest->send_time);

        if (! $sendTime) {
            return null;
        }

        return $nowInTimezone->copy()->setTimeFromTimeString($sendTime);
    }

    private function normalizeSendTime(?string $sendTime): ?string
    {
        if (! $sendTime) {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $sendTime) === 1) {
            return $sendTime.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $sendTime) === 1) {
            return $sendTime;
        }

        $parsed = \DateTime::createFromFormat('H:i:s', $sendTime)
            ?: \DateTime::createFromFormat('H:i', $sendTime);

        if (! $parsed) {
            return null;
        }

        return $parsed->format('H:i:s');
    }

    private function lastDispatchReference(Digest $digest): ?Carbon
    {
        $reference = $digest->last_dispatched_at ?? $digest->last_successful_run_at;

        if (! $reference) {
            return null;
        }

        return Carbon::instance($reference);
    }

    private function markAsDispatched(Digest $digest, Carbon $periodStartUtc, Carbon $now): bool
    {
        return Digest::query()
            ->whereKey($digest->id)
            ->where(function (Builder $query) use ($periodStartUtc) {
                $query->whereNull('last_dispatched_at')
                    ->orWhere('last_dispatched_at', '<', $periodStartUtc);
            })
            ->update(['last_dispatched_at' => $now]) === 1;
    }
}
