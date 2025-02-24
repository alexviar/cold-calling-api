<?php

namespace App\Providers;

use App\Integrations\GoogleTextToSpeech;
use App\Integrations\TelnyxCall;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\CallServiceContract::class, function () {
            // $callService = config('services.call.default');

            // if ($callService === 'telnyx') {
            //     return new TelnyxCall();
            // }

            return new TelnyxCall();
        });

        $this->app->bind(\App\Services\VoiceSynthesizerContract::class, function () {

            return new GoogleTextToSpeech();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
