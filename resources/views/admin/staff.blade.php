@extends('layouts.admin')

@section('title', 'Manajemen Karyawan')

@section('content')
<div x-data="staffManager()" x-init="init()" class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Daftar Staf & Karyawan</h2>
            <p class="text-sm text-gray-500">Kelola akses Waiter, Chef, dan Admin lainnya.</p>
        </div>
        <button @click="openModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-primary-600 hover:bg-primary-700 w-full sm:w-auto justify-center transition-colors">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            Tambah Karyawan
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden relative">
        <div x-show="loading" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama & Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="user in staffs" :key="user.id">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600" x-text="user.name.charAt(0)"></div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900" x-text="user.name"></div>
                                    <div class="text-sm text-gray-500" x-text="user.email"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800" x-text="(user.roles && user.roles.length > 0) ? user.roles[0].name : 'N/A'"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="user.is_active ? 'Aktif' : 'Nonaktif'"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button @click="deactivateUser(user)" class="text-xs font-medium" :class="user.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'" x-text="user.is_active ? 'Nonaktifkan' : 'Aktifkan'"></button>
                        </td>
                    </tr>
                </template>
                <tr x-show="staffs.length === 0 && !loading">
                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">Tidak ada staf.</td>
                </tr>
            </tbody>
        </table>
        <x-admin-pagination mode="footer" label="karyawan" />
    </div>

    <!-- Modal Form Tambah Staf -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form @submit.prevent="saveStaff">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 space-y-4">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-4">Tambah Karyawan Baru</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" x-model="form.name" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" x-model="form.email" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            Password sementara akan dibuat otomatis dan dikirim ke email karyawan.
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role (Peran)</label>
                            <select x-model="form.role" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                                <option value="">Pilih Role</option>
                                <option value="waiter">Waiter / Pelayan</option>
                                <option value="chef">Chef / Koki (KDS)</option>
                                <option value="admin">Administrator</option>
                            </select>
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
function staffManager() {
    return {
        staffs: [],
        loading: false,
        saving: false,
        isModalOpen: false,
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
        form: { name: '', email: '', role: '' },

        init() {
            this.fetchStaffs();
        },

        async fetchStaffs(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                const res = await fetch('/api/admin/staff?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.staffs = json.data.data || json.data;
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };
                }
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchStaffs(page);
        },

        openModal() {
            this.form = { name: '', email: '', role: '' };
            this.isModalOpen = true;
        },

        closeModal() { this.isModalOpen = false; },

        async saveStaff() {
            this.saving = true;
            try {
                const res = await fetch('/api/admin/staff', {
                    method: 'POST',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify(this.form)
                });
                
                if(res.ok) {
                    this.closeModal();
                    await window.restoAlert({ variant: 'success', title: 'Karyawan berhasil ditambahkan', message: 'Akun karyawan baru sudah dibuat.' });
                    this.fetchStaffs(this.pagination.current_page);
                } else {
                    const err = await res.json();
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menambah karyawan', message: err.message || 'Cek kembali data input.' });
                }
            } catch (e) { console.error(e); }
            finally { this.saving = false; }
        },

        async deactivateUser(user) {
            const action = user.is_active ? 'menonaktifkan' : 'mengaktifkan';
            const confirmed = await window.restoConfirm({ variant: 'danger', title: 'Ubah status akun?', message: `Yakin ingin ${action} akun ini?`, confirmText: user.is_active ? 'Nonaktifkan' : 'Aktifkan' });
            if(!confirmed) return;

            try {
                const endpoint = user.is_active ? 'deactivate' : 'activate';
                const res = await fetch(`/api/admin/staff/${user.id}/${endpoint}`, {
                    method: 'PATCH',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    user.is_active = !user.is_active;
                }
            } catch(e) { console.error(e); }
        }
    }
}
</script>
@endpush
