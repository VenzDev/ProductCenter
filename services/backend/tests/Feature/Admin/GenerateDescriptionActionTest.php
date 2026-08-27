<?php

declare(strict_types=1);

use App\Ai\DescriptionGeneration\Job\GenerateProductDescriptionJob;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ViewProduct;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Factories\AdminFactory;
use Tests\Factories\ProductFactory;

test('generating a description from the view page dispatches the job for the chosen locale', function () {
    Queue::fake();

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly([
        'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
        'attributes' => ['weight_kg' => 1.2],
    ]);
    $this->actingAs($admin, 'admin');

    Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('generateDescription', ['locale' => 'pl'])
        ->assertHasNoFormErrors();

    Queue::assertPushed(GenerateProductDescriptionJob::class, fn (GenerateProductDescriptionJob $job) => $job->productId === $product->id && $job->locale === 'pl');
});

test('the button is also available from the edit page', function () {
    Queue::fake();

    $admin = AdminFactory::new()->create();
    $product = ProductFactory::new()->createQuietly();
    $this->actingAs($admin, 'admin');

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('generateDescription', ['locale' => 'en'])
        ->assertHasNoFormErrors();

    Queue::assertPushed(GenerateProductDescriptionJob::class, fn (GenerateProductDescriptionJob $job) => $job->productId === $product->id && $job->locale === 'en');
});
