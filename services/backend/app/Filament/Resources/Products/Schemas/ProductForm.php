<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\AttributeType;
use App\Enums\Language;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductForm
{
    // Per docs/design.md, translatable columns support the languages in App\Enums\Language;
    // the fallback locale (config('app.fallback_locale')) is the required one.
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Translations')
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
                    )->all()),
                Tabs::make('Details')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Details')
                            ->schema([
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
                                Repeater::make('attributes')
                                    ->schema([
                                        Select::make('key')
                                            ->label('Attribute')
                                            ->options(fn () => self::attributesByKey()->map(fn (Attribute $attribute) => $attribute->name))
                                            ->required()
                                            ->live()
                                            ->searchable()
                                            ->distinct()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                        TextInput::make('value')
                                            ->numeric(fn (Get $get) => self::attributeType($get('key')) === AttributeType::Number)
                                            ->visible(fn (Get $get) => self::attributeType($get('key')) !== AttributeType::Select)
                                            ->dehydrated(fn (Get $get) => self::attributeType($get('key')) !== AttributeType::Select),
                                        Select::make('value')
                                            ->options(fn (Get $get) => self::selectOptions($get('key')))
                                            ->visible(fn (Get $get) => self::attributeType($get('key')) === AttributeType::Select)
                                            ->dehydrated(fn (Get $get) => self::attributeType($get('key')) === AttributeType::Select),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Add attribute')
                                    ->reorderable(false)
                                    ->defaultItems(0)
                                    ->afterStateHydrated(function (Repeater $component) {
                                        $flatMap = $component->getRawState();

                                        $rows = collect(is_array($flatMap) ? $flatMap : [])
                                            ->mapWithKeys(fn ($value, $key) => [
                                                (string) Str::uuid() => ['key' => $key, 'value' => $value],
                                            ])
                                            ->all();

                                        $component->rawState($rows);
                                    })
                                    ->mutateDehydratedStateUsing(fn (array $state) => collect($state)
                                        ->filter(fn ($row) => is_array($row) && filled($row['key'] ?? null))
                                        ->mapWithKeys(fn ($row) => [$row['key'] => $row['value'] ?? null])
                                        ->all()),
                            ]),
                        Tab::make('Media')
                            ->schema([
                                FileUpload::make('main_image')
                                    ->image()
                                    ->disk('s3')
                                    ->directory(fn (?Product $record) => $record ? "product-images/{$record->id}/uploads" : 'product-images/tmp')
                                    ->visibility('public'),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return Collection<string, Attribute>
     */
    private static function attributesByKey(): Collection
    {
        return once(fn () => Attribute::query()->get()->keyBy('key'));
    }

    private static function attributeType(?string $key): ?AttributeType
    {
        if (! $key) {
            return null;
        }

        return self::attributesByKey()->get($key)?->type;
    }

    /**
     * @return array<string, string>
     */
    private static function selectOptions(?string $key): array
    {
        $attribute = $key ? self::attributesByKey()->get($key) : null;

        if (! $attribute) {
            return [];
        }

        return collect($attribute->options ?? [])->mapWithKeys(fn (string $option) => [$option => $option])->all();
    }
}
