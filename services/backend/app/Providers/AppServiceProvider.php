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
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;
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

        $this->app->singleton(OpenSearchClient::class, function () {
            $builder = OpenSearchClientBuilder::create()->setHosts([config('opensearch.host')]);

            // Secured connection is driven by whether credentials are configured, not by the
            // environment name: the prod image is built with APP_ENV unset (so it defaults to
            // "production") but no OpenSearch env, which an app()->environment() check would
            // send down the authenticated path with null credentials and fatal. Local/testing
            // set no username and talk to a plain http OpenSearch.
            if (config('opensearch.username') !== null) {
                $builder
                    ->setBasicAuthentication(config('opensearch.username'), config('opensearch.password'))
                    ->setSSLVerification(config('opensearch.ssl_verification'));
            }

            return $builder->build();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);
    }
}
