<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RepositoryServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // TODO in future versions: update statistics as required
        /*
        Queue::after(function (JobProcessed $event) {
            if ($event->connectionName === 'database') {
                // ex, File uploaded ++
                // $event->job
                // $event->job->payload()
            }
        });
        */
    }
}
