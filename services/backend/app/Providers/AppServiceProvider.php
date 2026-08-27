<?php

declare(strict_types=1);

namespace App\Providers;

use App\Ai\AttachmentEmbeddingsGeneration\Embedder\PrismProductAttachmentEmbedder;
use App\Ai\AttachmentEmbeddingsGeneration\Embedder\ProductAttachmentEmbedderInterface;
use App\Ai\AttachmentEmbeddingsGeneration\Splitter\ChunksSplitterInterface;
use App\Ai\AttachmentEmbeddingsGeneration\Splitter\MinimalChunksSplitter;
use App\Ai\DescriptionGeneration\Generator\PrismProductDescriptionGenerator;
use App\Ai\DescriptionGeneration\Generator\ProductDescriptionGeneratorInterface;
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
        $this->app->bind(ChunksSplitterInterface::class, MinimalChunksSplitter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);
    }
}
