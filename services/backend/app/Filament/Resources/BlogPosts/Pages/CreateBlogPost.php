<?php

declare(strict_types=1);

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Images\Support\AssetFormSync;
use App\Models\BlogPost;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): BlogPost
    {
        $previewImage = $data['preview_image'] ?? null;
        unset($data['preview_image']);

        $post = parent::handleRecordCreation($data);
        assert($post instanceof BlogPost);

        AssetFormSync::syncSingleImage($post->previewImage(), is_string($previewImage) ? $previewImage : null);

        return $post;
    }
}
