<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessAudit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BusinessImportService
{
    protected int $imported = 0;
    protected int $skipped = 0;
    protected array $errors = [];

    /**
     * Import CSV
     */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            throw new \Exception('Unable to open CSV file.');
        }

        // Read CSV Header
        $header = fgetcsv($handle);

        if (!$header) {
            throw new \Exception('Invalid CSV.');
        }

        // Remove UTF8 BOM
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        while (($row = fgetcsv($handle, 0, ',')) !== false) {

            if (count($header) != count($row)) {
                continue;
            }

            $data = array_combine($header, $row);

            try {

                DB::beginTransaction();

                $business = $this->createBusiness($data);

                if (!$business) {

                    DB::rollBack();

                    continue;
                }

                $this->createAudit($business, $data);

                DB::commit();

                $this->imported++;

            } catch (\Exception $e) {

                DB::rollBack();

                $this->errors[] = [

                    'business' => $data['Business Name'] ?? '',

                    'error' => $e->getMessage(),

                ];
            }

        }

        fclose($handle);

        return [

            'success' => true,

            'imported' => $this->imported,

            'skipped' => $this->skipped,

            'errors' => $this->errors,

        ];
    }

    /**
     * Create Business
     */
    protected function createBusiness(array $data): ?Business
    {
        $website = $this->clean($data['Website'] ?? '');

        $phone = $this->clean($data['Phone'] ?? '');

        $email = strtolower($this->clean($data['Email'] ?? ''));

        // Duplicate Check

        $query = Business::query();

        if (!empty($website)) {
            $query->orWhere('website', $website);
        }

        if (!empty($phone)) {
            $query->orWhere('phone', $phone);
        }

        if (!empty($email)) {
            $query->orWhere('email', $email);
        }

        $exists = $query->exists();

        if ($exists) {

            $this->skipped++;

            return null;
        }

        return Business::create([

            'business_name' => $this->clean($data['Business Name'] ?? null),

            'category' => $this->clean($data['Category'] ?? null),

            'phone' => $phone,

            'email' => $email,

            'website' => $website,

            'address' => $this->clean($data['Address'] ?? null),

            'city' => $this->clean($data['City'] ?? null),

            'state' => $this->clean($data['State'] ?? null),

            'zip_code' => $this->clean($data['Zip Code'] ?? null),

            'country' => $this->clean($data['Country'] ?? null),

            'lead_source' => 'google_maps',

            'lead_status' => 'new',

            'lead_priority' => 1,

            'call_attempts' => 0,

            'is_called' => false,

            'is_archived' => false,

        ]);
    }
        /**
     * Create Business Audit
     */
    protected function createAudit(Business $business, array $data): BusinessAudit
    {
        return BusinessAudit::create([

            'business_id' => $business->id,

            'average_rating' => $this->normalizeValue($data['Average Rating'] ?? null),
            'review_count' => (int) ($this->normalizeValue($data['Review Count'] ?? 0) ?: 0),

            // Mobile
            'mobile_pagespeed' => $this->normalizeValue($data['Mobile PageSpeed'] ?? null),
            'mobile_seo' => $this->normalizeValue($data['Mobile SEO'] ?? null),
            'mobile_accessibility' => $this->normalizeValue($data['Mobile Accessibility'] ?? null),
            'mobile_best_practices' => $this->normalizeValue($data['Mobile Best Practices'] ?? null),
            'mobile_load_time' => $this->normalizeValue($data['Mobile Load Time (s)'] ?? null),
            'mobile_lcp' => $this->normalizeValue($data['Mobile LCP (s)'] ?? null),
            'mobile_tbt' => $this->normalizeValue($data['Mobile TBT (ms)'] ?? null),

            // Desktop
            'desktop_pagespeed' => $this->normalizeValue($data['Desktop PageSpeed'] ?? null),
            'desktop_seo' => $this->normalizeValue($data['Desktop SEO'] ?? null),
            'desktop_accessibility' => $this->normalizeValue($data['Desktop Accessibility'] ?? null),
            'desktop_best_practices' => $this->normalizeValue($data['Desktop Best Practices'] ?? null),
            'desktop_load_time' => $this->normalizeValue($data['Desktop Load Time (s)'] ?? null),
            'desktop_lcp' => $this->normalizeValue($data['Desktop LCP (s)'] ?? null),
            'desktop_tbt' => $this->normalizeValue($data['Desktop TBT (ms)'] ?? null),

            // Social
            'contact_form' => $this->normalizeBoolean($data['Contact Form'] ?? null),

            'facebook' => $this->normalizeValue($data['Facebook'] ?? null),
            'instagram' => $this->normalizeValue($data['Instagram'] ?? null),
            'linkedin' => $this->normalizeValue($data['LinkedIn'] ?? null),

        ]);
    }

    /**
     * Convert Yes/No to boolean
     */
    protected function normalizeBoolean($value): bool
    {
        if ($value === null) {
            return false;
        }

        $value = strtolower(trim($value));

        return in_array($value, [
            'yes',
            'true',
            '1',
            'available',
        ]);
    }

    /**
     * Clean values before saving
     */
    protected function normalizeValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (in_array(strtolower($value), [
            'n/a',
            'na',
            '-',
            '?',
            'null',
            'undefined',
        ])) {
            return null;
        }

        return $value;
    }

    /**
     * Clean text
     */
    protected function clean($value): ?string
    {
        return $this->normalizeValue($value);
    }
}