<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * Names of the configured filesystem disks (config/filesystems.php).
 *
 * Kept as a class of constants rather than a backed enum so it drops straight
 * into APIs that expect a disk-name string — Laravel's Storage::disk() and
 * Filament's ->disk()/->fileAttachmentsDisk() — without a ->value everywhere.
 */
final class StorageDisk
{
    /** Product & blog files (images, attachments); S3 in the cloud, LocalStack locally. */
    public const string S3 = 's3';
}
