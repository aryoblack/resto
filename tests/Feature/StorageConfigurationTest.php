<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verifies that the storage configuration for menu image uploads is correct.
 *
 * Covers task 1.10: Konfigurasi storage untuk upload gambar (local/S3)
 */
class StorageConfigurationTest extends TestCase
{
    /**
     * The 'menu-images' disk must be defined in the filesystems config.
     */
    public function test_menu_images_disk_is_configured(): void
    {
        $disks = config('filesystems.disks');

        $this->assertArrayHasKey('menu-images', $disks, "The 'menu-images' disk must be defined in config/filesystems.php");
    }

    /**
     * The 'menu-images' disk must use the local driver.
     */
    public function test_menu_images_disk_uses_local_driver(): void
    {
        $disk = config('filesystems.disks.menu-images');

        $this->assertEquals('local', $disk['driver']);
    }

    /**
     * The 'menu-images' disk root must point to storage/app/public/menu-images.
     */
    public function test_menu_images_disk_root_is_correct(): void
    {
        $disk = config('filesystems.disks.menu-images');
        $expectedRoot = storage_path('app/public/menu-images');

        $this->assertEquals($expectedRoot, $disk['root']);
    }

    /**
     * The 'menu-images' disk must have public visibility.
     */
    public function test_menu_images_disk_has_public_visibility(): void
    {
        $disk = config('filesystems.disks.menu-images');

        $this->assertEquals('public', $disk['visibility']);
    }

    /**
     * The 'menu-images' disk URL should be host-relative so local ports do not
     * break previews in the admin UI.
     */
    public function test_menu_images_disk_url_is_correct(): void
    {
        $disk = config('filesystems.disks.menu-images');

        $this->assertEquals('/storage/menu-images', $disk['url']);
    }

    /**
     * The 's3-menu-images' disk must be defined for production use.
     */
    public function test_s3_menu_images_disk_is_configured(): void
    {
        $disks = config('filesystems.disks');

        $this->assertArrayHasKey('s3-menu-images', $disks, "The 's3-menu-images' disk must be defined for production use");
    }

    /**
     * The 's3-menu-images' disk must use the s3 driver.
     */
    public function test_s3_menu_images_disk_uses_s3_driver(): void
    {
        $disk = config('filesystems.disks.s3-menu-images');

        $this->assertEquals('s3', $disk['driver']);
    }

    /**
     * The 's3-menu-images' disk must have a 'menu-images' root prefix.
     */
    public function test_s3_menu_images_disk_has_correct_root_prefix(): void
    {
        $disk = config('filesystems.disks.s3-menu-images');

        $this->assertEquals('menu-images', $disk['root']);
    }

    /**
     * The symlink configuration must map public/storage to storage/app/public.
     */
    public function test_storage_symlink_is_configured(): void
    {
        $links = config('filesystems.links');

        $this->assertArrayHasKey(
            public_path('storage'),
            $links,
            'The public/storage symlink must be configured'
        );

        $this->assertEquals(
            storage_path('app/public'),
            $links[public_path('storage')]
        );
    }

    /**
     * The default filesystem disk must be 'local' in the development environment.
     */
    public function test_default_filesystem_disk_is_local_in_development(): void
    {
        // In the test environment, FILESYSTEM_DISK defaults to 'local'
        $this->assertEquals('local', config('filesystems.default'));
    }

    /**
     * The menu-images storage directory must exist.
     */
    public function test_menu_images_storage_directory_exists(): void
    {
        $path = storage_path('app/public/menu-images');

        $this->assertDirectoryExists($path, 'The storage/app/public/menu-images directory must exist');
    }

    /**
     * The Storage facade can interact with the menu-images disk.
     */
    public function test_can_write_and_read_from_menu_images_disk(): void
    {
        Storage::fake('menu-images');

        $testContent = 'test-image-content';
        $testFilename = 'test-image.jpg';

        Storage::disk('menu-images')->put($testFilename, $testContent);

        $this->assertTrue(Storage::disk('menu-images')->exists($testFilename));
        $this->assertEquals($testContent, Storage::disk('menu-images')->get($testFilename));

        // Clean up
        Storage::disk('menu-images')->delete($testFilename);
    }
}
