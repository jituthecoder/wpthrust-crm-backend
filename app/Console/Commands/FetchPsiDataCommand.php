<?php

namespace App\Console\Commands;

use App\Jobs\FetchBusinessPsiJob;
use App\Models\Business;
use App\Models\BusinessAudit;
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

        // Priority 1: Query existing audits that are marked 'pending' (indexed & instant)
        $pendingAudits = BusinessAudit::where('psi_status', 'pending')
            ->whereHas('business', function ($q) {
                $q->whereNotNull('website')
                  ->where('website', '!=', '')
                  ->where('website', '!=', '-')
                  ->whereRaw('LOWER(website) != ?', ['n/a']);
            })
            ->with('business')
            ->limit($limit)
            ->get();

        $businesses = collect();

        foreach ($pendingAudits as $audit) {
            if ($audit->business) {
                $businesses->push($audit->business);
            }
        }

        // Priority 2: If limit not reached, query businesses without an audit record
        if ($businesses->count() < $limit) {
            $remainingLimit = $limit - $businesses->count();
            $noAuditBusinesses = Business::whereNotNull('website')
                ->where('website', '!=', '')
                ->where('website', '!=', '-')
                ->whereRaw('LOWER(website) != ?', ['n/a'])
                ->whereDoesntHave('audit')
                ->limit($remainingLimit)
                ->get();

            foreach ($noAuditBusinesses as $b) {
                $businesses->push($b);
            }
        }

        if ($businesses->isEmpty()) {
            $this->info("No pending businesses with websites found for PSI auditing.");
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($businesses as $b) {
            BusinessAudit::updateOrCreate(
                ['business_id' => $b->id],
                ['psi_status' => 'processing']
            );

            FetchBusinessPsiJob::dispatch($b);
            $count++;
        }

        $this->info("Dispatched PSI background audit jobs for {$count} business(es).");
        return self::SUCCESS;
    }
}
