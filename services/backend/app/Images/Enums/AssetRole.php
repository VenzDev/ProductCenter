<?php

declare(strict_types=1);

namespace App\Images\Enums;

enum AssetRole: string
{
    case MainImage = 'main_image';
    case PreviewImage = 'preview_image';
    case GalleryImage = 'gallery_image';
}
