<?php

declare(strict_types=1);

namespace App\Images\Support;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Relations\MorphOne;

// Upserts a model's single-image Asset (e.g. Product::mainImage(), BlogPost::previewImage())
// from a Filament form's submitted staged path. Used by Create/Edit page overrides for models
// whose FileUpload field isn't bound directly to a real column anymore. The relation's own
// withAttributes(['role' => ...]) fills in the role on a newly made Asset, so this only
// needs to know about the path.
class AssetFormSync
{
    /**
     * @param  MorphOne<Asset, *>  $relation
     */
    public static function syncSingleImage(MorphOne $relation, ?string $path): void
    {
        if ($path === null) {
            return;
        }

        $asset = $relation->first() ?? $relation->make();
        $asset->path = $path;
        $asset->save();
    }
}
