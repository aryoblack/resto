<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class ImageStorageService
{
    /**
     * The disk used for menu image storage.
     * Reads from MENU_IMAGE_DISK env variable, defaulting to 'menu-images'.
     */
    protected string $disk;

    /**
     * Maximum allowed file size in bytes (2 MB).
     */
    public const MAX_SIZE_BYTES = 2 * 1024 * 1024;

    /**
     * Allowed MIME types for menu images.
     */
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Allowed file extensions for menu images.
     */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Target width for optimized images (pixels).
     */
    protected const TARGET_WIDTH = 800;

    /**
     * JPEG/WebP quality for optimized images (1–100).
     */
    protected const IMAGE_QUALITY = 85;

    public function __construct()
    {
        $this->disk = config('filesystems.default') === 's3'
            ? env('MENU_IMAGE_DISK', 's3-menu-images')
            : env('MENU_IMAGE_DISK', 'menu-images');
    }

    /**
     * Store and optimize a menu image upload.
     *
     * The image is resized to a maximum width of 800px (maintaining aspect
     * ratio) and re-encoded at 85% quality before being saved to the
     * configured disk. A unique filename is generated automatically.
     *
     * @param  UploadedFile  $file  The validated uploaded file.
     * @param  string|null   $oldPath  Previous image path to delete (optional).
     * @return string  The stored file path relative to the disk root.
     */
    public function storeMenuImage(UploadedFile $file, ?string $oldPath = null): string
    {
        // Delete the old image if one exists
        if ($oldPath) {
            $this->deleteMenuImage($oldPath);
        }

        // Generate a unique filename preserving the original extension
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid().'.'.$extension;

        // Optimize the image with Intervention Image
        $manager = ImageManager::gd();
        $image = $manager->read($file->getRealPath());

        // Resize to max 800px wide, maintaining aspect ratio (never upscale)
        $image->scaleDown(width: self::TARGET_WIDTH);

        // Encode at reduced quality
        $encoded = match ($extension) {
            'png'  => $image->toPng(),
            'webp' => $image->toWebp(quality: self::IMAGE_QUALITY),
            default => $image->toJpeg(quality: self::IMAGE_QUALITY),
        };

        // Store the optimized image on the configured disk
        Storage::disk($this->disk)->put($filename, (string) $encoded);

        return $filename;
    }

    /**
     * Delete a menu image from storage.
     *
     * @param  string  $path  The file path relative to the disk root.
     * @return bool  True if deleted, false if the file did not exist.
     */
    public function deleteMenuImage(string $path): bool
    {
        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }

        return false;
    }

    /**
     * Generate a publicly accessible URL for a menu image.
     *
     * Works transparently with both local and S3 disks:
     * - Local: returns {APP_URL}/storage/menu-images/{filename}
     * - S3:    returns the S3 object URL (or CDN URL if AWS_URL is set)
     *
     * @param  string|null  $path  The file path relative to the disk root.
     * @return string|null  The full public URL, or null if path is empty.
     */
    public function getMenuImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Check whether a menu image file exists on the configured disk.
     *
     * @param  string  $path  The file path relative to the disk root.
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Return the name of the active storage disk for menu images.
     */
    public function getDisk(): string
    {
        return $this->disk;
    }
}
