<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\BusinessAudit;
use App\Jobs\FetchBusinessPsiJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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

        $dispatched = 0;

        Business::whereNotNull('website')
            ->where('website', '!=', '')
            ->where('website', '!=', '-')
            ->select('id', 'website')
            ->chunk(500, function ($businesses) use ($force, &$dispatched) {
                foreach ($businesses as $business) {
                    $audit = $business->audit;

                    $missing = false;

                    if (!$audit || $force || empty($audit->mobile_screenshot_path)) {
                        $missing = true;
                    } elseif (!Storage::disk('public')->exists($audit->mobile_screenshot_path)) {
                        $missing = true;
                    }

                    if ($missing) {
                        FetchBusinessPsiJob::dispatch($business);
                        $dispatched++;
                    }
                }
            });

        $this->info("Successfully queued {$dispatched} business leads for PageSpeed re-audit and screenshot generation.");

        return Command::SUCCESS;
    }
}
