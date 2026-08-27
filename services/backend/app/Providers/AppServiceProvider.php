<?php

declare(strict_types=1);

namespace App\Providers;

use App\Ai\ProductAttachments\PrismProductAttachmentEmbedder;
use App\Ai\ProductAttachments\ProductAttachmentEmbedderInterface;
use App\Ai\ProductDescription\PrismProductDescriptionGenerator;
use App\Ai\ProductDescription\ProductDescriptionGeneratorInterface;
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
        $this->app->bind(ProductAttachmentEmbedderInterface::class, PrismProductAttachmentEmbedder::class);
        $this->app->bind(ProductDescriptionGeneratorInterface::class, PrismProductDescriptionGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);
    }
}
