<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductInfolist
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
                                    ->state(fn (Product $record) => $record->getTranslation('name', 'en', false)),
                                TextEntry::make('description.en')
                                    ->label('Description')
                                    ->state(fn (Product $record) => $record->getTranslation('description', 'en', false))
                                    ->placeholder('-'),
                            ]),
                        Tab::make('Polski')
                            ->schema([
                                TextEntry::make('name.pl')
                                    ->label('Name')
                                    ->state(fn (Product $record) => $record->getTranslation('name', 'pl', false))
                                    ->placeholder('-'),
                                TextEntry::make('description.pl')
                                    ->label('Description')
                                    ->state(fn (Product $record) => $record->getTranslation('description', 'pl', false))
                                    ->placeholder('-'),
                            ]),
                    ]),
                TextEntry::make('category.name')
                    ->label('Category'),
                TextEntry::make('price_cents')
                    ->numeric(),
                TextEntry::make('currency'),
                KeyValueEntry::make('attributes')
                    ->placeholder('-'),
                ImageEntry::make('main_image')
                    ->disk('s3')
                    ->visibility('public')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
