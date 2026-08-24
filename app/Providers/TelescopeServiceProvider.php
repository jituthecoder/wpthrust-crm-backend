<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');
        $recordAll = config('telescope.record_all', env('TELESCOPE_RECORD_ALL', true));

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal, $recordAll) {
            if (!config('telescope.enabled', false)) {
                return false;
            }

            return $isLocal ||
                   $recordAll ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user = null) {
            if (
                $this->app->environment('local') ||
                config('telescope.allow_all', env('TELESCOPE_ALLOW_ALL', true))
            ) {
                return true;
            }

            if (!$user) {
                return false;
            }

            return in_array($user->email, [
                'jitendra@codegnan.com',
                'admin@wpthrust.in',
                'hello@wpthrust.in',
            ]) || ($user->role === 'super_admin');
        });
    }
}
