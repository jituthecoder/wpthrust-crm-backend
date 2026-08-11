<?php

namespace App\Console\Commands;

use App\Services\Email\Campaign\CampaignDeliverySchedulerService;
use Illuminate\Console\Command;

class CampaignProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:process {--chunk=100 : The chunk size for processing leads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due campaign leads and dispatch email delivery jobs';

    /**
     * Execute the console command.
     */
    public function handle(CampaignDeliverySchedulerService $scheduler): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dispatched = $scheduler->processDueLeads($chunkSize);

        $this->info("Processed due campaign leads. Dispatched {$dispatched} job(s).");

        return Command::SUCCESS;
    }
}
