@extends('layouts.admin')

@section('title', 'Manajemen Kategori')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@endpush

@section('content')
<div x-data="categoryManager()" x-init="init()" class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-gray-900">Daftar Kategori</h2>
            <p class="mt-1 text-sm text-gray-500">Kelompokkan menu seperti makanan, minuman, dan paket agar mudah dicari pelanggan.</p>
        </div>
        <button @click="openModal()" class="inline-flex w-full items-center justify-center rounded-xl border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Kategori
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Kategori</p>
            <p class="mt-2 text-2xl font-bold text-gray-900" x-text="categories.length"></p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Menu</p>
            <p class="mt-2 text-2xl font-bold text-gray-900" x-text="totalMenus()"></p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Pengurutan</p>
            <p class="mt-2 text-sm font-medium text-gray-900">Geser baris kategori</p>
        </div>
    </div>

    <!-- Table Section -->
    <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <!-- Loading overlay -->
        <div x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>

        <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="w-12 px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Kategori</th>
                    <th scope="col" class="w-24 px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Menu</th>
                    <th scope="col" class="w-32 px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody id="categoryTableList" class="bg-white divide-y divide-gray-200">
                <template x-for="(cat, index) in categories" :key="cat.id">
                    <tr class="hover:bg-gray-50 transition-colors group cursor-grab active:cursor-grabbing" :data-id="cat.id">
                        <td class="px-6 py-4 whitespace-nowrap text-center text-gray-400 group-hover:text-gray-600 drag-handle">
                            <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                </span>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900" x-text="cat.name"></div>
                                    <div class="text-xs text-gray-500">Urutan tampilan pelanggan #<span x-text="index + 1"></span></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-medium rounded-full bg-blue-50 text-blue-700" x-text="(cat.menus_count || 0) + ' Item'"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button @click="openModal(cat)" class="text-indigo-600 hover:text-indigo-900 mr-3 p-1 rounded hover:bg-indigo-50 transition-colors" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <button @click="deleteCategory(cat.id)" class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors" title="Hapus">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="categories.length === 0 && !loading">
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        <svg class="mx-auto mb-3 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        <p class="text-base font-medium text-gray-900">Belum ada kategori</p>
                        <p class="mt-1 text-sm text-gray-500">Tambahkan kategori untuk mengelompokkan menu restoran.</p>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>

        <div class="divide-y divide-gray-100 md:hidden">
            <template x-for="cat in categories" :key="cat.id">
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                </span>
                                <h3 class="truncate text-sm font-semibold text-gray-900" x-text="cat.name"></h3>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700" x-text="(cat.menus_count || 0) + ' Item'"></span>
                                <span class="text-xs text-gray-500">Kategori menu</span>
                            </div>
                        </div>
                        <div class="flex flex-shrink-0 items-center gap-1">
                            <button @click="openModal(cat)" class="rounded-lg p-2 text-indigo-600 transition-colors hover:bg-indigo-50 hover:text-indigo-900" title="Edit" aria-label="Edit kategori">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <button @click="deleteCategory(cat.id)" class="rounded-lg p-2 text-red-600 transition-colors hover:bg-red-50 hover:text-red-900" title="Hapus" aria-label="Hapus kategori">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </article>
            </template>

            <div x-show="categories.length === 0 && !loading" class="px-4 py-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 text-gray-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
                <p class="text-sm font-semibold text-gray-900">Belum ada kategori</p>
                <p class="mt-1 text-sm text-gray-500">Tambahkan kategori pertama untuk mengelompokkan menu.</p>
            </div>
        </div>
        <x-admin-pagination mode="footer" label="kategori" />
    </div>

    <!-- Modal Form -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="isModalOpen" x-transition.scale.origin.bottom class="inline-block w-full transform overflow-hidden rounded-t-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:max-w-md sm:align-middle sm:rounded-2xl">
                <form @submit.prevent="saveCategory">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-4" x-text="form.id ? 'Edit Kategori' : 'Tambah Kategori'"></h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
                            <input type="text" x-model="form.name" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
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
function categoryManager() {
    return {
        categories: [],
        loading: false,
        saving: false,
        isModalOpen: false,
        sortableInstance: null,
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
        form: {
            id: null,
            name: ''
        },

        init() {
            this.fetchCategories();
        },

        initSortable() {
            if(this.sortableInstance) this.sortableInstance.destroy();
            
            const el = document.getElementById('categoryTableList');
            if(!el) return;

            this.sortableInstance = new Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'bg-gray-100',
                onEnd: (evt) => {
                    const itemEl = evt.item;
                    // Gather the new order of IDs
                    const order = [];
                    el.querySelectorAll('tr').forEach(tr => {
                        if(tr.dataset.id) order.push(parseInt(tr.dataset.id));
                    });
                    
                    this.reorderCategories(order);
                }
            });
        },

        totalMenus() {
            return this.categories.reduce((total, category) => total + Number(category.menus_count || 0), 0);
        },

        async fetchCategories(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                const res = await fetch('/api/admin/categories?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.categories = json.data.data || json.data; // Handle pagination or plain array
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };
                    
                    // Wait for DOM to render template then init Sortable
                    this.$nextTick(() => {
                        this.initSortable();
                    });
                }
            } catch (e) { console.error('Gagal mengambil kategori', e); }
            finally { this.loading = false; }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchCategories(page);
        },

        openModal(cat = null) {
            if (cat) {
                this.form = { id: cat.id, name: cat.name };
            } else {
                this.form = { id: null, name: '' };
            }
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        async saveCategory() {
            this.saving = true;
            try {
                let url = '/api/admin/categories';
                let method = 'POST';

                if (this.form.id) {
                    url = `/api/admin/categories/${this.form.id}`;
                    method = 'PUT';
                }

                const res = await fetch(url, {
                    method: method,
                    headers: { 
                        ...window.restoAuthHeaders(), 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name: this.form.name })
                });

                if(res.ok) {
                    this.closeModal();
                    this.fetchCategories(this.pagination.current_page);
                } else {
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menyimpan kategori', message: 'Cek kembali nama kategori.' });
                }
            } catch (e) {
                console.error('Error saving category', e);
            } finally {
                this.saving = false;
            }
        },

        async deleteCategory(id) {
            const confirmed = await window.restoConfirm({ variant: 'danger', title: 'Hapus kategori?', message: 'Semua menu di dalam kategori ini mungkin terdampak.', confirmText: 'Hapus' });
            if(!confirmed) return;
            try {
                const res = await fetch(`/api/admin/categories/${id}`, {
                    method: 'DELETE',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    this.fetchCategories(this.pagination.current_page);
                }
            } catch (e) {
                console.error('Error deleting category', e);
            }
        },

        async reorderCategories(orderedIds) {
            try {
                // Post to reorder endpoint (Task 4.5 mentions reorder feature)
                await fetch('/api/admin/categories/reorder', {
                    method: 'POST',
                    headers: { 
                        ...window.restoAuthHeaders(), 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        category: orderedIds.map((id, index) => ({
                            id,
                            sort_order: index + 1
                        }))
                    })
                });
            } catch (e) {
                console.error('Error reordering', e);
            }
        }
    }
}
</script>
@endpush
