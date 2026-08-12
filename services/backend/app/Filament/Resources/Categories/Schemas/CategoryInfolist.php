<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\Language;
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
                    ->tabs(collect(Language::cases())->map(
                        fn (Language $language) => Tab::make($language->label())
                            ->schema([
                                TextEntry::make("name.{$language->value}")
                                    ->label('Name')
                                    ->state(fn (Category $record) => $record->getTranslation('name', $language->value, false))
                                    ->placeholder('-'),
                            ])
                    )->all()),
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
