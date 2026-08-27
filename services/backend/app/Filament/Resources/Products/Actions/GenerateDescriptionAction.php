<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Actions;

use App\Ai\DescriptionGeneration\Job\GenerateProductDescriptionJob;
use App\Enums\Language;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class GenerateDescriptionAction
{
    public static function make(): Action
    {
        return Action::make('generateDescription')
            ->label('Generate description')
            ->icon('heroicon-o-sparkles')
            ->schema([
                Select::make('locale')
                    ->label('Language')
                    ->options(collect(Language::cases())->mapWithKeys(
                        fn (Language $language) => [$language->value => $language->label()]
                    ))
                    ->required(),
            ])
            ->action(function (array $data, Product $record): void {
                GenerateProductDescriptionJob::dispatch($record->id, $data['locale']);

                Notification::make()
                    ->title('Description generation queued')
                    ->success()
                    ->send();
            });
    }
}
