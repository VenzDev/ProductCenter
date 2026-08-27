<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\Language;
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
                    ->tabs(collect(Language::cases())->map(
                        fn (Language $language) => Tab::make($language->label())
                            ->schema([
                                TextEntry::make("name.{$language->value}")
                                    ->label('Name')
                                    ->state(fn (Product $record) => $record->getTranslation('name', $language->value, false))
                                    ->placeholder('-'),
                                TextEntry::make("description.{$language->value}")
                                    ->label('Description')
                                    ->state(fn (Product $record) => $record->getTranslation('description', $language->value, false))
                                    ->placeholder('-'),
                            ])
                    )->all()),
                Tabs::make('Details')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Details')
                            ->schema([
                                TextEntry::make('category.name')
                                    ->label('Category'),
                                TextEntry::make('price_cents')
                                    ->numeric(),
                                TextEntry::make('currency'),
                                KeyValueEntry::make('attributes')
                                    ->placeholder('-'),
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->placeholder('-'),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->placeholder('-'),
                            ]),
                        Tab::make('Media')
                            ->schema([
                                ImageEntry::make('main_image')
                                    ->disk('s3')
                                    ->visibility('public')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
