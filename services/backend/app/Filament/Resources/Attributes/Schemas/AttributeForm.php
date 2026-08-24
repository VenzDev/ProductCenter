<?php

namespace App\Filament\Resources\Attributes\Schemas;

use App\Enums\AttributeType;
use App\Enums\Language;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
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
                TagsInput::make('options')
                    ->helperText('The selectable values for this attribute.')
                    ->visible(fn (Get $get) => AttributeType::tryFrom($get('type') ?? '')?->hasOptions())
                    ->required(fn (Get $get) => AttributeType::tryFrom($get('type') ?? '')?->hasOptions()),
            ]);
    }
}
