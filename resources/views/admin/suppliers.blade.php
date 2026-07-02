@extends('layouts.admin')

@section('title', 'Master Supplier')

@section('content')
<div x-data="supplierManager()" x-init="init()" class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:max-w-sm relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" x-model="search" @input.debounce.300ms="fetchSuppliers()" class="block w-full rounded-xl border border-gray-200 bg-white py-2 pl-10 pr-3 text-sm placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="Cari supplier...">
        </div>
        <button @click="openModal()" class="inline-flex w-full items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-primary-700 sm:w-auto">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"></path></svg>
            Tambah Supplier
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Supplier</p>
            <p class="mt-2 text-2xl font-bold text-gray-900" x-text="suppliers.length"></p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Supplier Aktif</p>
            <p class="mt-2 text-2xl font-bold text-gray-900" x-text="activeCount()"></p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Dipakai Bahan</p>
            <p class="mt-2 text-2xl font-bold text-gray-900" x-text="usedInventoryCount()"></p>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div x-show="loading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-sm">
            <svg class="h-8 w-8 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Kontak</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Bahan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="supplier in suppliers" :key="supplier.id">
                        <tr class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900" x-text="supplier.name"></div>
                                <div class="text-xs text-gray-500" x-text="supplier.address || '-'"></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="font-semibold text-gray-900" x-text="supplier.contact_person || '-'"></div>
                                <div x-text="supplier.phone || '-'"></div>
                                <div class="text-xs" x-text="supplier.email || '-'"></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold" :class="supplier.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" x-text="supplier.is_active ? 'Aktif' : 'Nonaktif'"></span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-700" x-text="(supplier.inventory_count || 0) + ' bahan'"></td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <button @click="openModal(supplier)" class="rounded-lg bg-indigo-50 p-2 text-indigo-600 transition hover:bg-indigo-100 hover:text-indigo-900" title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 00-3.536-3.536L4 16.929V20z"></path></svg>
                                </button>
                                <button @click="deleteSupplier(supplier)" class="ml-2 rounded-lg bg-red-50 p-2 text-red-600 transition hover:bg-red-100 hover:text-red-900" title="Hapus">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="suppliers.length === 0 && !loading">
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada supplier.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <x-admin-pagination mode="footer" label="supplier" />
    </div>

    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-900/50" @click="closeModal()"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div x-show="isModalOpen" x-transition.scale.origin.bottom class="inline-block w-full transform overflow-hidden rounded-t-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:max-w-lg sm:rounded-2xl sm:align-middle">
                <form @submit.prevent="saveSupplier">
                    <div class="space-y-4 bg-white px-4 pb-4 pt-5 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900" x-text="form.id ? 'Edit Supplier' : 'Tambah Supplier'"></h3>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Nama Supplier</label>
                            <input type="text" x-model="form.name" required class="block w-full rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">PIC</label>
                                <input type="text" x-model="form.contact_person" class="block w-full rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Telepon</label>
                                <input type="text" x-model="form.phone" class="block w-full rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" x-model="form.email" class="block w-full rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Alamat</label>
                            <textarea x-model="form.address" rows="2" class="block w-full rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Catatan</label>
                            <textarea x-model="form.notes" rows="2" class="block w-full rounded-xl border border-gray-300 px-4 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                        </div>
                        <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700">
                            <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            Supplier aktif
                        </label>
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-4 py-3 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" @click="closeModal()" class="inline-flex justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="saving" class="inline-flex justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-primary-700 disabled:opacity-60" x-text="saving ? 'Menyimpan...' : 'Simpan'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function supplierManager() {
    return {
        suppliers: [],
        loading: false,
        saving: false,
        search: '',
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
        isModalOpen: false,
        form: {
            id: null,
            name: '',
            contact_person: '',
            phone: '',
            email: '',
            address: '',
            notes: '',
            is_active: true
        },

        init() {
            this.fetchSuppliers();
        },

        activeCount() {
            return this.suppliers.filter(item => item.is_active).length;
        },

        usedInventoryCount() {
            return this.suppliers.reduce((total, item) => total + Number(item.inventory_count || 0), 0);
        },

        async fetchSuppliers(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                if (this.search) params.append('search', this.search);
                const res = await fetch('/api/admin/suppliers?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if (res.ok) {
                    const json = await res.json();
                    this.suppliers = json.data || [];
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };
                }
            } catch (e) {
                console.error('Gagal mengambil supplier', e);
            } finally {
                this.loading = false;
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchSuppliers(page);
        },

        openModal(supplier = null) {
            this.form = supplier ? { ...supplier } : {
                id: null,
                name: '',
                contact_person: '',
                phone: '',
                email: '',
                address: '',
                notes: '',
                is_active: true
            };
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        async saveSupplier() {
            this.saving = true;
            try {
                const url = this.form.id ? `/api/admin/suppliers/${this.form.id}` : '/api/admin/suppliers';
                const method = this.form.id ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify(this.form)
                });

                if (res.ok) {
                    this.closeModal();
                    await this.fetchSuppliers(this.pagination.current_page);
                } else {
                    const json = await res.json();
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menyimpan supplier', message: json.message || 'Cek kembali data supplier.' });
                }
            } catch (e) {
                console.error('Gagal menyimpan supplier', e);
            } finally {
                this.saving = false;
            }
        },

        async deleteSupplier(supplier) {
            const confirmed = await window.restoConfirm({ variant: 'danger', title: 'Hapus supplier?', message: `Supplier ${supplier.name} akan dihapus jika belum dipakai bahan baku.`, confirmText: 'Hapus' });
            if (!confirmed) return;

            try {
                const res = await fetch(`/api/admin/suppliers/${supplier.id}`, {
                    method: 'DELETE',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });

                if (res.ok) {
                    await this.fetchSuppliers(this.pagination.current_page);
                } else {
                    const json = await res.json();
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menghapus supplier', message: json.message || 'Supplier tidak dapat dihapus.' });
                }
            } catch (e) {
                console.error('Gagal menghapus supplier', e);
            }
        }
    }
}
</script>
@endpush
