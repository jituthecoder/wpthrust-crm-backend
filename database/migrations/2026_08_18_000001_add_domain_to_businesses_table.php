<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('businesses', 'domain')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->string('domain', 255)->nullable()->after('website')->index();
            });
        }

        // Backfill existing domain values from website column
        DB::table('businesses')
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->whereNull('domain')
            ->chunkById(500, function ($businesses) {
                foreach ($businesses as $b) {
                    $url = $b->website;
                    if (!preg_match('~^https?://~i', $url)) {
                        $url = 'http://' . $url;
                    }
                    $host = parse_url($url, PHP_URL_HOST);
                    if (!$host) {
                        $cleaned = preg_replace('~^https?://~i', '', $b->website);
                        $host = explode('/', explode('?', explode('#', $cleaned)[0])[0])[0];
                    }
                    $host = strtolower(trim(preg_replace('~^www\.~i', '', $host)));

                    if (!empty($host)) {
                        DB::table('businesses')->where('id', $b->id)->update(['domain' => $host]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('businesses', 'domain')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('domain');
            });
        }
    }
};
