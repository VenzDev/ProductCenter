<?php

declare(strict_types=1);

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Models\BlogPost;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, ?BlogPost $record) {
                        if (! $record?->exists) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                FileUpload::make('preview_image')
                    ->image()
                    ->disk('s3')
                    ->directory(fn (?BlogPost $record) => $record ? "blog-post-images/{$record->id}/uploads" : 'blog-post-images/tmp')
                    ->visibility('public'),
                RichEditor::make('content')
                    ->required()
                    ->resizableImages()
                    ->fileAttachmentsDisk('s3')
                    ->fileAttachmentsDirectory('blog-images')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
                DateTimePicker::make('published_at')
                    ->helperText('Leave empty to keep this post as a draft.'),
            ]);
    }
}
