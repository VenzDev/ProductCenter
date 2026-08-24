<?php

declare(strict_types=1);

namespace App\Enums;

enum AttributeType: string
{
    case Number = 'number';
    case Text = 'text';
    case Select = 'select';
    case MultiSelect = 'multiselect';

    public function label(): string
    {
        return match ($this) {
            self::Number => 'Number',
            self::Text => 'Text',
            self::Select => 'Select',
            self::MultiSelect => 'Multi-select',
        };
    }

    public function hasOptions(): bool
    {
        return $this === self::Select || $this === self::MultiSelect;
    }
}
