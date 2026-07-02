@extends('layouts.admin')

@section('title', 'Manajemen Reservasi')

@section('content')
<div x-data="reservationManager()" x-init="init()" class="space-y-6">

    <!-- Filters -->
    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="overflow-x-auto">
                <div class="inline-grid min-w-max grid-cols-4 gap-1 rounded-2xl bg-gray-100 p-1">
                    <button @click="setStatus('')" class="min-h-11 rounded-xl px-4 text-sm font-black transition" :class="statusFilter === '' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Semua</button>
                    <button @click="setStatus('pending')" class="min-h-11 rounded-xl px-4 text-sm font-black transition" :class="statusFilter === 'pending' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Menunggu</button>
                    <button @click="setStatus('confirmed')" class="min-h-11 rounded-xl px-4 text-sm font-black transition" :class="statusFilter === 'confirmed' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Dikonfirmasi</button>
                    <button @click="setStatus('cancelled')" class="min-h-11 rounded-xl px-4 text-sm font-black transition" :class="statusFilter === 'cancelled' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Dibatalkan</button>
                </div>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="flex min-h-12 items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 transition focus-within:border-primary-300 focus-within:bg-white focus-within:ring-4 focus-within:ring-primary-500/10">
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" x-model="search" @input.debounce.400ms="fetchReservations()" placeholder="Cari reservasi, meja, atau pelanggan" class="h-full min-w-0 flex-1 border-0 bg-transparent px-0 text-sm font-semibold text-gray-900 outline-none placeholder:text-gray-400 focus:border-0 focus:outline-none focus:ring-0">
                    <button x-show="search" @click="search = ''; fetchReservations()" class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Bersihkan pencarian">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <input type="date" x-model="dateFilter" @change="fetchReservations()" class="min-h-12 rounded-2xl border-gray-200 bg-white px-4 text-sm font-bold text-gray-700 shadow-sm focus:border-primary-400 focus:ring-primary-500/20">
                <button x-show="hasActiveFilter()" @click="clearFilters()" class="min-h-12 rounded-2xl bg-primary-50 px-4 text-sm font-black text-primary-700 hover:bg-primary-100">Reset filter</button>
                <button @click="fetchReservations()" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-primary-600" title="Refresh">
                    <svg class="h-5 w-5" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Reservations -->
    <div class="relative">
        <div x-show="loading" class="absolute inset-0 z-10 flex items-center justify-center rounded-2xl bg-white/70 backdrop-blur-sm">
            <svg class="h-8 w-8 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>

        <div x-show="reservations.length > 0" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <template x-for="res in reservations" :key="res.id">
                <article class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" :class="{
                    'border-yellow-200 ring-1 ring-yellow-100': res.status === 'pending',
                    'border-green-200 ring-1 ring-green-100': res.status === 'confirmed',
                    'border-gray-200': res.status === 'cancelled'
                }">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 p-5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-lg font-black text-gray-900">#<span x-text="res.id"></span></span>
                                <span class="rounded-full px-3 py-1 text-xs font-black" :class="statusClass(res.status)" x-text="statusLabel(res.status)"></span>
                            </div>
                            <p class="mt-2 truncate text-sm font-bold text-gray-900" x-text="res.user?.name || 'Tamu'"></p>
                            <p class="truncate text-xs font-medium text-gray-500" x-text="res.user?.email || '-'"></p>
                        </div>
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-px bg-gray-100 text-sm">
                        <div class="bg-white p-4">
                            <p class="text-xs font-black uppercase text-gray-400">Jadwal</p>
                            <p class="mt-1 font-black text-gray-900" x-text="formatDate(res.date)"></p>
                            <p class="text-xs font-bold text-gray-500" x-text="res.time"></p>
                        </div>
                        <div class="bg-white p-4">
                            <p class="text-xs font-black uppercase text-gray-400">Meja</p>
                            <p class="mt-1 font-black text-gray-900">Meja <span x-text="res.table?.table_number || res.table_id"></span></p>
                            <p class="text-xs font-bold text-gray-500"><span x-text="res.number_of_people"></span> orang</p>
                        </div>
                    </div>

                    <div x-show="res.notes" class="border-t border-gray-100 px-5 py-4">
                        <p class="text-xs font-black uppercase text-gray-400">Catatan</p>
                        <p class="mt-1 text-sm font-medium leading-relaxed text-gray-600" x-text="res.notes"></p>
                    </div>

                    <div class="flex gap-2 border-t border-gray-100 bg-gray-50 p-4">
                        <template x-if="res.status === 'pending'">
                            <div class="grid w-full grid-cols-2 gap-2">
                                <button @click="requestStatusChange(res, 'cancel')" class="rounded-xl border border-red-100 bg-white px-3 py-2.5 text-sm font-black text-red-600 transition hover:bg-red-50">Tolak</button>
                                <button @click="requestStatusChange(res, 'confirm')" class="rounded-xl bg-green-600 px-3 py-2.5 text-sm font-black text-white shadow-sm shadow-green-500/20 transition hover:bg-green-700">Terima</button>
                            </div>
                        </template>
                        <template x-if="res.status === 'confirmed'">
                            <button @click="requestStatusChange(res, 'cancel')" class="w-full rounded-xl border border-red-100 bg-white px-3 py-2.5 text-sm font-black text-red-600 transition hover:bg-red-50">Batalkan reservasi</button>
                        </template>
                        <template x-if="res.status === 'cancelled'">
                            <div class="w-full rounded-xl bg-gray-100 px-3 py-2.5 text-center text-sm font-black text-gray-500">Reservasi ditutup</div>
                        </template>
                    </div>
                </article>
            </template>
        </div>

        <div x-show="reservations.length === 0 && !loading" class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-50 text-gray-300">
                <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <p class="mt-4 text-lg font-black text-gray-900">Tidak ada reservasi ditemukan</p>
            <p class="mt-1 text-sm text-gray-500">Data akan muncul sesuai filter yang dipilih.</p>
            <button x-show="hasActiveFilter()" @click="clearFilters()" class="mt-5 rounded-xl bg-primary-600 px-4 py-2 text-sm font-black text-white hover:bg-primary-700">Lihat semua reservasi</button>
        </div>

        <x-admin-pagination class="mt-4" label="reservasi" />
    </div>

    <!-- Confirmation Modal -->
    <div x-show="confirmDialog.open"
         x-cloak
         @keydown.escape.window="closeConfirmDialog()"
         x-transition.opacity.duration.200ms
         class="fixed inset-0 z-[70] flex items-end justify-center bg-gray-900/60 p-0 backdrop-blur-sm sm:items-center sm:p-4"
         style="display: none;">
        <div @click="closeConfirmDialog()" class="absolute inset-0"></div>
        <section x-show="confirmDialog.open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave-end="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95"
                 class="relative w-full overflow-hidden rounded-t-[2rem] bg-white shadow-2xl ring-1 ring-black/5 sm:rounded-[2rem]"
                 style="max-width: 36rem;">
            <div class="absolute inset-x-0 top-0 h-1" :class="confirmDialog.action === 'confirm' ? 'bg-green-500' : 'bg-red-500'"></div>
            <div class="absolute right-0 top-0 h-28 w-28 rounded-bl-full opacity-10" :class="confirmDialog.action === 'confirm' ? 'bg-green-500' : 'bg-red-500'"></div>
            <div class="p-5 sm:p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl ring-1" :class="confirmDialog.action === 'confirm' ? 'bg-green-50 text-green-600 ring-green-100' : 'bg-red-50 text-red-500 ring-red-100'">
                    <svg x-show="confirmDialog.action === 'confirm'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="confirmDialog.action !== 'confirm'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-black uppercase tracking-wider" :class="confirmDialog.action === 'confirm' ? 'text-green-600' : 'text-red-500'" x-text="confirmDialog.eyebrow"></p>
                    <h3 class="mt-1 text-xl font-black text-gray-900" x-text="confirmDialog.title"></h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500" x-text="confirmDialog.message"></p>
                </div>
                <button @click="closeConfirmDialog()" class="relative rounded-full bg-gray-100 p-2 text-gray-500 transition hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div x-show="confirmDialog.reservation" class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Detail reservasi</p>
                        <p class="mt-0.5 text-sm font-black text-gray-900">Meja <span x-text="confirmDialog.reservation?.table?.table_number || confirmDialog.reservation?.table_id"></span></p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-black" :class="confirmDialog.action === 'confirm' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'" x-text="confirmDialog.action === 'confirm' ? 'Akan diterima' : 'Akan dibatalkan'"></span>
                </div>
                <div class="grid gap-0 text-sm sm:grid-cols-2">
                    <div class="border-b border-gray-200 p-4 sm:border-r">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Pelanggan</p>
                        <p class="mt-1 font-black text-gray-900" x-text="confirmDialog.reservation?.user?.name || 'Tamu'"></p>
                        <p class="truncate text-xs text-gray-500" x-text="confirmDialog.reservation?.user?.email || '-'"></p>
                    </div>
                    <div class="border-b border-gray-200 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Jadwal</p>
                        <p class="mt-1 font-black text-gray-900"><span x-text="formatDate(confirmDialog.reservation?.date)"></span></p>
                        <p class="text-xs font-bold text-gray-500" x-text="confirmDialog.reservation?.time"></p>
                    </div>
                    <div class="p-4 sm:border-r sm:border-gray-200">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Jumlah tamu</p>
                        <p class="mt-1 font-black text-gray-900"><span x-text="confirmDialog.reservation?.number_of_people"></span> orang</p>
                    </div>
                    <div class="p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Status saat ini</p>
                        <p class="mt-1 font-black capitalize text-gray-900" x-text="confirmDialog.reservation?.status || '-'"></p>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <button @click="closeConfirmDialog()" :disabled="confirmDialog.loading" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700 transition hover:bg-gray-50 disabled:opacity-60">Kembali</button>
                <button @click="submitStatusChange()" :disabled="confirmDialog.loading" class="flex items-center justify-center rounded-xl px-4 py-3 text-sm font-black text-white shadow-lg transition disabled:opacity-70" :class="confirmDialog.action === 'confirm' ? 'bg-green-600 shadow-green-500/20 hover:bg-green-700' : 'bg-red-500 shadow-red-500/20 hover:bg-red-600'">
                    <svg x-show="confirmDialog.loading" class="-ml-1 mr-2 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="confirmDialog.loading ? 'Memproses...' : confirmDialog.confirmText"></span>
                </button>
            </div>
            </div>
        </section>
    </div>

    <!-- Notice Modal -->
    <div x-show="noticeDialog.open" x-cloak class="fixed inset-0 z-[80] flex items-end justify-center bg-gray-900/60 p-0 backdrop-blur-sm sm:items-center sm:p-4" style="display: none;">
        <div @click="noticeDialog.open = false" class="absolute inset-0"></div>
        <section class="relative w-full rounded-t-[2rem] bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-[2rem] sm:p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl" :class="noticeDialog.variant === 'success' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500'">
                    <svg x-show="noticeDialog.variant === 'success'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="noticeDialog.variant !== 'success'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-xl font-black text-gray-900" x-text="noticeDialog.title"></h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500" x-text="noticeDialog.message"></p>
                </div>
            </div>
            <button @click="noticeDialog.open = false" class="mt-6 w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-primary-500/20 transition-colors hover:bg-primary-700">Mengerti</button>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
function reservationManager() {
    return {
        reservations: [],
        loading: false,
        statusFilter: '',
        dateFilter: '',
        search: '',
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
        confirmDialog: {
            open: false,
            loading: false,
            action: '',
            reservation: null,
            eyebrow: '',
            title: '',
            message: '',
            confirmText: ''
        },
        noticeDialog: {
            open: false,
            title: '',
            message: '',
            variant: 'success'
        },

        init() {
            this.fetchReservations();
        },

        async fetchReservations(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                if (this.statusFilter) params.append('status', this.statusFilter);
                if (this.dateFilter) params.append('date', this.dateFilter);
                if (this.search) params.append('search', this.search);

                const res = await fetch('/api/admin/reservations?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.reservations = json.data.data || json.data;
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };
                }
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchReservations(page);
        },

        setStatus(status) {
            this.statusFilter = status;
            this.fetchReservations();
        },

        hasActiveFilter() {
            return this.statusFilter !== '' || this.dateFilter !== '' || this.search !== '';
        },

        clearFilters() {
            this.statusFilter = '';
            this.dateFilter = '';
            this.search = '';
            this.fetchReservations();
        },

        statusLabel(status) {
            return {
                pending: 'Menunggu',
                confirmed: 'Dikonfirmasi',
                cancelled: 'Dibatalkan',
            }[status] || 'Semua';
        },

        statusClass(status) {
            return {
                pending: 'bg-yellow-100 text-yellow-800',
                confirmed: 'bg-green-100 text-green-800',
                cancelled: 'bg-red-100 text-red-800',
            }[status] || 'bg-gray-100 text-gray-700';
        },

        async requestStatusChange(reservation, action) {
            const isConfirm = action === 'confirm';
            const tableNumber = reservation.table?.table_number || reservation.table_id;
            const scheduledAt = `${this.formatDate(reservation.date)} pukul ${reservation.time}`;

            const confirmed = await window.restoConfirm({
                variant: isConfirm ? 'success' : 'danger',
                eyebrow: isConfirm ? 'Konfirmasi Reservasi' : 'Pembatalan Reservasi',
                title: isConfirm ? 'Terima reservasi ini?' : 'Batalkan reservasi ini?',
                message: isConfirm
                    ? `Reservasi Meja ${tableNumber} pada ${scheduledAt} akan dikonfirmasi untuk pelanggan.`
                    : `Reservasi Meja ${tableNumber} pada ${scheduledAt} akan ditandai dibatalkan.`,
                confirmText: isConfirm ? 'Terima Reservasi' : 'Batalkan Reservasi',
            });

            if (!confirmed) return;

            await this.performStatusChange(reservation, action);
        },

        closeConfirmDialog() {
            if (this.confirmDialog.loading) return;
            this.confirmDialog.open = false;
        },

        async submitStatusChange() {
            const reservation = this.confirmDialog.reservation;
            const action = this.confirmDialog.action;
            if (!reservation || !action) return;

            await this.performStatusChange(reservation, action);
        },

        async performStatusChange(reservation, action) {
            this.confirmDialog.loading = true;

            try {
                const res = await fetch(`/api/admin/reservations/${reservation.id}/${action}`, {
                    method: 'POST',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    this.confirmDialog.open = false;
                    await window.restoAlert({
                        variant: 'success',
                        title: action === 'confirm' ? 'Reservasi dikonfirmasi' : 'Reservasi dibatalkan',
                        message: action === 'confirm'
                            ? 'Pelanggan akan melihat status reservasi sebagai dikonfirmasi.'
                            : 'Status reservasi sudah diperbarui menjadi dibatalkan.',
                    });
                    await this.fetchReservations(this.pagination.current_page);
                } else {
                    const err = await res.json();
                    await window.restoAlert({
                        variant: 'danger',
                        title: 'Aksi gagal',
                        message: err.message || 'Terjadi kesalahan saat memperbarui reservasi.',
                    });
                }
            } catch (e) {
                console.error(e);
                await window.restoAlert({
                    variant: 'danger',
                    title: 'Aksi gagal',
                    message: 'Tidak bisa terhubung ke server. Coba lagi.',
                });
            } finally {
                this.confirmDialog.loading = false;
            }
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
