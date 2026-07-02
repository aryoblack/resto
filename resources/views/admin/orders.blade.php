@extends('layouts.admin')

@section('title', 'Pesanan Live')

@section('content')
@php
    $canAddOrderItems = in_array(auth()->user()->role ?? null, ['admin', 'waiter'], true);
@endphp

<div x-data="orderManager()" x-init="init()" class="flex min-h-0 flex-col space-y-4 lg:h-[calc(100vh-10rem)] lg:space-y-6">

    <!-- Filters & Actions -->
    <div class="flex-shrink-0 overflow-hidden rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.8fr)]">
            <div class="min-w-0 space-y-3">
                <div class="-mx-1 overflow-x-auto px-1">
                    <div class="inline-flex min-w-full gap-1 rounded-2xl bg-gray-100 p-1 sm:grid sm:grid-cols-5">
                        <button @click="setStatus('')" class="min-h-11 flex-1 shrink-0 rounded-xl px-4 text-sm font-black transition sm:min-w-0 sm:px-3" :class="statusFilter === '' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Semua</button>
                        <button @click="setStatus('Diterima')" class="min-h-11 flex-1 shrink-0 rounded-xl px-4 text-sm font-black transition sm:min-w-0 sm:px-3" :class="statusFilter === 'Diterima' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Baru</button>
                        <button @click="setStatus('Diproses')" class="min-h-11 flex-1 shrink-0 rounded-xl px-4 text-sm font-black transition sm:min-w-0 sm:px-3" :class="statusFilter === 'Diproses' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Proses</button>
                        <button @click="setStatus('Dimasak')" class="min-h-11 flex-1 shrink-0 rounded-xl px-4 text-sm font-black transition sm:min-w-0 sm:px-3" :class="statusFilter === 'Dimasak' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Dimasak</button>
                        <button @click="setStatus('Selesai')" class="min-h-11 flex-1 shrink-0 rounded-xl px-4 text-sm font-black transition sm:min-w-0 sm:px-3" :class="statusFilter === 'Selesai' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'">Siap Saji</button>
                    </div>
                </div>

                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <div class="w-full rounded-2xl border border-gray-100 bg-white p-1 shadow-sm sm:w-auto">
                        <div class="grid grid-cols-3 gap-1">
                            <button @click="setType('')" class="min-h-10 rounded-xl px-2 text-xs font-black uppercase tracking-wide transition sm:px-3" :class="typeFilter === '' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">Semua</button>
                            <button @click="setType('dine_in')" class="min-h-10 rounded-xl px-2 text-xs font-black uppercase tracking-wide transition sm:px-3" :class="typeFilter === 'dine_in' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">Dine In</button>
                            <button @click="setType('delivery')" class="min-h-10 rounded-xl px-2 text-xs font-black uppercase tracking-wide transition sm:px-3" :class="typeFilter === 'delivery' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">Delivery</button>
                        </div>
                    </div>

                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-500" x-text="statusLabel(statusFilter)"></span>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-500" x-text="typeLabel(typeFilter)"></span>
                    <button x-show="hasActiveFilter()" @click="clearFilters()" class="rounded-full bg-primary-50 px-3 py-1 text-xs font-black text-primary-700 hover:bg-primary-100">Reset filter</button>
                </div>
            </div>

            <div class="flex min-w-0 gap-3">
                <div class="flex h-14 flex-1 items-center rounded-2xl border border-gray-200 bg-white shadow-sm transition focus-within:border-primary-500 focus-within:ring-4 focus-within:ring-primary-50">
                    <div class="flex h-full w-12 flex-shrink-0 items-center justify-center text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" x-model="search" @input.debounce.400ms="fetchOrders()" placeholder="Cari order, meja, atau pelanggan" class="h-full min-w-0 flex-1 border-0 bg-transparent px-0 text-sm font-semibold text-gray-900 outline-none placeholder:text-gray-400 focus:border-0 focus:outline-none focus:ring-0">
                    <button x-show="search" @click="search = ''; fetchOrders()" class="mr-2 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" title="Bersihkan pencarian">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <button @click="fetchOrders()" class="inline-flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-primary-600" title="Refresh">
                    <svg class="h-5 w-5" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Orders Grid -->
    <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 xl:gap-6">
            
            <template x-for="order in orders" :key="order.id">
                <div class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-shadow hover:shadow-md" :class="{'ring-2 ring-primary-500': order.order_status === 'Diterima'}">
                    
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:px-5" :class="getStatusHeaderClass(order.order_status)">
                        <div class="min-w-0">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <span class="min-w-0 break-words text-base font-black leading-tight text-gray-900 sm:text-lg">#<span x-text="order.order_number || order.id"></span></span>
                                <span class="px-2 py-0.5 text-xs font-bold rounded-lg bg-white/70 text-gray-700 ring-1 ring-black/5" x-text="order.order_type === 'dine_in' ? 'Dine In' : 'Delivery'"></span>
                                <span class="px-2 py-0.5 text-xs font-bold rounded-lg" :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" x-text="order.payment_status === 'paid' ? 'Lunas' : 'Belum Bayar'"></span>
                            </div>
                            <div class="text-sm mt-1 opacity-90">
                                <span x-show="order.order_type === 'dine_in'">Meja <span class="font-bold" x-text="order.table?.table_number || order.table_id || '-'"></span></span>
                                <span class="mx-1">•</span>
                                <span x-text="formatTime(order.created_at)"></span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium" :class="getStatusBadgeClass(order.order_status)" x-text="order.order_status"></span>
                        </div>
                    </div>

                    <!-- Body: Items -->
                    <div class="max-h-64 flex-1 overflow-y-auto p-4 sm:p-5">
                        <ul class="space-y-3">
                            <template x-for="item in order.items" :key="item.id">
                                <li class="flex min-w-0 items-start rounded-xl bg-gray-50 px-3 py-2 text-sm">
                                    <div class="mr-3 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-white text-xs font-black text-gray-700 ring-1 ring-gray-200" x-text="item.quantity + 'x'"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="break-words font-bold text-gray-900" x-text="item.menu?.name || item.menu_name"></div>
                                        <div x-show="item.variant_selected" class="text-xs text-gray-500 mt-0.5" x-text="'Varian: ' + item.variant_selected"></div>
                                        <div x-show="item.note" class="text-xs text-red-500 italic mt-0.5" x-text="'Catatan: ' + item.note"></div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Footer: Actions -->
                    <div class="mt-auto border-t border-gray-100 bg-white px-4 py-4 sm:px-5">
                        <div class="flex justify-between items-center mb-3">
                            <div class="text-sm text-gray-500">Total Tagihan</div>
                            <div class="font-black text-lg text-gray-900" x-text="formatCurrency(order.total_price)"></div>
                        </div>
                        <div class="flex gap-2">
                            @if($canAddOrderItems)
                            <button x-show="canAddItemsToOrder(order)" @click.stop="openAddItemModal(order)" class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary-100 bg-primary-50 px-3 py-2.5 text-sm font-black text-primary-700 transition hover:bg-primary-100" title="Tambah item">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 6v12m6-6H6"></path></svg>
                                <span class="hidden sm:inline">Item</span>
                            </button>
                            @endif

                            <!-- State Machine Actions -->
                            <template x-if="order.order_status === 'Diterima'">
                                <button @click="updateStatus(order.id, 'Diproses')" :disabled="isUpdatingOrder(order.id)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2.5 rounded-xl text-sm font-black transition-colors disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-blue-600">
                                    <span x-text="isUpdatingOrder(order.id) ? 'Memproses...' : 'Proses Pesanan'"></span>
                                </button>
                            </template>
                            
                            <template x-if="order.order_status === 'Diproses'">
                                <button @click="updateStatus(order.id, 'Dimasak')" :disabled="isUpdatingOrder(order.id)" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white px-3 py-2.5 rounded-xl text-sm font-black transition-colors disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-orange-500">
                                    <span x-text="isUpdatingOrder(order.id) ? 'Memproses...' : 'Mulai Masak'"></span>
                                </button>
                            </template>
                            
                            <template x-if="order.order_status === 'Selesai'">
                                <button @click="updateStatus(order.id, 'Disajikan')" :disabled="isUpdatingOrder(order.id)" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-3 py-2.5 rounded-xl text-sm font-black transition-colors disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-green-600">
                                    <span x-text="isUpdatingOrder(order.id) ? 'Memproses...' : 'Sajikan ke Pelanggan'"></span>
                                </button>
                            </template>

                            <button @click.stop="viewDetails(order)" class="p-2.5 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-100 transition-colors" title="Detail Lengkap">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="orders.length === 0 && !loading" class="col-span-full">
                <div class="rounded-2xl border border-gray-100 bg-white px-6 py-20 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-300">
                        <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <p class="text-lg font-black text-gray-900">Belum ada pesanan aktif</p>
                    <p class="text-sm text-gray-500 mt-1">Pesanan baru akan muncul di sini secara real-time.</p>
                    <button x-show="hasActiveFilter()" @click="clearFilters()" class="mt-5 rounded-xl bg-primary-600 px-4 py-2 text-sm font-black text-white hover:bg-primary-700">Lihat semua pesanan</button>
                </div>
            </div>
            
            <div x-show="loading && orders.length === 0" class="col-span-full py-20 flex justify-center">
                <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </div>
    </div>

    <x-admin-pagination label="pesanan" />

    <div x-show="addItemModalOpen" x-cloak @keydown.escape.window="closeAddItemModal()" class="fixed inset-0 z-[115] flex items-end justify-center bg-gray-900/60 p-0 backdrop-blur-sm sm:items-center sm:p-4" style="display: none;">
        <div class="absolute inset-0" @click="closeAddItemModal()"></div>
        <section x-show="addItemModalOpen"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave-end="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-xl overflow-hidden rounded-t-3xl bg-white shadow-2xl ring-1 ring-black/5 sm:rounded-3xl">
            <div class="absolute inset-x-0 top-0 h-1 bg-primary-500"></div>
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 p-5">
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-wider text-primary-600">Tambah Item</p>
                    <h3 class="mt-1 break-words text-xl font-black text-gray-900">
                        #<span x-text="addItemOrder?.order_number || addItemOrder?.id || '-'"></span>
                    </h3>
                    <p class="mt-1 text-sm font-semibold text-gray-500">Tambahkan menu yang belum masuk tagihan.</p>
                </div>
                <button @click="closeAddItemModal()" class="rounded-full bg-gray-100 p-2 text-gray-500 transition hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form @submit.prevent="submitAdditionalItem()" class="p-5">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-black text-gray-900">Menu</label>
                        <x-searchable-select
                            model="addItemForm.menu_id"
                            options="availableAdditionalMenus()"
                            value="option.id"
                            label="option.name + ' - ' + formatCurrency(option.price)"
                            description="'Stok ' + option.stock + (option.category?.name ? ' - ' + option.category.name : '')"
                            placeholder="Pilih menu tambahan"
                            search-placeholder="Cari menu..."
                            empty-text="Menu tidak ditemukan"
                            on-select="addItemForm.variant_id = ''; addItemForm.quantity = 1;"
                        />
                    </div>

                    <div x-show="selectedAdditionalMenuVariants().length > 0">
                        <label class="mb-1 block text-sm font-black text-gray-900">Varian</label>
                        <x-searchable-select
                            model="addItemForm.variant_id"
                            options="selectedAdditionalMenuVariants()"
                            value="option.id"
                            label="option.variant_name + (Number(option.extra_price || 0) > 0 ? ' +' + formatCurrency(option.extra_price || 0) : '')"
                            placeholder="Regular"
                            search-placeholder="Cari varian..."
                            empty-text="Varian tidak ditemukan"
                            :nullable="true"
                            null-label="Regular"
                        />
                    </div>

                    <div class="grid grid-cols-[8rem_minmax(0,1fr)] gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-black text-gray-900">Jumlah</label>
                            <input type="number" min="1" x-model.number="addItemForm.quantity" required class="block w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-900 focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-black text-gray-900">Catatan</label>
                            <input type="text" x-model="addItemForm.note" maxlength="500" placeholder="Opsional" class="block w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900 focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>

                    <div class="rounded-2xl bg-primary-50 p-4 ring-1 ring-primary-100">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-bold text-primary-700">Estimasi tambahan</span>
                            <span class="text-lg font-black text-primary-700" x-text="formatCurrency(addItemTotal())"></span>
                        </div>
                        <p class="mt-1 text-xs font-semibold text-primary-700/80">Total akhir akan dihitung ulang termasuk pajak, layanan, dan voucher.</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button type="button" @click="closeAddItemModal()" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700 transition hover:bg-gray-50">Batal</button>
                    <button type="submit" :disabled="addingItem || !addItemForm.menu_id" class="rounded-xl bg-primary-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-primary-500/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="addingItem ? 'Menambahkan...' : 'Tambah Item'"></span>
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div x-show="detailModalOpen" x-cloak @keydown.escape.window="closeDetails()" class="fixed inset-0 flex items-end justify-center p-0 sm:items-center sm:p-4" style="display: none; z-index: 120;">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeDetails()"></div>
        <section x-show="detailModalOpen"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave-end="translate-y-6 opacity-0 sm:translate-y-0 sm:scale-95"
                 class="relative flex w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl ring-1 ring-black/5 sm:rounded-3xl"
                 style="max-height: calc(100dvh - 1rem);">
            <div class="absolute inset-x-0 top-0 h-1 bg-primary-500"></div>
            <div class="flex flex-shrink-0 items-start justify-between gap-4 border-b border-gray-100 p-4 sm:p-6">
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-wider text-primary-600">Detail Pesanan</p>
                    <h3 class="mt-1 break-words text-lg font-black leading-tight text-gray-900 sm:text-2xl">
                        #<span x-text="selectedOrder?.order_number || selectedOrder?.id || '-'"></span>
                    </h3>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-black text-gray-700" x-text="orderTypeLabel(selectedOrder?.order_type)"></span>
                        <span class="rounded-lg px-2.5 py-1 text-xs font-black" :class="getStatusBadgeClass(selectedOrder?.order_status)" x-text="statusLabel(selectedOrder?.order_status)"></span>
                        <span class="rounded-lg px-2.5 py-1 text-xs font-black" :class="selectedOrder?.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" x-text="paymentStatusLabel(selectedOrder?.payment_status)"></span>
                    </div>
                </div>
                <button @click.stop="closeDetails()" class="rounded-full bg-gray-100 p-2 text-gray-500 transition hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Meja</p>
                        <p class="mt-1 text-base font-black text-gray-900" x-text="selectedOrder?.table?.table_number || selectedOrder?.table_id || '-'"></p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Waktu</p>
                        <p class="mt-1 text-base font-black text-gray-900" x-text="formatDateTime(selectedOrder?.created_at)"></p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Pembayaran</p>
                        <p class="mt-1 text-base font-black text-gray-900" x-text="paymentMethodLabel(selectedOrder?.payment_method)"></p>
                    </div>
                </div>

                <div x-show="selectedOrder?.notes" class="mt-4 rounded-2xl border border-orange-100 bg-orange-50 p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-orange-600">Catatan Pesanan</p>
                    <p class="mt-1 whitespace-pre-line text-sm font-semibold text-orange-900" x-text="selectedOrder?.notes"></p>
                </div>

                <div class="mt-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h4 class="text-sm font-black uppercase tracking-wide text-gray-900">Item Pesanan</h4>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-black text-gray-500" x-text="(selectedOrder?.items?.length || 0) + ' item'"></span>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-gray-100">
                        <template x-for="item in selectedOrder?.items || []" :key="item.id">
                            <div class="flex flex-col gap-3 border-b border-gray-100 p-4 last:border-b-0 sm:flex-row sm:items-start">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-gray-50 text-sm font-black text-gray-800 ring-1 ring-gray-200" x-text="item.quantity + 'x'"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="break-words text-sm font-black text-gray-900" x-text="item.menu?.name || item.menu_name || 'Menu'"></p>
                                    <p x-show="item.variant_selected" class="mt-0.5 text-xs font-semibold text-gray-500" x-text="'Varian: ' + item.variant_selected"></p>
                                    <p x-show="item.note" class="mt-1 rounded-lg bg-red-50 px-2 py-1 text-xs font-semibold text-red-600" x-text="'Catatan: ' + item.note"></p>
                                    <p class="mt-2 text-xs font-semibold text-gray-500" x-text="formatCurrency(item.price_at_time || 0) + ' / item'"></p>
                                </div>
                                <div class="text-left text-sm font-black text-gray-900 sm:text-right" x-text="formatCurrency(item.subtotal || ((item.price_at_time || 0) * (item.quantity || 0)))"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl bg-gray-50 p-4">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-bold text-gray-900" x-text="formatCurrency(orderSubtotal(selectedOrder))"></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Pajak</span>
                            <span class="font-bold text-gray-900" x-text="formatCurrency(selectedOrder?.tax_amount || 0)"></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Layanan</span>
                            <span class="font-bold text-gray-900" x-text="formatCurrency(selectedOrder?.service_charge || 0)"></span>
                        </div>
                        <div x-show="Number(selectedOrder?.discount_amount || 0) > 0" class="flex justify-between gap-4">
                            <span class="text-green-600">Diskon</span>
                            <span class="font-bold text-green-600" x-text="'- ' + formatCurrency(selectedOrder?.discount_amount || 0)"></span>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm font-black uppercase tracking-wide text-gray-600">Total Tagihan</span>
                                <span class="text-xl font-black text-primary-600" x-text="formatCurrency(selectedOrder?.total_price || 0)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-shrink-0 border-t border-gray-100 p-4 sm:p-6">
                <button @click.stop="closeDetails()" class="w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-primary-500/20 transition hover:bg-primary-700">Tutup</button>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
function orderManager() {
    return {
        orders: [],
        loading: false,
        updatingOrders: {},
        canAddOrderItems: {{ $canAddOrderItems ? 'true' : 'false' }},
        addingItem: false,
        addItemModalOpen: false,
        addItemOrder: null,
        additionalMenus: [],
        addItemForm: { menu_id: '', variant_id: '', quantity: 1, note: '' },
        detailModalOpen: false,
        selectedOrder: null,
        detailCloseTimer: null,
        statusFilter: '',
        typeFilter: '',
        search: '',
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },

        init() {
            this.fetchOrders();
            if (this.canAddOrderItems) this.fetchAdditionalMenus();

            if (window.Echo) {
                // Listen for new orders
                window.Echo.private('orders')
                    .listen('.order.created', (e) => {
                        if (e.order) {
                            const idx = this.orders.findIndex(o => o.id === e.order.id);
                            if (idx !== -1) {
                                this.orders[idx] = e.order;
                                return;
                            }

                            if (this.statusFilter === '' || this.statusFilter === 'Diterima') {
                                this.orders.unshift(e.order);
                            }
                        }
                    })
                    // Listen for status updates on existing orders
                    .listen('.order.status.updated', (e) => {
                        if (e.order) {
                            const idx = this.orders.findIndex(o => o.id === e.order.id);
                            if (idx !== -1) {
                                // If it doesn't match filter anymore, remove it
                                if(this.statusFilter && this.statusFilter !== e.order.order_status) {
                                    this.orders.splice(idx, 1);
                                } else {
                                    this.orders[idx] = e.order;
                                }
                            } else if (this.statusFilter === '' || this.statusFilter === e.order.order_status) {
                                // Add it to the list if it matches filter and wasn't there
                                this.orders.unshift(e.order);
                            }
                        }
                    })
                    .listen('.order.updated', (e) => {
                        if (!e.order) return;

                        const idx = this.orders.findIndex(o => o.id === e.order.id);
                        if (idx !== -1) {
                            this.orders[idx] = e.order;
                        } else if (this.statusFilter === '' || this.statusFilter === e.order.order_status) {
                            this.orders.unshift(e.order);
                        }

                        if (this.selectedOrder?.id === e.order.id) {
                            this.selectedOrder = e.order;
                        }
                    });
            }
        },

        async fetchAdditionalMenus() {
            try {
                const res = await fetch('/api/customer/menus', {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const json = await res.json();
                    this.additionalMenus = json.data || [];
                }
            } catch (e) {
                console.error('Gagal mengambil menu tambahan', e);
            }
        },

        async fetchOrders(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                if (this.statusFilter) params.append('order_status', this.statusFilter);
                if (this.typeFilter) params.append('order_type', this.typeFilter);
                if (this.search) params.append('search', this.search);

                const res = await fetch('/api/staff/orders?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.orders = json.data.data || json.data; 
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };
                }
            } catch (e) { console.error('Error fetching orders', e); }
            finally { this.loading = false; }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchOrders(page);
        },

        setStatus(status) {
            this.statusFilter = status;
            this.fetchOrders();
        },

        setType(type) {
            this.typeFilter = type;
            this.fetchOrders();
        },

        hasActiveFilter() {
            return this.statusFilter !== '' || this.typeFilter !== '' || this.search !== '';
        },

        clearFilters() {
            this.statusFilter = '';
            this.typeFilter = '';
            this.search = '';
            this.fetchOrders();
        },

        statusLabel(status) {
            return {
                '': 'Semua Status',
                Diterima: 'Baru',
                Diproses: 'Proses',
                Dimasak: 'Dimasak',
                Selesai: 'Siap Saji',
                Disajikan: 'Disajikan',
            }[status] || status;
        },

        typeLabel(type) {
            return {
                '': 'Semua Tipe',
                dine_in: 'Dine In',
                delivery: 'Delivery',
            }[type] || type;
        },

        isUpdatingOrder(orderId) {
            return Boolean(this.updatingOrders[orderId]);
        },

        canAddItemsToOrder(order) {
            return this.canAddOrderItems
                && order
                && !['Disajikan', 'Dibatalkan'].includes(order.order_status)
                && order.payment_status !== 'paid';
        },

        availableAdditionalMenus() {
            return this.additionalMenus.filter(menu => menu.is_available !== false && Number(menu.stock || 0) > 0);
        },

        selectedAdditionalMenu() {
            return this.additionalMenus.find(menu => String(menu.id) === String(this.addItemForm.menu_id)) || null;
        },

        selectedAdditionalMenuVariants() {
            return this.selectedAdditionalMenu()?.variants || [];
        },

        selectedAdditionalVariant() {
            return this.selectedAdditionalMenuVariants().find(variant => String(variant.id) === String(this.addItemForm.variant_id)) || null;
        },

        addItemUnitPrice() {
            const menu = this.selectedAdditionalMenu();
            if (!menu) return 0;

            return Number(menu.price || 0) + Number(this.selectedAdditionalVariant()?.extra_price || 0);
        },

        addItemTotal() {
            return this.addItemUnitPrice() * Math.max(1, Number(this.addItemForm.quantity || 1));
        },

        openAddItemModal(order) {
            if (!this.canAddItemsToOrder(order)) {
                window.restoAlert({ variant: 'info', title: 'Tidak bisa ditambah', message: 'Pesanan sudah lunas, disajikan, atau dibatalkan.' });
                return;
            }

            this.addItemOrder = order;
            this.addItemForm = { menu_id: '', variant_id: '', quantity: 1, note: '' };
            this.addItemModalOpen = true;

            if (this.additionalMenus.length === 0) this.fetchAdditionalMenus();
        },

        closeAddItemModal() {
            this.addItemModalOpen = false;
            this.addItemOrder = null;
            this.addItemForm = { menu_id: '', variant_id: '', quantity: 1, note: '' };
        },

        async submitAdditionalItem() {
            if (!this.addItemOrder || this.addingItem) return;

            const menu = this.selectedAdditionalMenu();
            if (!menu) return;

            this.addingItem = true;
            const variant = this.selectedAdditionalVariant();

            try {
                const res = await fetch(`/api/staff/orders/${this.addItemOrder.id}/items`, {
                    method: 'POST',
                    headers: {
                        ...window.restoAuthHeaders(),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        items: [{
                            menu_id: Number(this.addItemForm.menu_id),
                            quantity: Math.max(1, Number(this.addItemForm.quantity || 1)),
                            variant_id: variant?.id || null,
                            note: this.addItemForm.note || null
                        }]
                    })
                });

                const json = await res.json();
                if (res.ok) {
                    const updatedOrder = json.data;
                    const idx = this.orders.findIndex(order => order.id === updatedOrder.id);
                    if (idx !== -1) this.orders[idx] = updatedOrder;
                    if (this.selectedOrder?.id === updatedOrder.id) this.selectedOrder = updatedOrder;
                    this.closeAddItemModal();
                    await window.restoAlert({ variant: 'success', title: 'Item ditambahkan', message: 'Tagihan pesanan sudah diperbarui.' });
                } else {
                    const message = json.message || Object.values(json.errors || {}).flat().join('\n') || 'Item tidak bisa ditambahkan.';
                    await window.restoAlert({ variant: 'danger', title: 'Gagal tambah item', message });
                }
            } catch (e) {
                console.error('Error adding item', e);
                await window.restoAlert({ variant: 'danger', title: 'Gagal tambah item', message: 'Tidak bisa terhubung ke server.' });
            } finally {
                this.addingItem = false;
            }
        },

        async updateStatus(orderId, newStatus) {
            if (this.isUpdatingOrder(orderId)) return;

            this.updatingOrders = { ...this.updatingOrders, [orderId]: true };

            try {
                const res = await fetch(`/api/staff/orders/${orderId}/status`, {
                    method: 'PATCH',
                    headers: { 
                        ...window.restoAuthHeaders(), 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                if(res.ok) {
                    // Update locally if echo isn't fast enough
                    const idx = this.orders.findIndex(o => o.id === orderId);
                    if(idx !== -1) {
                        if(this.statusFilter && this.statusFilter !== newStatus) {
                            this.orders.splice(idx, 1);
                        } else {
                            this.orders[idx].order_status = newStatus;
                        }
                    }
                } else {
                    await window.restoAlert({ variant: 'danger', title: 'Gagal update status', message: 'Status pesanan tidak dapat diperbarui.' });
                }
            } catch (e) {
                console.error('Error updating status', e);
            } finally {
                const { [orderId]: _finished, ...remaining } = this.updatingOrders;
                this.updatingOrders = remaining;
            }
        },

        viewDetails(order) {
            const openDetail = () => {
                if (this.detailCloseTimer) {
                    window.clearTimeout(this.detailCloseTimer);
                    this.detailCloseTimer = null;
                }

                this.selectedOrder = order;
                this.detailModalOpen = true;
            };

            if (typeof window.restoCloseDialog === 'function') {
                const hadGlobalDialog = window.restoCloseDialog();
                if (hadGlobalDialog) {
                    window.setTimeout(openDetail, 180);
                    return;
                }
            }

            openDetail();
        },

        closeDetails() {
            if (!this.detailModalOpen) return;

            this.detailModalOpen = false;
            if (this.detailCloseTimer) {
                window.clearTimeout(this.detailCloseTimer);
            }

            this.detailCloseTimer = window.setTimeout(() => {
                if (!this.detailModalOpen) {
                    this.selectedOrder = null;
                }
                this.detailCloseTimer = null;
            }, 220);
        },

        orderTypeLabel(type) {
            return {
                dine_in: 'Dine In',
                delivery: 'Delivery',
            }[type] || '-';
        },

        paymentStatusLabel(status) {
            return {
                pending: 'Belum Bayar',
                paid: 'Lunas',
                failed: 'Gagal',
            }[status] || status || '-';
        },

        paymentMethodLabel(method) {
            return {
                cash: 'Tunai',
                qris: 'QRIS',
                card: 'Kartu',
            }[method] || '-';
        },

        orderSubtotal(order) {
            return (order?.items || []).reduce((sum, item) => {
                return sum + Number(item.subtotal || (Number(item.price_at_time || 0) * Number(item.quantity || 0)));
            }, 0);
        },

        getStatusHeaderClass(status) {
            switch(status) {
                case 'Diterima': return 'bg-yellow-50';
                case 'Diproses': return 'bg-blue-50';
                case 'Dimasak': return 'bg-orange-50';
                case 'Selesai': return 'bg-green-50';
                case 'Disajikan': return 'bg-gray-100';
                default: return 'bg-white';
            }
        },

        getStatusBadgeClass(status) {
            switch(status) {
                case 'Diterima': return 'bg-yellow-100 text-yellow-800';
                case 'Diproses': return 'bg-blue-100 text-blue-800';
                case 'Dimasak': return 'bg-orange-100 text-orange-800';
                case 'Selesai': return 'bg-green-100 text-green-800';
                case 'Disajikan': return 'bg-gray-200 text-gray-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
        },
        
        formatTime(isoString) {
            const d = new Date(isoString);
            return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
        },

        formatDateTime(isoString) {
            if (!isoString) return '-';

            return new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }).format(new Date(isoString));
        }
    }
}
</script>
@endpush
