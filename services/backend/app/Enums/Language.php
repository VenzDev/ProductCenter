<?php

declare(strict_types=1);

namespace App\Enums;

enum Language: string
{
    case English = 'en';
    case Polish = 'pl';
    case German = 'de';
    case French = 'fr';
    case Italian = 'it';

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Polish => 'Polski',
            self::German => 'Deutsch',
            self::French => 'Français',
            self::Italian => 'Italiano',
        };
    }

    public function isFallback(): bool
    {
        return $this->value === config('app.fallback_locale');
    }
}
