<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\Language;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CategoryForm
{
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
                            ])
                    )->all()),
                TextInput::make('slug')
                    ->required(),
            ]);
    }
}
