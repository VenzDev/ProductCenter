<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'microsoft_id'])]
#[Hidden(['microsoft_id'])]
class Admin extends Authenticatable implements FilamentUser
{
    // Rows are either manually provisioned or JIT-created on first login for the allowed
    // tenant domain (see MicrosoftAuthController::provisionAdmin) — no further per-admin
    // authorization exists yet, so access is unconditional.
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
