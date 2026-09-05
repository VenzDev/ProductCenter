<?php

declare(strict_types=1);

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Images\Support\AssetFormSync;
use App\Models\BlogPost;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof BlogPost);

        $previewImage = $data['preview_image'] ?? null;
        unset($data['preview_image']);

        $updated = parent::handleRecordUpdate($record, $data);
        assert($updated instanceof BlogPost);

        AssetFormSync::syncSingleImage($updated->previewImage(), is_string($previewImage) ? $previewImage : null);

        return $updated;
    }
}
