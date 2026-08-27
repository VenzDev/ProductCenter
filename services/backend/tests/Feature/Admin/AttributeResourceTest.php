<?php

declare(strict_types=1);

use App\Enums\AttributeType;
use App\Filament\Resources\Attributes\Pages\CreateAttribute;
use App\Filament\Resources\Attributes\Pages\EditAttribute;
use App\Models\Attribute;
use Livewire\Livewire;
use Tests\Factories\AdminFactory;

test('an admin can list attributes', function () {
    $admin = AdminFactory::new()->create();
    Attribute::create(['key' => 'weight_kg', 'name' => 'Weight', 'type' => AttributeType::Number]);

    $response = $this->actingAs($admin, 'admin')->get('/admin/attributes');

    $response->assertOk()->assertSee('weight_kg');
});

test('an admin can create a text attribute with translations for both locales', function () {
    $admin = AdminFactory::new()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateAttribute::class)
        ->fillForm([
            'key' => 'material',
            'name.en' => 'Material',
            'name.pl' => 'Materiał',
            'type' => AttributeType::Text->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $attribute = Attribute::where('key', 'material')->first();
    expect($attribute)->not->toBeNull();
    expect($attribute->type)->toBe(AttributeType::Text);
    expect($attribute->getTranslation('name', 'en', false))->toBe('Material');
    expect($attribute->getTranslation('name', 'pl', false))->toBe('Materiał');
});

test('a select attribute requires its options', function () {
    $admin = AdminFactory::new()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateAttribute::class)
        ->fillForm([
            'key' => 'color',
            'name.en' => 'Color',
            'type' => AttributeType::Select->value,
            'options' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['options']);
});

test('an admin can create a select attribute with translated options', function () {
    $admin = AdminFactory::new()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateAttribute::class)
        ->fillForm([
            'key' => 'color',
            'name.en' => 'Color',
            'type' => AttributeType::Select->value,
            'options' => [
                ['key' => 'red', 'name' => ['en' => 'Red', 'pl' => 'Czerwony']],
                ['key' => 'blue', 'name' => ['en' => 'Blue', 'pl' => 'Niebieski']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $attribute = Attribute::where('key', 'color')->first();
    expect($attribute->options)->toBe([
        ['key' => 'red', 'name' => ['de' => null, 'en' => 'Red', 'fr' => null, 'it' => null, 'pl' => 'Czerwony']],
        ['key' => 'blue', 'name' => ['de' => null, 'en' => 'Blue', 'fr' => null, 'it' => null, 'pl' => 'Niebieski']],
    ]);
});

test('a multiselect attribute requires its options', function () {
    $admin = AdminFactory::new()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateAttribute::class)
        ->fillForm([
            'key' => 'materials',
            'name.en' => 'Materials',
            'type' => AttributeType::MultiSelect->value,
            'options' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['options']);
});

test('an admin can create a multiselect attribute with translated options', function () {
    $admin = AdminFactory::new()->create();
    $this->actingAs($admin, 'admin');

    Livewire::test(CreateAttribute::class)
        ->fillForm([
            'key' => 'materials',
            'name.en' => 'Materials',
            'type' => AttributeType::MultiSelect->value,
            'options' => [
                ['key' => 'wood', 'name' => ['en' => 'Wood', 'pl' => 'Drewno']],
                ['key' => 'metal', 'name' => ['en' => 'Metal', 'pl' => 'Metal']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $attribute = Attribute::where('key', 'materials')->first();
    expect($attribute->type)->toBe(AttributeType::MultiSelect);
    expect($attribute->options)->toBe([
        ['key' => 'wood', 'name' => ['de' => null, 'en' => 'Wood', 'fr' => null, 'it' => null, 'pl' => 'Drewno']],
        ['key' => 'metal', 'name' => ['de' => null, 'en' => 'Metal', 'fr' => null, 'it' => null, 'pl' => 'Metal']],
    ]);
});

test('an admin can update an attribute', function () {
    $admin = AdminFactory::new()->create();
    $attribute = Attribute::create(['key' => 'weight_kg', 'name' => 'Weight', 'type' => AttributeType::Number]);
    $this->actingAs($admin, 'admin');

    Livewire::test(EditAttribute::class, ['record' => $attribute->getRouteKey()])
        ->fillForm(['name.en' => 'Weight (kg)'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($attribute->refresh()->getTranslation('name', 'en', false))->toBe('Weight (kg)');
});
