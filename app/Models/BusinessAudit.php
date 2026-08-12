<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BusinessAudit extends Model
{
    protected $guarded = [];

    protected $appends = [
        'mobile_screenshot_url',
    ];

    protected function casts(): array
    {
        return [
            'psi_fetched_at' => 'datetime',
            'contact_form' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Dynamic Public Screenshot URL accessor
     */
    public function getMobileScreenshotUrlAttribute(): ?string
    {
        if (empty($this->mobile_screenshot_path)) {
            return null;
        }

        if (str_starts_with($this->mobile_screenshot_path, 'http://') || str_starts_with($this->mobile_screenshot_path, 'https://')) {
            return $this->mobile_screenshot_path;
        }

        $baseUrl = (request()->header('Host'))
            ? request()->schemeAndHttpHost()
            : rtrim(config('app.url'), '/');

        $path = ltrim($this->mobile_screenshot_path, '/');

        return "{$baseUrl}/storage/{$path}";
    }
}