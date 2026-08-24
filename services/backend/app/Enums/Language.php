<?php

declare(strict_types=1);

namespace App\Enums;

enum Language: string
{
    case English = 'en';
    case Polish = 'pl';

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Polish => 'Polski',
        };
    }

    public function isFallback(): bool
    {
        return $this->value === config('app.fallback_locale');
    }
}
