<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\BusinessAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDomainsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:domains {file : Absolute or relative path to CSV file} {--source=import : Lead source name} {--chunk=2000 : Chunk size for batch insertion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import 100k+ domain and email records in high-performance batches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $leadSource = $this->option('source');
        $chunkSize = (int) $this->option('chunk');

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return self::FAILURE;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Failed to open file: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Starting import from {$filePath}...");

        // Pre-fetch existing websites and emails for fast deduplication
        $this->info("Loading existing websites and emails for duplicate check...");
        $existingWebsites = array_flip(
            array_filter(
                Business::whereNotNull('website')
                    ->pluck('website')
                    ->map(fn($w) => strtolower(trim(preg_replace('~^https?://~i', '', rtrim($w, '/')))))
                    ->toArray()
            )
        );

        $existingEmails = array_flip(
            array_filter(
                Business::whereNotNull('email')
                    ->pluck('email')
                    ->map(fn($e) => strtolower(trim($e)))
                    ->toArray()
            )
        );

        $firstRow = fgetcsv($handle, 0, ',');
        if (!$firstRow) {
            $this->error("CSV file is empty.");
            fclose($handle);
            return self::FAILURE;
        }

        // Determine column indexes
        $domainIndex = 0;
        $emailIndex = 1;

        $hasHeader = false;
        $headerMap = array_map(fn($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))), $firstRow);

        foreach ($headerMap as $idx => $col) {
            if (in_array($col, ['domain', 'website', 'url', 'site', 'domain_name'])) {
                $domainIndex = $idx;
                $hasHeader = true;
            }
            if (in_array($col, ['email', 'email_address', 'mail'])) {
                $emailIndex = $idx;
                $hasHeader = true;
            }
        }

        if (!$hasHeader) {
            // Re-seek to start if first line wasn't a header
            rewind($handle);
        }

        $imported = 0;
        $skipped = 0;
        $totalProcessed = 0;

        $businessBatch = [];
        $now = now()->toDateTimeString();

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $totalProcessed++;

            $rawDomain = trim($row[$domainIndex] ?? '');
            $rawEmail = strtolower(trim($row[$emailIndex] ?? ''));

            if (empty($rawDomain)) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            // Clean domain
            $cleanDomain = preg_replace('~^https?://~i', '', $rawDomain);
            $cleanDomain = rtrim(explode('?', explode('#', $cleanDomain)[0])[0], '/');
            $domainKey = strtolower($cleanDomain);

            // Deduplication
            if (isset($existingWebsites[$domainKey])) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            if (!empty($rawEmail) && isset($existingEmails[$rawEmail])) {
                $skipped++;
                $progressBar->advance();
                continue;
            }

            // Format website URL & business name
            $websiteUrl = 'https://' . $cleanDomain;
            $businessName = ucfirst(explode('.', $cleanDomain)[0] ?? $cleanDomain);

            // Add to in-memory index for batch deduplication
            $existingWebsites[$domainKey] = true;
            if (!empty($rawEmail)) {
                $existingEmails[$rawEmail] = true;
            }

            $businessBatch[] = [
                'business_name' => $businessName,
                'category' => null,
                'phone' => null,
                'email' => !empty($rawEmail) ? $rawEmail : null,
                'website' => $websiteUrl,
                'domain' => $cleanDomain,
                'address' => null,
                'city' => null,
                'state' => null,
                'zip_code' => null,
                'country' => null,
                'assigned_user_id' => null,
                'lead_source' => $leadSource,
                'lead_status' => 'new',
                'lead_priority' => 1,
                'call_attempts' => 0,
                'is_called' => false,
                'is_archived' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $imported++;
            $progressBar->advance();

            if (count($businessBatch) >= $chunkSize) {
                $this->flushBatch($businessBatch);
                $businessBatch = [];
            }
        }

        if (!empty($businessBatch)) {
            $this->flushBatch($businessBatch);
        }

        fclose($handle);
        $progressBar->finish();
        $this->newLine(2);

        $this->info("Import Completed Successfully!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', number_format($totalProcessed)],
                ['Successfully Imported', number_format($imported)],
                ['Skipped (Duplicates / Empty)', number_format($skipped)],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Flush batch of businesses into DB and create audit records
     */
    protected function flushBatch(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            DB::table('businesses')->insert($batch);

            // Fetch newly inserted business IDs for audit creation
            $websites = array_column($batch, 'website');
            $insertedBusinesses = DB::table('businesses')
                ->whereIn('website', $websites)
                ->pluck('id');

            $auditBatch = [];
            $now = now()->toDateTimeString();

            foreach ($insertedBusinesses as $id) {
                $auditBatch[] = [
                    'business_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($auditBatch)) {
                DB::table('business_audits')->insertOrIgnore($auditBatch);
            }
        });
    }
}
