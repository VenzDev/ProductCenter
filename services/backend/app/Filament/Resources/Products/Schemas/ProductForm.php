<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

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
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('price_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('PLN'),
                KeyValue::make('attributes'),
                FileUpload::make('main_image')
                    ->image()
                    ->disk('s3')
                    ->directory('products')
                    ->visibility('public'),
            ]);
    }
}
