@extends('layouts.admin')

@section('title', 'Manajemen Menu')

@section('content')
<div x-data="menuManager()" x-init="init()" class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative flex-1 sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" x-model="search" @input.debounce.500ms="fetchMenus()" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm transition-shadow" placeholder="Cari menu...">
            </div>
            <select x-model="categoryFilter" @change="fetchMenus()" class="block w-full sm:w-auto pl-3 pr-10 py-2 text-base border-gray-200 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-xl">
                <option value="">Semua Kategori</option>
                <template x-for="cat in categories" :key="cat.id">
                    <option :value="cat.id" x-text="cat.name"></option>
                </template>
            </select>
        </div>
        <button @click="openModal()" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors w-full sm:w-auto">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Menu
        </button>
    </div>

    <!-- Table Section -->
    <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Menu</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Harga</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stok</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" :class="{'opacity-50 pointer-events-none': loading}">
                    <template x-for="menu in menus" :key="menu.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12 rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                                        <img :src="menu.image_url || 'https://ui-avatars.com/api/?name=' + menu.name + '&background=F3F4F6&color=9CA3AF'" alt="" class="h-12 w-12 object-cover">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900" x-text="menu.name"></div>
                                        <div class="text-xs text-gray-500 w-48 truncate" x-text="menu.description"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-medium rounded-full bg-gray-100 text-gray-800" x-text="menu.category?.name || '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium" x-text="formatCurrency(menu.price)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm" :class="menu.stock <= 0 ? 'text-red-600 font-bold' : 'text-gray-900'" x-text="menu.stock"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button @click="toggleStatus(menu)" class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" :class="menu.is_available ? 'bg-primary-600' : 'bg-gray-200'">
                                    <span class="sr-only">Toggle status</span>
                                    <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200" :class="menu.is_available ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openModal(menu)" class="text-indigo-600 hover:text-indigo-900 mr-3 p-1 rounded hover:bg-indigo-50 transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button @click="deleteMenu(menu.id)" class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors" title="Hapus">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="menus.length === 0 && !loading">
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                            <p class="text-base font-medium text-gray-900">Belum ada menu</p>
                            <p class="text-sm mt-1">Tambahkan menu baru untuk mulai berjualan.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <x-admin-pagination
            mode="footer"
            label="hasil"
            from="pagination.from || 0"
            to="pagination.to || 0"
            prev-disabled="pagination.current_page === 1"
            next-disabled="pagination.current_page === pagination.last_page"
        />
    </div>

    <!-- Modal Form -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="isModalOpen" x-transition.scale.origin.bottom class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form @submit.prevent="saveMenu">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-4" x-text="form.id ? 'Edit Menu' : 'Tambah Menu Baru'"></h3>
                        
                        <div class="space-y-4">
                            <!-- Image Preview & Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Foto Menu</label>
                                <div class="mt-1 flex items-center gap-4">
                                    <div class="h-16 w-16 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden flex-shrink-0">
                                        <template x-if="imagePreview">
                                            <img :src="imagePreview" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!imagePreview">
                                            <svg class="h-full w-full text-gray-300 p-2" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </template>
                                    </div>
                                    <input type="file" x-ref="fileInput" @change="handleImageUpload" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition-colors">
                                </div>
                                <div class="mt-2 rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                    <template x-if="form.image">
                                        <span>File baru: <span class="font-semibold text-gray-700" x-text="form.image.name"></span></span>
                                    </template>
                                    <template x-if="!form.image && form.id && currentImageName">
                                        <span>Gambar saat ini: <span class="font-semibold text-gray-700" x-text="currentImageName"></span>. Pilih file baru hanya jika ingin mengganti foto.</span>
                                    </template>
                                    <template x-if="!form.image && (!form.id || !currentImageName)">
                                        <span>Belum ada foto menu. Pilih file JPG, PNG, atau WEBP.</span>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Menu</label>
                                <input type="text" x-model="form.name" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                <select x-model="form.category_id" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                                    <option value="">Pilih Kategori</option>
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp)</label>
                                    <input type="number" x-model="form.price" required min="0" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
                                    <input type="number" x-model="form.stock" required min="0" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                <textarea x-model="form.description" rows="3" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border"></textarea>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900">Pilihan Varian / Porsi</label>
                                        <p class="text-xs text-gray-500 mt-0.5">Contoh: Regular, Jumbo, Setengah Porsi.</p>
                                    </div>
                                    <button type="button" @click="addVariant()" class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-primary-700 border border-primary-100 hover:bg-primary-50">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"></path></svg>
                                        Tambah
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <template x-if="form.variants.length === 0">
                                        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-3 text-sm text-gray-500">
                                            Belum ada varian. Menu tetap bisa dipesan dengan harga normal.
                                        </div>
                                    </template>
                                    <template x-for="(variant, index) in form.variants" :key="variant.uid">
                                        <div class="grid grid-cols-[minmax(0,1fr)_9rem_2.5rem] gap-2 items-center">
                                            <input type="text" x-model="variant.variant_name" placeholder="Nama varian" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border bg-white">
                                            <input type="number" x-model="variant.extra_price" min="0" placeholder="Extra" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border bg-white">
                                            <button type="button" @click="removeVariant(index)" class="h-10 w-10 inline-flex items-center justify-center rounded-xl text-red-600 bg-white border border-red-100 hover:bg-red-50" title="Hapus varian">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl border-t border-gray-100">
                        <button type="submit" :disabled="saving" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors disabled:opacity-50">
                            <span x-show="!saving">Simpan</span>
                            <span x-show="saving">Menyimpan...</span>
                        </button>
                        <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function menuManager() {
    return {
        menus: [],
        categories: [],
        loading: false,
        saving: false,
        search: '',
        categoryFilter: '',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
            from: 0,
            to: 0
        },
        isModalOpen: false,
        imagePreview: null,
        currentImageName: '',
        form: {
            id: null,
            name: '',
            category_id: '',
            price: '',
            stock: '',
            description: '',
            image: null,
            variants: [],
            deletedVariantIds: []
        },

        init() {
            this.fetchCategories();
            this.fetchMenus();
        },

        async fetchCategories() {
            try {
                const res = await fetch('/api/admin/categories?per_page=1000', {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.categories = json.data;
                }
            } catch (e) { console.error('Gagal mengambil kategori', e); }
        },

        async fetchMenus(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page });
                params.append('per_page', this.pagination.per_page);
                if (this.search) params.append('search', this.search);
                if (this.categoryFilter) params.append('category_id', this.categoryFilter);

                const res = await fetch('/api/admin/menus?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    const paginator = json.meta ? { ...json.meta, data: json.data } : json;
                    this.menus = paginator.data || [];
                    if(paginator.current_page) {
                        this.pagination = {
                            ...this.pagination,
                            current_page: paginator.current_page,
                            last_page: paginator.last_page,
                            total: paginator.total,
                            from: paginator.from,
                            to: paginator.to
                        };
                    }
                }
            } catch (e) {
                console.error('Gagal mengambil menu', e);
            } finally {
                this.loading = false;
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.fetchMenus(page);
            }
        },

        openModal(menu = null) {
            if (menu) {
                this.form = {
                    id: menu.id,
                    name: menu.name,
                    category_id: menu.category_id,
                    price: menu.price,
                    stock: menu.stock,
                    description: menu.description || '',
                    image: null,
                    variants: this.normalizeVariants(menu.variants || []),
                    deletedVariantIds: []
                };
                this.imagePreview = menu.image_url;
                this.currentImageName = this.extractImageName(menu.image_url);
            } else {
                this.form = {
                    id: null,
                    name: '',
                    category_id: '',
                    price: '',
                    stock: '',
                    description: '',
                    image: null,
                    variants: [],
                    deletedVariantIds: []
                };
                this.imagePreview = null;
                this.currentImageName = '';
            }
            if(this.$refs.fileInput) this.$refs.fileInput.value = '';
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        handleImageUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.form.image = file;
            this.currentImageName = file.name;
            
            const reader = new FileReader();
            reader.onload = (e) => { this.imagePreview = e.target.result; };
            reader.readAsDataURL(file);
        },

        extractImageName(url) {
            if (!url) return '';
            try {
                const pathname = new URL(url, window.location.origin).pathname;
                return decodeURIComponent(pathname.split('/').filter(Boolean).pop() || '');
            } catch (e) {
                return String(url).split('/').filter(Boolean).pop() || '';
            }
        },

        normalizeVariants(variants) {
            return variants.map((variant) => ({
                id: variant.id || null,
                uid: variant.id ? `existing-${variant.id}` : this.newUid(),
                variant_name: variant.variant_name || '',
                extra_price: Number(variant.extra_price || 0)
            }));
        },

        addVariant() {
            this.form.variants.push({
                id: null,
                uid: this.newUid(),
                variant_name: '',
                extra_price: 0
            });
        },

        newUid() {
            return `new-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        },

        removeVariant(index) {
            const [variant] = this.form.variants.splice(index, 1);
            if (variant?.id) {
                this.form.deletedVariantIds.push(variant.id);
            }
        },

        async saveMenu() {
            this.saving = true;
            try {
                const formData = new FormData();
                formData.append('name', this.form.name);
                formData.append('category_id', this.form.category_id);
                formData.append('price', this.form.price);
                formData.append('stock', this.form.stock);
                if (this.form.description) formData.append('description', this.form.description);
                if (this.form.image) formData.append('image', this.form.image);

                let url = '/api/admin/menus';
                let method = 'POST';

                if (this.form.id) {
                    url = `/api/admin/menus/${this.form.id}`;
                    formData.append('_method', 'PUT'); // For Laravel form data PUT
                }

                const res = await fetch(url, {
                    method: 'POST', // Using POST with _method for FormData support in Laravel
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' }),
                    body: formData
                });

                if(res.ok) {
                    const json = await res.json();
                    await this.syncVariants(json.data.id);
                    this.closeModal();
                    this.fetchMenus(this.pagination.current_page);
                    await window.restoAlert({ variant: 'success', title: 'Menu berhasil disimpan', message: 'Data menu sudah diperbarui.' });
                } else {
                    const error = await res.json();
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menyimpan menu', message: error.message || 'Cek input Anda.' });
                }
            } catch (e) {
                console.error('Error saving menu', e);
                await window.restoAlert({ variant: 'danger', title: 'Gagal menyimpan menu', message: e.message || 'Cek kembali data menu dan varian.' });
            } finally {
                this.saving = false;
            }
        },

        async syncVariants(menuId) {
            for (const variantId of this.form.deletedVariantIds) {
                await fetch(`/api/admin/variants/${variantId}`, {
                    method: 'DELETE',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
            }

            for (const variant of this.form.variants) {
                const name = String(variant.variant_name || '').trim();
                if (!name) continue;

                const payload = {
                    variant_name: name,
                    extra_price: Number(variant.extra_price || 0)
                };

                const url = variant.id ? `/api/admin/variants/${variant.id}` : `/api/admin/menus/${menuId}/variants`;
                const method = variant.id ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: window.restoAuthHeaders({
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }),
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    const error = await res.json().catch(() => ({}));
                    throw new Error(error.message || 'Gagal menyimpan varian menu.');
                }
            }
        },

        async toggleStatus(menu) {
            try {
                const res = await fetch(`/api/admin/menus/${menu.id}/toggle-availability`, {
                    method: 'PATCH',
                    headers: { 
                        ...window.restoAuthHeaders(), 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                if(res.ok) {
                    menu.is_available = !menu.is_available;
                }
            } catch (e) {
                console.error('Error toggling status', e);
            }
        },

        async deleteMenu(id) {
            const confirmed = await window.restoConfirm({ variant: 'danger', title: 'Hapus menu?', message: 'Menu akan dihapus dari daftar aktif.', confirmText: 'Hapus' });
            if(!confirmed) return;
            try {
                const res = await fetch(`/api/admin/menus/${id}`, {
                    method: 'DELETE',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ confirm: true })
                });
                if(res.ok) {
                    this.fetchMenus(this.pagination.current_page);
                }
            } catch (e) {
                console.error('Error deleting menu', e);
            }
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
        }
    }
}
</script>
@endpush
