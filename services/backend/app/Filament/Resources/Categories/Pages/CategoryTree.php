<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Enums\Language;
use App\Models\Category;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use SolutionForest\FilamentTree\Pages\TreePage;

class CategoryTree extends TreePage
{
    protected static string $model = Category::class;

    protected static ?string $title = 'Categories';

    protected static ?string $slug = 'categories';

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static int $maxDepth = 2;

    // Vertical dragging only reorders within the current level. To nest a category
    // under another, drag it to just below the target, then — still holding — move
    // the mouse right past a small threshold; that's what triggers nesting.
    protected ?string $subheading = 'Drag a category, then move it right while dropping to nest it under another.';

    /**
     * @return array<int, mixed>
     */
    protected function getTreeToolbarActions(): array
    {
        return [];
    }

    protected function getActions(): array
    {
        return [
            $this->getCreateAction(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    protected function getFormSchema(): array
    {
        return [
            Tabs::make('Translations')
                ->columnSpanFull()
                ->tabs(collect(Language::cases())->map(
                    fn (Language $language) => Tab::make($language->label())
                        ->schema([
                            TextInput::make("name.{$language->value}")
                                ->label('Name')
                                ->required($language->isFallback()),
                        ])
                )->all()),
            TextInput::make('slug')
                ->required(),
        ];
    }

    protected function hasDeleteAction(): bool
    {
        return true;
    }

    protected function hasEditAction(): bool
    {
        return true;
    }

    protected function hasViewAction(): bool
    {
        return false;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    /**
     * @return array<int, string|Htmlable>
     */
    public function getBreadcrumbs(): array
    {
        return [static::getTitle()];
    }
}
