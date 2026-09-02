<?php

declare(strict_types=1);

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Enums\Language;
use App\Models\BlogPost;
use App\Storage\StorageDisk;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
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
                                TextInput::make("title.{$language->value}")
                                    ->label('Title')
                                    ->required($language->isFallback())
                                    ->when($language->isFallback(), fn (TextInput $input) => $input
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Set $set, ?string $state, ?BlogPost $record) {
                                            if (! $record?->exists) {
                                                $set('slug', Str::slug($state ?? ''));
                                            }
                                        })),
                                RichEditor::make("content.{$language->value}")
                                    ->label('Content')
                                    ->required($language->isFallback())
                                    ->resizableImages()
                                    ->fileAttachmentsDisk(StorageDisk::S3)
                                    ->fileAttachmentsDirectory('blog-images')
                                    ->fileAttachmentsVisibility('public'),
                            ])
                    )->all()),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                FileUpload::make('preview_image')
                    ->image()
                    ->disk(StorageDisk::S3)
                    ->directory(fn (?BlogPost $record) => $record ? "blog-post-images/{$record->id}/uploads" : 'blog-post-images/tmp')
                    ->visibility('public'),
                DateTimePicker::make('published_at')
                    ->helperText('Leave empty to keep this post as a draft.'),
            ]);
    }
}
