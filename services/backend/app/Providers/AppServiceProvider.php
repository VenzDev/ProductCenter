<?php

namespace App\Providers;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SqsClient::class, fn () => new SqsClient([
            'version' => 'latest',
            'region' => config('services.sqs.region'),
            'endpoint' => config('services.sqs.endpoint'),
            'credentials' => [
                'key' => config('services.sqs.key'),
                'secret' => config('services.sqs.secret'),
            ],
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);
    }
}
