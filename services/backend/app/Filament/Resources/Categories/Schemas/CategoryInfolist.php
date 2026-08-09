<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CategoryInfolist
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
                                TextEntry::make('name.en')
                                    ->label('Name')
                                    ->state(fn (Category $record) => $record->getTranslation('name', 'en', false)),
                            ]),
                        Tab::make('Polski')
                            ->schema([
                                TextEntry::make('name.pl')
                                    ->label('Name')
                                    ->state(fn (Category $record) => $record->getTranslation('name', 'pl', false))
                                    ->placeholder('-'),
                            ]),
                    ]),
                TextEntry::make('slug'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
