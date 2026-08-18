<?php

namespace App\Providers;

use App\Services\Ai\PrismProductManualAiService;
use App\Services\Ai\ProductManualAiService;
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
        $this->app->bind(ProductManualAiService::class, PrismProductManualAiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);
    }
}
