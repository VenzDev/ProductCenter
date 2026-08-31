<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\AttributeType;
use App\Enums\Language;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Product\Support\AttributeDefinitions;
use App\Storage\StorageDisk;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    // Per docs/design.md, translatable columns support the languages in App\Enums\Language;
    // the fallback locale (config('app.fallback_locale')) is the required one.
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::translationsTab(),
                self::detailsTab(),
            ]);
    }

    private static function translationsTab(): Tabs
    {
        return Tabs::make('Translations')
            ->columnSpanFull()
            ->tabs(collect(Language::cases())->map(
                fn (Language $language) => Tab::make($language->label())
                    ->schema([
                        TextInput::make("name.{$language->value}")
                            ->label('Name')
                            ->required($language->isFallback()),
                        Textarea::make("description.{$language->value}")
                            ->label('Description'),
                    ])
            )->all());
    }

    private static function detailsTab(): Tabs
    {
        return Tabs::make('Details')
            ->columnSpanFull()
            ->tabs([
                Tab::make('Details')->schema(self::detailsSchema()),
                Tab::make('Media')->schema(self::mediaSchema()),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function detailsSchema(): array
    {
        return [
            Select::make('category_id')
                ->relationship('category', 'name')
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state, ?Product $record) {
                    if ($record?->exists) {
                        return;
                    }

                    $category = $state ? Category::query()->find($state) : null;
                    $rows = $category ? $category->attributes : collect();

                    $set('attributes', $rows
                        ->mapWithKeys(fn (Attribute $attribute) => [
                            (string) Str::uuid() => ['key' => $attribute->key, 'value' => null],
                        ])
                        ->all());
                }),
            TextInput::make('price_cents')
                ->required()
                ->numeric(),
            TextInput::make('currency')
                ->required()
                ->default('PLN'),
            self::attributesRepeater(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function mediaSchema(): array
    {
        return [
            FileUpload::make('main_image')
                ->image()
                ->required()
                ->disk(StorageDisk::S3)
                ->directory(fn (?Product $record) => $record ? "product-images/{$record->id}/uploads" : 'product-images/tmp')
                ->visibility('public'),
        ];
    }

    /**
     * A product's attribute value is stored under exactly one of three widgets below,
     * chosen by the selected attribute's type: a plain input, a select fed by the
     * attribute's options, or a per-language text grid. All three share the 'attributes'
     * repeater's row and are mutually exclusive via isPlainValue/isOptionsValue/isTranslatedValue.
     */
    private static function attributesRepeater(): Repeater
    {
        return Repeater::make('attributes')
            ->schema([
                Select::make('key')
                    ->label('Attribute')
                    ->options(fn () => AttributeDefinitions::all()->map(fn (Attribute $attribute) => $attribute->name))
                    ->required()
                    ->live()
                    ->searchable()
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('value')
                    ->numeric(fn (Get $get) => self::attributeType($get('key')) === AttributeType::Number)
                    ->visible(fn (Get $get) => self::isPlainValue($get('key')))
                    ->dehydrated(fn (Get $get) => self::isPlainValue($get('key'))),
                Select::make('value')
                    ->options(fn (Get $get) => self::selectOptions($get('key')))
                    ->multiple(fn (Get $get) => self::attributeType($get('key')) === AttributeType::MultiSelect)
                    ->visible(fn (Get $get) => self::isOptionsValue($get('key')))
                    ->dehydrated(fn (Get $get) => self::isOptionsValue($get('key'))),
                Grid::make()
                    ->columnSpanFull()
                    ->columns([
                        'default' => 2,
                        'md' => 3,
                        'xl' => count(Language::cases()),
                    ])
                    ->visible(fn (Get $get) => self::isTranslatedValue($get('key')))
                    ->schema(collect(Language::cases())->map(
                        fn (Language $language) => TextInput::make("value_translations.{$language->value}")
                            ->label($language->label())
                            ->required(fn (Get $get) => $language->isFallback() && self::isTranslatedValue($get('key')))
                            ->dehydrated(fn (Get $get) => self::isTranslatedValue($get('key')))
                    )->all()),
            ])
            ->columns(2)
            ->addActionLabel('Add attribute')
            ->reorderable(false)
            ->defaultItems(0)
            ->afterStateHydrated(function (Repeater $component) {
                $flatMap = $component->getRawState();

                $rows = collect(is_array($flatMap) ? $flatMap : [])
                    ->mapWithKeys(function ($value, $key) {
                        $valueKey = self::isTranslatedValue($key) ? 'value_translations' : 'value';

                        return [(string) Str::uuid() => ['key' => $key, $valueKey => $value]];
                    })
                    ->all();

                $component->rawState($rows);
            })
            ->mutateDehydratedStateUsing(fn (array $state) => collect($state)
                ->filter(fn ($row) => is_array($row) && filled($row['key'] ?? null))
                ->mapWithKeys(fn ($row) => [
                    // Read from the widget the attribute's type actually uses — not by
                    // checking which key is present. A non-dehydrated Grid field still
                    // leaves an empty 'value_translations' array in $row, which is not
                    // null, so a `??` fallback would silently prefer it over 'value'.
                    $row['key'] => self::isTranslatedValue($row['key'])
                        ? ($row['value_translations'] ?? null)
                        : ($row['value'] ?? null),
                ])
                ->all());
    }

    private static function attributeType(?string $key): ?AttributeType
    {
        if (! $key) {
            return null;
        }

        return AttributeDefinitions::all()->get($key)?->type;
    }

    private static function isPlainValue(?string $key): bool
    {
        return ! self::isOptionsValue($key) && ! self::isTranslatedValue($key);
    }

    private static function isOptionsValue(?string $key): bool
    {
        return self::attributeType($key)?->hasOptions() ?? false;
    }

    private static function isTranslatedValue(?string $key): bool
    {
        return self::attributeType($key)?->isTranslatable() ?? false;
    }

    /**
     * @return array<string, string>
     */
    private static function selectOptions(?string $key): array
    {
        return ($key ? AttributeDefinitions::all()->get($key) : null)?->translatedOptions() ?? [];
    }
}
