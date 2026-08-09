<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductForm
{
    // Per docs/design.md, translatable columns support PL/EN today (extensible later);
    // en is the fallback locale (config('app.fallback_locale')), so it's the required one.
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Translations')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('English')
                            ->schema([
                                TextInput::make('name.en')
                                    ->label('Name')
                                    ->required(),
                                Textarea::make('description.en')
                                    ->label('Description'),
                            ]),
                        Tab::make('Polski')
                            ->schema([
                                TextInput::make('name.pl')
                                    ->label('Name'),
                                Textarea::make('description.pl')
                                    ->label('Description'),
                            ]),
                    ]),
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
                TextInput::make('image_path'),
            ]);
    }
}
