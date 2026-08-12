<?php

namespace App\Filament\Resources\Products\Actions;

use App\Enums\Language;
use App\Models\Product;
use App\Services\Sqs\Data\ProductDescriptionRequestData;
use App\Services\Sqs\SqsPublisher;
use App\Services\Sqs\SqsQueue;
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
            ->action(function (array $data, Product $record, SqsPublisher $publisher): void {
                $publisher->publish(
                    SqsQueue::ProductDescriptionRequested,
                    ProductDescriptionRequestData::fromProduct($record, $data['locale']),
                );

                Notification::make()
                    ->title('Description request queued')
                    ->success()
                    ->send();
            });
    }
}
