<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\BusinessAudit;
use App\Services\PageSpeed\GooglePsiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchBusinessPsiJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Business $business
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GooglePsiService $psiService): void
    {
        if (empty($this->business->website)) {
            return;
        }

        // Set status to processing
        BusinessAudit::updateOrCreate(
            ['business_id' => $this->business->id],
            ['psi_status' => 'processing']
        );

        try {
            $psiService->analyzeBusiness($this->business);
            Log::info("PSI analysis completed for Business #{$this->business->id} ({$this->business->website}).");
        } catch (\Throwable $e) {
            Log::warning("PSI analysis error for Business #{$this->business->id} ({$this->business->website}): " . $e->getMessage());

            BusinessAudit::updateOrCreate(
                ['business_id' => $this->business->id],
                [
                    'psi_status' => 'failed',
                    'psi_error_reason' => mb_substr($e->getMessage(), 0, 255),
                    'psi_fetched_at' => now(),
                ]
            );
        }
    }
}
