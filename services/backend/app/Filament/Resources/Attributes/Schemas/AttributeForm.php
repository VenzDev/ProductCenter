<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attributes\Schemas;

use App\Enums\AttributeType;
use App\Enums\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                Tabs::make('Translations')
                    ->columnSpanFull()
                    ->tabs(collect(Language::cases())->map(
                        fn (Language $language) => Tab::make($language->label())
                            ->schema([
                                TextInput::make("name.{$language->value}")
                                    ->label('Name')
                                    ->required($language->isFallback()),
                            ])
                    )->all()),
                Select::make('type')
                    ->options(collect(AttributeType::cases())->mapWithKeys(
                        fn (AttributeType $type) => [$type->value => $type->label()]
                    ))
                    ->required()
                    ->live(),
                Toggle::make('filterable')
                    ->helperText('Whether this attribute can be used as a product listing filter.')
                    ->visible(fn (Get $get) => AttributeType::tryFrom($get('type') ?? '')?->isFilterable())
                    ->dehydrated(fn (Get $get) => AttributeType::tryFrom($get('type') ?? '')?->isFilterable()),
                Repeater::make('options')
                    ->columnSpanFull()
                    ->helperText('The selectable values for this attribute, translated per language.')
                    ->schema([
                        TextInput::make('key')
                            ->required()
                            ->alphaDash(),
                        ...collect(Language::cases())->map(
                            fn (Language $language) => TextInput::make("name.{$language->value}")
                                ->label($language->label())
                                ->required($language->isFallback())
                        )->all(),
                    ])
                    ->columns([
                        'default' => 2,
                        'md' => 3,
                        'xl' => 1 + count(Language::cases()),
                    ])
                    ->addActionLabel('Add option')
                    ->reorderable(false)
                    ->defaultItems(0)
                    ->visible(fn (Get $get) => AttributeType::tryFrom($get('type') ?? '')?->hasOptions())
                    ->required(fn (Get $get) => AttributeType::tryFrom($get('type') ?? '')?->hasOptions()),
            ]);
    }
}
