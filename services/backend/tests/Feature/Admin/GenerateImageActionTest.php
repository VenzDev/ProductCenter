<?php

declare(strict_types=1);

use App\Ai\ImageGeneration\Job\GenerateProductImageJob;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ViewProduct;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Factories\AdminFactory;
use Tests\Factories\ProductFactory;

test('generating an image from the view page dispatches the job', function () {
    Queue::fake();

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly();
    $this->actingAs($admin, 'admin');

    Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('generateImage')
        ->assertHasNoFormErrors();

    Queue::assertPushed(GenerateProductImageJob::class, fn (GenerateProductImageJob $job) => $job->productId === $product->id);
});

test('the button is also available from the edit page', function () {
    Queue::fake();

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly();
    $this->actingAs($admin, 'admin');

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('generateImage')
        ->assertHasNoFormErrors();

    Queue::assertPushed(GenerateProductImageJob::class, fn (GenerateProductImageJob $job) => $job->productId === $product->id);
});
