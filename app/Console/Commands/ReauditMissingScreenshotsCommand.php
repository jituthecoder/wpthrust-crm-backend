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

        $businessIds = $query->pluck('id');
        $count = 0;

        foreach ($businessIds->chunk(200) as $chunk) {
            foreach ($chunk as $bId) {
                $audit = BusinessAudit::updateOrCreate(
                    ['business_id' => $bId],
                    ['psi_status' => 'pending', 'mobile_screenshot_path' => null]
                );
                FetchBusinessPsiJob::dispatch($audit->business);
                $count++;
            }
        }

        $this->info("Successfully queued {$count} business leads for PageSpeed re-audit.");

        return Command::SUCCESS;
    }
}
