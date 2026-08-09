<?php

namespace App\Filament\Resources\Categories\Schemas;

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
                    ->tabs([
                        Tab::make('English')
                            ->schema([
                                TextInput::make('name.en')
                                    ->label('Name')
                                    ->required(),
                            ]),
                        Tab::make('Polski')
                            ->schema([
                                TextInput::make('name.pl')
                                    ->label('Name'),
                            ]),
                    ]),
                TextInput::make('slug')
                    ->required(),
            ]);
    }
}
