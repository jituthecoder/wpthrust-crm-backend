<?php

namespace App\Console\Commands;

use App\Services\Email\Campaign\CampaignRecoveryService;
use Illuminate\Console\Command;

class CampaignRecoverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:recover {--timeout= : Processing timeout in minutes} {--chunk=100 : Chunk size}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover stale processing campaign leads and reconcile sender capacity';

    /**
     * Execute the console command.
     */
    public function handle(CampaignRecoveryService $recoveryService): int
    {
        $timeoutOption = $this->option('timeout');
        $timeoutMinutes = $timeoutOption !== null ? (int) $timeoutOption : null;
        $chunkSize = (int) $this->option('chunk');

        $recovered = $recoveryService->recoverStaleLeads($timeoutMinutes, $chunkSize);

        $this->info("Completed campaign recovery. Recovered {$recovered} stale lead(s).");

        return Command::SUCCESS;
    }
}
