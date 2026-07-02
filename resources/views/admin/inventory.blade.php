@extends('layouts.admin')

@section('title', 'Manajemen Stok')

@section('content')
<div x-data="inventoryManager()" x-init="init()" class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="w-full sm:w-auto relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" x-model="search" @input.debounce.300ms="fetchInventory()" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="Cari bahan baku...">
        </div>
        <button @click="openModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-primary-600 hover:bg-primary-700 w-full sm:w-auto justify-center transition-colors">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Bahan Baru
        </button>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden relative">
        <div x-show="loading" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bahan Baku</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Satuan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stok Saat Ini</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="item in inventory" :key="item.id">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900" x-text="item.ingredient_name"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500" x-text="item.unit"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                  :class="item.current_stock <= item.min_stock ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
                                <span x-text="item.current_stock"></span>
                                <span x-show="item.current_stock <= item.min_stock" class="ml-1">(Kritis!)</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="item.supplier || '-'"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <button @click="openAddStockModal(item)" class="text-green-600 hover:text-green-900 p-1 bg-green-50 rounded" title="Tambah Stok Masuk">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                            <button @click="openHistoryModal(item)" class="text-blue-600 hover:text-blue-900 p-1 bg-blue-50 rounded" title="Riwayat Stok">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                            <button @click="openModal(item)" class="text-indigo-600 hover:text-indigo-900 p-1 bg-indigo-50 rounded" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 p-1 bg-red-50 rounded" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="inventory.length === 0 && !loading">
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">Bahan baku tidak ditemukan.</td>
                </tr>
            </tbody>
        </table>
        <x-admin-pagination mode="footer" label="bahan" />
    </div>

    <!-- Modal Form Tambah/Edit Bahan -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form @submit.prevent="saveItem">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 space-y-4">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-4" x-text="form.id ? 'Edit Bahan Baku' : 'Tambah Bahan Baku'"></h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bahan</label>
                            <input type="text" x-model="form.ingredient_name" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                                <input type="text" x-model="form.unit" required placeholder="kg, gram, pcs..." class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stok Minimum</label>
                                <input type="number" step="0.01" x-model="form.min_stock" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                            </div>
                        </div>
                        <div x-show="!form.id">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok Awal</label>
                            <input type="number" step="0.01" x-model="form.current_stock" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                            <select x-model="form.supplier_id" @change="syncSupplierName()" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                                <option value="">Tanpa supplier</option>
                                <template x-for="supplier in suppliers" :key="supplier.id">
                                    <option :value="supplier.id" x-text="supplier.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Terakhir</label>
                                <input type="number" step="0.01" min="0" x-model="form.last_price" :disabled="!form.supplier_id" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border disabled:bg-gray-100 disabled:text-gray-400">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Lead Time (hari)</label>
                                <input type="number" min="0" x-model="form.lead_time_days" :disabled="!form.supplier_id" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border disabled:bg-gray-100 disabled:text-gray-400">
                            </div>
                        </div>
                        <p x-show="!form.supplier_id" class="text-xs text-gray-500">Pilih supplier dulu untuk menyimpan harga terakhir dan lead time.</p>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button type="submit" :disabled="saving" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                        <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Stok (In) -->
    <div x-show="isAddStockModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="isAddStockModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
                <form @submit.prevent="addStock">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 space-y-4">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-2">Tambah Stok Masuk</h3>
                        <p class="text-sm text-gray-500 mb-4" x-text="activeItem ? `Bahan: ${activeItem.ingredient_name} (${activeItem.unit})` : ''"></p>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Ditambahkan</label>
                            <input type="number" step="0.01" min="0.01" x-model="addStockForm.quantity" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea x-model="addStockForm.note" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button type="submit" :disabled="saving" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">Tambah</button>
                        <button type="button" @click="isAddStockModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Riwayat Stok -->
    <div x-show="isHistoryModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeHistoryModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-primary-600">Riwayat Pergerakan Stok</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900" x-text="historyItem ? historyItem.ingredient_name : 'Riwayat stok'"></h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Stok saat ini:
                                <span class="font-semibold text-gray-800" x-text="historyItem ? `${historyItem.current_stock} ${historyItem.unit}` : '-'"></span>
                            </p>
                        </div>
                        <button type="button" @click="closeHistoryModal()" class="rounded-xl bg-gray-100 p-2 text-gray-500 transition hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_auto]">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Dari</label>
                            <input type="date" x-model="historyFilters.from" class="mt-1 block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Sampai</label>
                            <input type="date" x-model="historyFilters.to" class="mt-1 block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="fetchStockHistory()" class="w-full rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 sm:w-auto">Filter</button>
                        </div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-gray-100">
                        <div x-show="historyLoading" class="flex items-center justify-center py-12 text-sm text-gray-500">
                            <svg class="mr-2 h-5 w-5 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Memuat riwayat stok...
                        </div>
                        <div x-show="!historyLoading && stockMovements.length === 0" class="py-12 text-center text-sm text-gray-500">
                            Belum ada pergerakan stok untuk bahan ini.
                        </div>
                        <div x-show="!historyLoading && stockMovements.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tipe</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Jumlah</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Referensi</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <template x-for="movement in stockMovements" :key="movement.id">
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600" x-text="formatDateTime(movement.created_at)"></td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="movement.type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="movement.type === 'in' ? 'Masuk' : 'Keluar'"></span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold" :class="movement.type === 'in' ? 'text-green-700' : 'text-red-700'" x-text="formatQuantity(movement)"></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600" x-text="movement.order_id ? `Pesanan #${movement.order_id}` : (movement.creator?.name || '-')"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600" x-text="movement.note || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                    <button type="button" @click="closeHistoryModal()" class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">Tutup</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function inventoryManager() {
    return {
        inventory: [],
        suppliers: [],
        loading: false,
        saving: false,
        search: '',
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
        isModalOpen: false,
        isAddStockModalOpen: false,
        isHistoryModalOpen: false,
        historyLoading: false,
        activeItem: null,
        historyItem: null,
        stockMovements: [],
        historyFilters: { from: '', to: '' },
        form: { id: null, ingredient_name: '', unit: '', min_stock: 0, current_stock: 0, supplier: '', supplier_id: '', last_price: '', lead_time_days: '' },
        addStockForm: { quantity: '', note: '' },

        init() {
            this.fetchSuppliers();
            this.fetchInventory();
        },

        async fetchSuppliers() {
            try {
                const res = await fetch('/api/admin/suppliers?status=active&per_page=1000', {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.suppliers = json.data || [];
                }
            } catch (e) { console.error(e); }
        },

        async fetchInventory(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                if (this.search) params.append('search', this.search);
                const url = '/api/admin/inventory?' + params.toString();
                const res = await fetch(url, {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.inventory = json.data.data || json.data;
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };
                }
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchInventory(page);
        },

        openModal(item = null) {
            if (item) {
                this.form = {
                    ...item,
                    supplier_id: item.supplier_id || '',
                    last_price: item.last_price || '',
                    lead_time_days: item.lead_time_days || ''
                };
            } else {
                this.form = { id: null, ingredient_name: '', unit: '', min_stock: 0, current_stock: 0, supplier: '', supplier_id: '', last_price: '', lead_time_days: '' };
            }
            this.isModalOpen = true;
        },

        closeModal() { this.isModalOpen = false; },

        syncSupplierName() {
            const supplier = this.suppliers.find(item => String(item.id) === String(this.form.supplier_id));
            this.form.supplier = supplier ? supplier.name : '';
            if (!supplier) {
                this.form.last_price = '';
                this.form.lead_time_days = '';
            }
        },

        openAddStockModal(item) {
            this.activeItem = item;
            this.addStockForm = { quantity: '', note: '' };
            this.isAddStockModalOpen = true;
        },

        async saveItem() {
            this.saving = true;
            try {
                let url = '/api/admin/inventory';
                let method = 'POST';
                if (this.form.id) {
                    url = `/api/admin/inventory/${this.form.id}`;
                    method = 'PUT';
                }
                const res = await fetch(url, {
                    method: method,
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify(this.form)
                });
                if(res.ok) {
                    this.closeModal();
                    this.fetchInventory(this.pagination.current_page);
                } else {
                    const err = await res.json().catch(() => ({}));
                    const message = err.message || Object.values(err.errors || {}).flat()[0] || 'Data bahan baku tidak dapat disimpan.';
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menyimpan bahan baku', message });
                }
            } catch (e) { console.error(e); }
            finally { this.saving = false; }
        },

        async deleteItem(id) {
            const confirmed = await window.restoConfirm({ variant: 'danger', title: 'Hapus bahan baku?', message: 'Data bahan baku akan dihapus dari stok.', confirmText: 'Hapus' });
            if(!confirmed) return;
            try {
                await fetch(`/api/admin/inventory/${id}`, {
                    method: 'DELETE',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                this.fetchInventory(this.pagination.current_page);
            } catch (e) { console.error(e); }
        },

        async addStock() {
            this.saving = true;
            try {
                const res = await fetch(`/api/admin/inventory/${this.activeItem.id}/add-stock`, {
                    method: 'POST',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify(this.addStockForm)
                });
                if(res.ok) {
                    this.isAddStockModalOpen = false;
                    this.fetchInventory(this.pagination.current_page);
                    await window.restoAlert({ variant: 'success', title: 'Stok berhasil ditambahkan', message: 'Jumlah stok bahan baku sudah diperbarui.' });
                } else {
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menambah stok', message: 'Cek kembali jumlah stok yang dimasukkan.' });
                }
            } catch (e) { console.error(e); }
            finally { this.saving = false; }
        },

        async openHistoryModal(item) {
            this.historyItem = item;
            this.stockMovements = [];
            this.historyFilters = { from: '', to: '' };
            this.isHistoryModalOpen = true;
            await this.fetchStockHistory();
        },

        closeHistoryModal() {
            this.isHistoryModalOpen = false;
            this.historyItem = null;
            this.stockMovements = [];
        },

        async fetchStockHistory() {
            if (!this.historyItem) return;

            this.historyLoading = true;
            try {
                const params = new URLSearchParams();
                if (this.historyFilters.from) params.append('from', this.historyFilters.from);
                if (this.historyFilters.to) params.append('to', this.historyFilters.to);

                const query = params.toString();
                const res = await fetch(`/api/admin/inventory/${this.historyItem.id}/movements${query ? '?' + query : ''}`, {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });

                if (res.ok) {
                    const json = await res.json();
                    this.stockMovements = json.data || [];
                } else {
                    const err = await res.json().catch(() => ({}));
                    await window.restoAlert({ variant: 'danger', title: 'Gagal memuat riwayat stok', message: err.message || 'Riwayat stok tidak dapat diambil.' });
                }
            } catch (e) {
                console.error(e);
                await window.restoAlert({ variant: 'danger', title: 'Gagal memuat riwayat stok', message: 'Terjadi kesalahan saat mengambil riwayat stok.' });
            } finally {
                this.historyLoading = false;
            }
        },

        formatDateTime(value) {
            if (!value) return '-';
            return new Date(value).toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatQuantity(movement) {
            const number = Number(movement.quantity_change || 0);
            const formatted = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(Math.abs(number));
            const sign = movement.type === 'in' ? '+' : '-';
            return `${sign}${formatted}`;
        }
    }
}
</script>
@endpush
