<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneTelescopeLimitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:prune-limit {--limit=5000 : Maximum entries to keep} {--hours= : Also prune entries older than X hours} {--clear : Truncate all Telescope entries immediately}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limit or clear Laravel Telescope entries to prevent database bloat and server crashes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!Schema::hasTable('telescope_entries')) {
            $this->warn('telescope_entries table does not exist.');
            return 0;
        }

        if ($this->option('clear')) {
            Schema::disableForeignKeyConstraints();

            if (Schema::hasTable('telescope_entries_tags')) {
                DB::table('telescope_entries_tags')->truncate();
            }
            if (Schema::hasTable('telescope_monitoring')) {
                DB::table('telescope_monitoring')->truncate();
            }
            DB::table('telescope_entries')->truncate();

            Schema::enableForeignKeyConstraints();

            $this->info('All Telescope database tables truncated cleanly.');
            return 0;
        }

        $limit = (int) $this->option('limit');
        if ($limit <= 0) {
            $limit = 5000;
        }

        $total = DB::table('telescope_entries')->count();
        $this->info("Current Telescope entries count: {$total}");

        $deleted = 0;

        // 1. Prune by hours if provided
        if ($hours = $this->option('hours')) {
            $cutoffTime = now()->subHours((int) $hours);
            $hoursDeleted = DB::table('telescope_entries')
                ->where('created_at', '<', $cutoffTime)
                ->delete();
            $deleted += $hoursDeleted;
            $this->info("Pruned {$hoursDeleted} entries older than {$hours} hours.");
        }

        // 2. Enforce strict max record limit (keep newest $limit records) in chunks
        while (true) {
            $currentCount = DB::table('telescope_entries')->count();
            if ($currentCount <= $limit) {
                break;
            }

            $sequenceCutoff = DB::table('telescope_entries')
                ->orderBy('sequence', 'desc')
                ->skip($limit - 1)
                ->take(1)
                ->value('sequence');

            if (!$sequenceCutoff) {
                break;
            }

            $limitDeleted = DB::table('telescope_entries')
                ->where('sequence', '<', $sequenceCutoff)
                ->limit(5000)
                ->delete();

            if ($limitDeleted === 0) {
                break;
            }

            $deleted += $limitDeleted;
        }

        // 3. Clean up orphaned tags in telescope_entries_tags if table exists
        if (Schema::hasTable('telescope_entries_tags')) {
            $orphanedTags = DB::table('telescope_entries_tags')
                ->whereNotIn('entry_uuid', function ($query) {
                    $query->select('uuid')->from('telescope_entries');
                })
                ->delete();

            if ($orphanedTags > 0) {
                $this->info("Cleaned up {$orphanedTags} orphaned tags.");
            }
        }

        $finalCount = DB::table('telescope_entries')->count();
        $this->info("Pruning complete! Total deleted: {$deleted}. Remaining Telescope entries: {$finalCount}.");

        return 0;
    }
}
