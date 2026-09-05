<?php

declare(strict_types=1);

namespace App\Models;

use App\Images\Enums\AssetRole;
use App\Images\Observers\AssetObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $owner_id
 * @property AssetRole $role
 */
#[Fillable(['owner_type', 'owner_id', 'role', 'path', 'order'])]
#[ObservedBy(AssetObserver::class)]
class Asset extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => AssetRole::class,
        ];
    }
}
