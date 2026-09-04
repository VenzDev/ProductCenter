<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Actions;

use App\Ai\ImageGeneration\Job\GenerateProductImageJob;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class GenerateImageAction
{
    public static function make(): Action
    {
        return Action::make('generateImage')
            ->label('Generate image')
            ->icon('heroicon-o-photo')
            ->requiresConfirmation()
            ->modalDescription('This replaces the current main image with an AI-generated one, based on the product\'s name, description, and attributes.')
            ->action(function (Product $record): void {
                GenerateProductImageJob::dispatch($record->id);

                Notification::make()
                    ->title('Image generation queued')
                    ->success()
                    ->send();
            });
    }
}
