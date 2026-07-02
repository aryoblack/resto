<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for the Menu_Manager module.
 *
 * Covers: CRUD menus, image upload, soft delete, toggle availability,
 *         category management, variant management, and customer menu API.
 *
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.8, 3.9, 4.2, 4.4
 */
class MenuManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Create admin user
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        // Create customer user
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->assignRole('customer');

        // Create a test category
        $this->category = Category::create(['name' => 'Makanan', 'sort_order' => 1]);

        // Use fake storage for image tests
        Storage::fake('menu-images');
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function adminToken(): string
    {
        return $this->admin->createToken('test')->plainTextToken;
    }

    private function customerToken(): string
    {
        return $this->customer->createToken('test')->plainTextToken;
    }

    // -------------------------------------------------------------------------
    // 4.1 — Admin CRUD: create menu
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_create_menu(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/menus', [
                'name'        => 'Nasi Goreng',
                'category_id' => $this->category->id,
                'price'       => 25000,
                'stock'       => 50,
                'description' => 'Nasi goreng spesial',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Menu berhasil dibuat.'])
            ->assertJsonPath('data.name', 'Nasi Goreng')
            ->assertJsonPath('data.price', '25000.00');

        $this->assertDatabaseHas('menu', [
            'name'        => 'Nasi Goreng',
            'category_id' => $this->category->id,
        ]);
    }

    #[Test]
    public function test_menu_name_is_saved_with_capital_first_letter_on_create(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/menus', [
                'name'        => '  nasi goreng',
                'category_id' => $this->category->id,
                'price'       => 25000,
                'stock'       => 50,
                'description' => 'Nasi goreng spesial',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Nasi goreng');

        $this->assertDatabaseHas('menu', [
            'name'        => 'Nasi goreng',
            'category_id' => $this->category->id,
        ]);
    }

    #[Test]
    public function test_menu_creation_fails_without_required_fields(): void
    {
        // Missing name, category_id, price, stock
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/menus', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id', 'price', 'stock']);
    }
    #[Test]
    public function test_menu_creation_fails_with_invalid_category(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/menus', [
                'name'        => 'Test Menu',
                'category_id' => 9999, // non-existent
                'price'       => 10000,
                'stock'       => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    // -------------------------------------------------------------------------
    // 4.2 — Image upload
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_create_menu_with_image(): void
    {
        $image = UploadedFile::fake()->image('menu.jpg', 400, 300);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/menus', [
                'name'        => 'Ayam Bakar',
                'category_id' => $this->category->id,
                'price'       => 30000,
                'stock'       => 20,
                'image'       => $image,
            ]);

        $response->assertStatus(201);

        // image_url should be stored in the database (filename, not full URL)
        $menu = Menu::where('name', 'Ayam Bakar')->first();
        $this->assertNotNull($menu->image_url);
    }
    #[Test]
    public function test_image_upload_rejects_invalid_mime_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/menus', [
                'name'        => 'Test Menu',
                'category_id' => $this->category->id,
                'price'       => 10000,
                'stock'       => 10,
                'image'       => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    // -------------------------------------------------------------------------
    // 4.1 — Admin CRUD: show & update
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_view_single_menu(): void
    {
        $menu = Menu::create([
            'name'        => 'Soto Ayam',
            'category_id' => $this->category->id,
            'price'       => 20000,
            'stock'       => 30,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->getJson("/api/admin/menus/{$menu->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Soto Ayam');
    }
    #[Test]
    public function test_admin_can_update_menu(): void
    {
        $menu = Menu::create([
            'name'        => 'Mie Goreng',
            'category_id' => $this->category->id,
            'price'       => 18000,
            'stock'       => 40,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->putJson("/api/admin/menus/{$menu->id}", [
                'name'  => 'Mie Goreng Spesial',
                'price' => 22000,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Menu berhasil diperbarui.'])
            ->assertJsonPath('data.name', 'Mie Goreng Spesial');

        $this->assertDatabaseHas('menu', [
            'id'    => $menu->id,
            'name'  => 'Mie Goreng Spesial',
            'price' => 22000,
        ]);
    }

    #[Test]
    public function test_menu_name_is_saved_with_capital_first_letter_on_update(): void
    {
        $menu = Menu::create([
            'name'        => 'Mie Goreng',
            'category_id' => $this->category->id,
            'price'       => 18000,
            'stock'       => 40,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->putJson("/api/admin/menus/{$menu->id}", [
                'name' => '  mie ayam',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Mie ayam');

        $this->assertDatabaseHas('menu', [
            'id'   => $menu->id,
            'name' => 'Mie ayam',
        ]);
    }

    #[Test]
    public function test_admin_can_update_menu_image(): void
    {
        $menu = Menu::create([
            'name'        => 'Bakso',
            'category_id' => $this->category->id,
            'price'       => 15000,
            'stock'       => 100,
            'image_url'   => 'old-image.jpg',
        ]);

        // Put old image in fake storage so deletion works
        Storage::disk('menu-images')->put('old-image.jpg', 'fake content');

        $newImage = UploadedFile::fake()->image('new-menu.jpg', 400, 300);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->putJson("/api/admin/menus/{$menu->id}", [
                'image' => $newImage,
            ]);

        $response->assertStatus(200);

        // Old image should be deleted
        Storage::disk('menu-images')->assertMissing('old-image.jpg');

        // New image_url should differ from old
        $menu->refresh();
        $this->assertNotEquals('old-image.jpg', $menu->image_url);
    }

    // -------------------------------------------------------------------------
    // 4.3 — Soft delete
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_soft_delete_menu(): void
    {
        $menu = Menu::create([
            'name'        => 'Gado-Gado',
            'category_id' => $this->category->id,
            'price'       => 17000,
            'stock'       => 25,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->deleteJson("/api/admin/menus/{$menu->id}", ['confirm' => true]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Menu berhasil dihapus.']);

        // Record still exists in DB (soft delete)
        $this->assertSoftDeleted('menu', ['id' => $menu->id]);
    }
    #[Test]
    public function test_destroy_without_confirm_flag_is_rejected(): void
    {
        $menu = Menu::create([
            'name'        => 'Pecel',
            'category_id' => $this->category->id,
            'price'       => 15000,
            'stock'       => 20,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->deleteJson("/api/admin/menus/{$menu->id}");

        $response->assertStatus(422);

        // Menu should NOT be deleted
        $this->assertDatabaseHas('menu', ['id' => $menu->id, 'deleted_at' => null]);
    }

    // -------------------------------------------------------------------------
    // 4.4 — Toggle availability
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_toggle_menu_availability(): void
    {
        $menu = Menu::create([
            'name'         => 'Es Teh',
            'category_id'  => $this->category->id,
            'price'        => 5000,
            'stock'        => 100,
            'is_available' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->patchJson("/api/admin/menus/{$menu->id}/toggle-availability");

        $response->assertStatus(200)
            ->assertJsonFragment(['is_available' => false]);

        $this->assertDatabaseHas('menu', ['id' => $menu->id, 'is_available' => false]);

        // Toggle back
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->patchJson("/api/admin/menus/{$menu->id}/toggle-availability")
            ->assertJsonFragment(['is_available' => true]);
    }

    // -------------------------------------------------------------------------
    // 4.7 — Customer menu API
    // -------------------------------------------------------------------------
    #[Test]
    public function test_customer_can_get_active_menus(): void
    {
        Menu::create([
            'name'         => 'Rendang',
            'category_id'  => $this->category->id,
            'price'        => 35000,
            'stock'        => 10,
            'is_available' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->customerToken())
            ->getJson('/api/customer/menus');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
    }
    #[Test]
    public function test_inactive_menu_not_shown_as_available_to_customer(): void
    {
        // Active menu
        Menu::create([
            'name'         => 'Sate Ayam',
            'category_id'  => $this->category->id,
            'price'        => 25000,
            'stock'        => 15,
            'is_available' => true,
        ]);

        // Inactive menu
        Menu::create([
            'name'         => 'Sate Kambing',
            'category_id'  => $this->category->id,
            'price'        => 30000,
            'stock'        => 5,
            'is_available' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->customerToken())
            ->getJson('/api/customer/menus');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data); // Both returned (frontend shows "Habis" label)

        // The inactive one should have is_available = false
        $inactive = collect($data)->firstWhere('name', 'Sate Kambing');
        $this->assertFalse($inactive['is_available']);
    }
    #[Test]
    public function test_soft_deleted_menu_not_shown_to_customer(): void
    {
        $menu = Menu::create([
            'name'         => 'Opor Ayam',
            'category_id'  => $this->category->id,
            'price'        => 28000,
            'stock'        => 10,
            'is_available' => true,
        ]);

        $menu->delete(); // soft delete

        $response = $this->withHeader('Authorization', 'Bearer '.$this->customerToken())
            ->getJson('/api/customer/menus');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }
    #[Test]
    public function test_menu_filter_by_category(): void
    {
        $otherCategory = Category::create(['name' => 'Minuman', 'sort_order' => 2]);

        Menu::create([
            'name'         => 'Nasi Uduk',
            'category_id'  => $this->category->id,
            'price'        => 20000,
            'stock'        => 10,
            'is_available' => true,
        ]);

        Menu::create([
            'name'         => 'Es Jeruk',
            'category_id'  => $otherCategory->id,
            'price'        => 8000,
            'stock'        => 50,
            'is_available' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->customerToken())
            ->getJson("/api/customer/menus?category_id={$this->category->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Nasi Uduk', $data[0]['name']);
    }
    #[Test]
    public function test_menu_search_by_name(): void
    {
        Menu::create([
            'name'         => 'Nasi Goreng Spesial',
            'category_id'  => $this->category->id,
            'price'        => 25000,
            'stock'        => 10,
            'is_available' => true,
        ]);

        Menu::create([
            'name'         => 'Mie Goreng',
            'category_id'  => $this->category->id,
            'price'        => 20000,
            'stock'        => 10,
            'is_available' => true,
        ]);

        Menu::create([
            'name'         => 'Soto Betawi',
            'category_id'  => $this->category->id,
            'price'        => 22000,
            'stock'        => 10,
            'is_available' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->customerToken())
            ->getJson('/api/customer/menus?search=goreng');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    // -------------------------------------------------------------------------
    // 4.5 — Category management
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_list_categories_ordered_by_sort_order(): void
    {
        Category::create(['name' => 'Dessert', 'sort_order' => 3]);
        Category::create(['name' => 'Minuman', 'sort_order' => 2]);
        // $this->category has sort_order = 1

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->getJson('/api/admin/categories');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should be ordered: Makanan(1), Minuman(2), Dessert(3)
        $this->assertEquals('Makanan', $data[0]['name']);
        $this->assertEquals('Minuman', $data[1]['name']);
        $this->assertEquals('Dessert', $data[2]['name']);
    }

    #[Test]
    public function test_category_name_is_saved_with_capital_first_letter_on_create_and_update(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/categories', [
                'name' => '  makanan ringan',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Makanan ringan');

        $categoryId = $response->json('data.id');

        $updateResponse = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->putJson("/api/admin/categories/{$categoryId}", [
                'name' => '  minuman dingin',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Minuman dingin');

        $this->assertDatabaseHas('category', [
            'id'   => $categoryId,
            'name' => 'Minuman dingin',
        ]);
    }

    #[Test]
    public function test_admin_can_reorder_categories(): void
    {
        $cat2 = Category::create(['name' => 'Minuman', 'sort_order' => 2]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/categories/reorder', [
                'category' => [
                    ['id' => $this->category->id, 'sort_order' => 10],
                    ['id' => $cat2->id, 'sort_order' => 5],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('category', ['id' => $this->category->id, 'sort_order' => 10]);
        $this->assertDatabaseHas('category', ['id' => $cat2->id, 'sort_order' => 5]);
    }

    // -------------------------------------------------------------------------
    // 4.6 — Variant management
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_create_variant_for_menu(): void
    {
        $menu = Menu::create([
            'name'        => 'Kopi Susu',
            'category_id' => $this->category->id,
            'price'       => 15000,
            'stock'       => 50,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson("/api/admin/menus/{$menu->id}/variants", [
                'variant_name' => 'Extra Shot',
                'extra_price'  => 5000,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['variant_name' => 'Extra Shot']);

        $this->assertDatabaseHas('variant', [
            'menu_id'      => $menu->id,
            'variant_name' => 'Extra Shot',
            'extra_price'  => 5000,
        ]);
    }
    #[Test]
    public function test_admin_can_update_variant(): void
    {
        $menu = Menu::create([
            'name'        => 'Teh Tarik',
            'category_id' => $this->category->id,
            'price'       => 12000,
            'stock'       => 30,
        ]);

        $variant = Variant::create([
            'menu_id'      => $menu->id,
            'variant_name' => 'Manis',
            'extra_price'  => 0,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->putJson("/api/admin/variants/{$variant->id}", [
                'variant_name' => 'Kurang Manis',
                'extra_price'  => 1000,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.variant_name', 'Kurang Manis');
    }
    #[Test]
    public function test_admin_can_delete_variant(): void
    {
        $menu = Menu::create([
            'name'        => 'Jus Alpukat',
            'category_id' => $this->category->id,
            'price'       => 18000,
            'stock'       => 20,
        ]);

        $variant = Variant::create([
            'menu_id'      => $menu->id,
            'variant_name' => 'Tanpa Susu',
            'extra_price'  => 0,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->deleteJson("/api/admin/variants/{$variant->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('variant', ['id' => $variant->id]);
    }

    // -------------------------------------------------------------------------
    // Authorization checks
    // -------------------------------------------------------------------------
    #[Test]
    public function test_customer_cannot_access_admin_menu_routes(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->customerToken())
            ->postJson('/api/admin/menus', [
                'name'        => 'Unauthorized',
                'category_id' => $this->category->id,
                'price'       => 10000,
                'stock'       => 5,
            ]);

        $response->assertStatus(403);
    }
    #[Test]
    public function test_admin_cannot_access_customer_menu_route(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->getJson('/api/customer/menus');

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 4.8 — Cache invalidation
    // -------------------------------------------------------------------------
    #[Test]
    public function test_cache_is_invalidated_after_menu_creation(): void
    {
        // Prime the cache
        $this->withHeader('Authorization', 'Bearer '.$this->customerToken())
            ->getJson('/api/customer/menus');

        // Create a new menu as admin
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/admin/menus', [
                'name'        => 'Pempek',
                'category_id' => $this->category->id,
                'price'       => 20000,
                'stock'       => 30,
            ]);

        // Customer should see the new menu (cache was invalidated)
        $response = $this->withHeader('Authorization', 'Bearer '.$this->customerToken())
            ->getJson('/api/customer/menus');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Pempek', $names);
    }
}
