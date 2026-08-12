<?php

namespace App\Console\Commands;

use App\Jobs\FetchBusinessPsiJob;
use App\Models\Business;
use Illuminate\Console\Command;

class FetchPsiDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psi:fetch {--business_id= : Specific business ID to audit} {--limit=50 : Limit number of pending businesses to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Mobile PageSpeed Insights data & screenshots for businesses with websites';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $businessId = $this->option('business_id');
        $limit = (int) $this->option('limit');

        if ($businessId) {
            $business = Business::find($businessId);
            if (!$business) {
                $this->error("Business #{$businessId} not found.");
                return self::FAILURE;
            }

            if (empty($business->website)) {
                $this->warn("Business #{$businessId} does not have a website URL.");
                return self::FAILURE;
            }

            FetchBusinessPsiJob::dispatch($business);
            $this->info("Dispatched FetchBusinessPsiJob for Business #{$business->id} ({$business->website}).");
            return self::SUCCESS;
        }

        // Query businesses that have websites and either no audit or pending/failed psi_status
        $query = Business::query()
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->where(function ($q) {
                $q->whereDoesntHave('audit')
                  ->orWhereHas('audit', function ($auditQuery) {
                      $auditQuery->whereIn('psi_status', ['pending', 'failed'])
                                 ->orWhereNull('psi_fetched_at');
                  });
            })
            ->limit($limit);

        $businesses = $query->get();

        if ($businesses->isEmpty()) {
            $this->info("No pending businesses with websites found for PSI auditing.");
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($businesses as $b) {
            FetchBusinessPsiJob::dispatch($b);
            $count++;
        }

        $this->info("Dispatched PSI background audit jobs for {$count} business(es).");
        return self::SUCCESS;
    }
}
