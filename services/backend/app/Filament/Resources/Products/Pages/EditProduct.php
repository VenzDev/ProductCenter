<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Actions\GenerateDescriptionAction;
use App\Filament\Resources\Products\Actions\GenerateImageAction;
use App\Filament\Resources\Products\ProductResource;
use App\Images\Support\AssetFormSync;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateDescriptionAction::make(),
            GenerateImageAction::make(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Product);

        $mainImage = $data['main_image'] ?? null;
        unset($data['main_image']);

        $updated = parent::handleRecordUpdate($record, $data);
        assert($updated instanceof Product);

        AssetFormSync::syncSingleImage($updated->mainImage(), is_string($mainImage) ? $mainImage : null);

        return $updated;
    }
}
