<?php

namespace App\Console\Commands;

use App\Support\YamlStore;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Data retention: deletes visit-log and feedback entries older than the
 * configured number of days (see privacy policy). Scheduled to run daily —
 * Forge runs the Laravel scheduler for every site automatically, no manual
 * cron setup needed.
 */
class PruneOldData extends Command
{
    protected $signature = 'data:prune {--days=90}';

    protected $description = 'Delete log and feedback entries older than the retention period';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subDays((int) $this->option('days'));

        $isOld = function (array $entry) use ($cutoff) {
            if (empty($entry['time'])) {
                return false;
            }
            try {
                return Carbon::parse($entry['time'])->lt($cutoff);
            } catch (\Throwable) {
                return false;
            }
        };

        $removedLog = YamlStore::removeWhere('log', $isOld);

        $removedFeedback = YamlStore::removeWhere('feedback', $isOld);
        foreach ($removedFeedback as $entry) {
            if (! empty($entry['screenshot'])) {
                Storage::disk('local')->delete($entry['screenshot']);
            }
        }

        $this->info(sprintf(
            'Pruned %d log entries and %d feedback entries older than %s.',
            count($removedLog),
            count($removedFeedback),
            $cutoff->toDateString()
        ));

        return self::SUCCESS;
    }
}
