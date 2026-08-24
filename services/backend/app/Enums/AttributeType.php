<?php

namespace App\Enums;

enum AttributeType: string
{
    case Number = 'number';
    case Text = 'text';
    case Select = 'select';

    public function label(): string
    {
        return match ($this) {
            self::Number => 'Number',
            self::Text => 'Text',
            self::Select => 'Select',
        };
    }
}
