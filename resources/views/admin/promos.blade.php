@extends('layouts.admin')

@section('title', 'Promo & Voucher')

@section('content')
<div x-data="promoManager()" x-init="init()" class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Daftar Promo & Voucher</h2>
            <p class="text-sm text-gray-500">Kelola kode diskon dan program promosi.</p>
        </div>
        <button @click="openModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-primary-600 hover:bg-primary-700 transition-colors">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Buat Promo Baru
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden relative">
        <div x-show="loading" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Promo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nilai Diskon</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Penggunaan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="promo in promos" :key="promo.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="promo.name"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-200 rounded text-gray-800 tracking-wider" x-text="promo.code"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <span x-text="promo.type === 'percentage' ? promo.value + '%' : formatCurrency(promo.value)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div x-text="formatDate(promo.start_date)"></div>
                                <div class="text-xs text-gray-400" x-text="'s/d ' + formatDate(promo.end_date)"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span x-text="promo.usage_count + ' / ' + (promo.usage_limit || '∞')"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                      :class="promo.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                      x-text="promo.is_active ? 'Aktif' : 'Nonaktif'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openModal(promo)" class="text-indigo-600 hover:text-indigo-900 mr-3 p-1 rounded hover:bg-indigo-50 transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button @click="deletePromo(promo.id)" class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors" title="Hapus">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="promos.length === 0 && !loading">
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada promo yang dibuat.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <x-admin-pagination mode="footer" label="promo" />
    </div>

    <!-- Modal Form -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="savePromo">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 space-y-4">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-4" x-text="form.id ? 'Edit Promo' : 'Buat Promo Baru'"></h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Promo</label>
                                <input type="text" x-model="form.name" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Voucher</label>
                                <input type="text" x-model="form.code" required style="text-transform:uppercase" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border font-mono">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Diskon</label>
                                <select x-model="form.type" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                                    <option value="percentage">Persentase (%)</option>
                                    <option value="nominal">Nominal Tetap (Rp)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Diskon</label>
                                <input type="number" x-model="form.value" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Batas Kuota Penggunaan</label>
                                <input type="number" x-model="form.usage_limit" placeholder="Kosongkan jika tak terbatas" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mulai Berlaku</label>
                                <input type="date" x-model="form.start_date" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Berakhir Pada</label>
                                <input type="date" x-model="form.end_date" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                            </div>
                        </div>
                        <div class="pt-2">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-600">Promo Aktif (dapat digunakan pelanggan)</span>
                            </label>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button type="submit" :disabled="saving" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                        <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function promoManager() {
    return {
        promos: [],
        loading: false,
        saving: false,
        isModalOpen: false,
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
        form: { id: null, name: '', code: '', type: 'percentage', value: '', min_purchase: 0, max_discount: 0, start_date: '', end_date: '', is_active: true, usage_limit: '' },

        init() {
            this.fetchPromos();
        },

        async fetchPromos(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                const res = await fetch('/api/admin/promos?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.promos = json.data.data || json.data;
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };
                }
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchPromos(page);
        },

        openModal(promo = null) {
            if (promo) {
                this.form = { ...promo, start_date: promo.start_date.split('T')[0], end_date: promo.end_date.split('T')[0] };
                this.form.is_active = !!promo.is_active;
            } else {
                this.form = { id: null, name: '', code: '', type: 'percentage', value: '', min_purchase: 0, max_discount: 0, start_date: '', end_date: '', is_active: true, usage_limit: '' };
            }
            this.isModalOpen = true;
        },

        closeModal() { this.isModalOpen = false; },

        async savePromo() {
            this.saving = true;
            try {
                let url = '/api/admin/promos';
                let method = 'POST';
                if (this.form.id) {
                    url = `/api/admin/promos/${this.form.id}`;
                    method = 'PUT';
                }
                
                // uppercase code
                this.form.code = this.form.code.toUpperCase();

                const res = await fetch(url, {
                    method: method,
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify(this.form)
                });
                if(res.ok) {
                    this.closeModal();
                    this.fetchPromos(this.pagination.current_page);
                } else {
                    const err = await res.json();
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menyimpan promo', message: err.message || 'Cek input Anda.' });
                }
            } catch (e) { console.error(e); }
            finally { this.saving = false; }
        },

        async deletePromo(id) {
            const confirmed = await window.restoConfirm({ variant: 'danger', title: 'Hapus promo?', message: 'Promo atau voucher ini akan dihapus.', confirmText: 'Hapus' });
            if(!confirmed) return;
            try {
                await fetch(`/api/admin/promos/${id}`, {
                    method: 'DELETE',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                this.fetchPromos(this.pagination.current_page);
            } catch (e) { console.error(e); }
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val);
        },
        
        formatDate(dateString) {
            if(!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    }
}
</script>
@endpush
