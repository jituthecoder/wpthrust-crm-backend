<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\BusinessAudit;
use App\Jobs\FetchBusinessPsiJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ReauditMissingScreenshotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psi:reaudit-missing {--force : Re-audit all businesses regardless of missing screenshot status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-audit PageSpeed Insights and re-generate missing mobile screenshots for leads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $this->info('Scanning businesses for missing PageSpeed audits or missing screenshot files...');

        $query = Business::whereNotNull('website')
            ->where('website', '!=', '')
            ->where('website', '!=', '-');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereDoesntHave('audit')
                  ->orWhereHas('audit', function ($sq) {
                      $sq->whereNull('mobile_screenshot_path')->orWhere('mobile_screenshot_path', '');
                  });
            });
        }

        $businessIds = $query->pluck('id')->toArray();
        $count = count($businessIds);

        if ($count > 0) {
            foreach (array_chunk($businessIds, 500) as $chunk) {
                // Ensure audit records exist for all business IDs
                foreach ($chunk as $bId) {
                    BusinessAudit::firstOrCreate(
                        ['business_id' => $bId],
                        ['psi_status' => 'pending']
                    );
                }

                DB::table('business_audits')->whereIn('business_id', $chunk)->update([
                    'psi_status' => 'pending',
                    'mobile_screenshot_path' => null,
                    'updated_at' => now(),
                ]);
            }
        }

        $this->info("Successfully reset {$count} business leads to pending status for PageSpeed re-audit.");

        return Command::SUCCESS;
    }
}
