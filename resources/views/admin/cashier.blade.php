@extends('layouts.admin')

@section('title', 'Kasir')

@section('content')
<div x-data="cashierManager()" x-init="init()" class="space-y-6">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="space-y-4">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.9fr)]">
                    <div class="space-y-3">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                            <div class="rounded-2xl bg-gray-100 p-1">
                                <div class="grid grid-cols-3 gap-1">
                                    <button @click="setPaymentStatus('pending')" class="min-h-11 min-w-0 rounded-xl px-2 text-center text-xs font-black transition sm:px-4 sm:text-sm" :class="paymentStatus === 'pending' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'"><span class="block truncate sm:whitespace-nowrap">Belum Bayar</span></button>
                                    <button @click="setPaymentStatus('paid')" class="min-h-11 min-w-0 rounded-xl px-2 text-center text-xs font-black transition sm:px-4 sm:text-sm" :class="paymentStatus === 'paid' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'"><span class="block truncate sm:whitespace-nowrap">Terbayar</span></button>
                                    <button @click="setPaymentStatus('')" class="min-h-11 min-w-0 rounded-xl px-2 text-center text-xs font-black transition sm:px-4 sm:text-sm" :class="paymentStatus === '' ? 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' : 'text-gray-500 hover:text-gray-800'"><span class="block truncate sm:whitespace-nowrap">Semua</span></button>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-gray-100 bg-white p-1 shadow-sm">
                                <div class="grid grid-cols-4 gap-1">
                                    <button @click="setPaymentMethod('')" class="min-h-11 rounded-xl px-3 text-xs font-black uppercase tracking-wide transition" :class="paymentMethod === '' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">Semua</button>
                                    <button @click="setPaymentMethod('cash')" class="min-h-11 rounded-xl px-3 text-xs font-black uppercase tracking-wide transition" :class="paymentMethod === 'cash' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">Tunai</button>
                                    <button @click="setPaymentMethod('qris')" class="min-h-11 rounded-xl px-3 text-xs font-black uppercase tracking-wide transition" :class="paymentMethod === 'qris' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">QRIS</button>
                                    <button @click="setPaymentMethod('card')" class="min-h-11 rounded-xl px-3 text-xs font-black uppercase tracking-wide transition" :class="paymentMethod === 'card' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">Kartu</button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-gray-500">
                            <span class="rounded-full bg-gray-100 px-3 py-1" x-text="paymentStatusLabel(paymentStatus || 'all')"></span>
                            <span class="rounded-full bg-gray-100 px-3 py-1" x-text="paymentMethodLabel(paymentMethod)"></span>
                            <button x-show="hasActiveFilter()" @click="clearFilters()" class="rounded-full bg-primary-50 px-3 py-1 font-black text-primary-700 hover:bg-primary-100">Reset filter</button>
                        </div>
                    </div>

                    <div class="flex min-w-0 gap-3">
                        <div class="flex h-14 flex-1 items-center rounded-2xl border border-gray-200 bg-white shadow-sm transition focus-within:border-primary-500 focus-within:ring-4 focus-within:ring-primary-50">
                            <div class="flex h-full w-12 flex-shrink-0 items-center justify-center text-gray-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" x-model="search" @input.debounce.400ms="fetchOrders()" placeholder="Cari order #1024, meja A1, atau nama pelanggan" class="h-full min-w-0 flex-1 border-0 bg-transparent px-0 text-sm font-semibold text-gray-900 outline-none placeholder:text-gray-400 focus:border-0 focus:outline-none focus:ring-0">
                            <button x-show="search" @click="search = ''; fetchOrders()" class="mr-2 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" title="Bersihkan pencarian">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <button @click="fetchOrders()" class="inline-flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-primary-600" title="Refresh">
                            <svg class="h-5 w-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="hidden grid-cols-12 gap-4 border-b border-gray-100 bg-gray-50 px-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-500 lg:grid">
                    <div class="col-span-3">Order</div>
                    <div class="col-span-2">Meja</div>
                    <div class="col-span-2">Metode</div>
                    <div class="col-span-2">Status</div>
                    <div class="col-span-2 text-right">Total</div>
                    <div class="col-span-1 text-right">Aksi</div>
                </div>

                <template x-for="order in orders" :key="order.id">
                    <div @click="selectOrder(order)" @keydown.enter.prevent="selectOrder(order)" @keydown.space.prevent="selectOrder(order)" role="button" tabindex="0" class="grid w-full cursor-pointer grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-4 border-b border-gray-100 px-4 py-4 text-left transition hover:bg-primary-50/40 focus:outline-none focus:ring-2 focus:ring-primary-500/30 lg:grid-cols-12 lg:items-center lg:gap-4" :class="selectedOrder?.id === order.id ? 'bg-primary-50' : 'bg-white'">
                        <div class="min-w-0 lg:col-span-3">
                            <div class="break-words font-black leading-tight text-gray-900 lg:truncate">#<span x-text="order.order_number || order.id"></span></div>
                            <div class="text-xs text-gray-500" x-text="formatDateTime(order.created_at)"></div>
                        </div>
                        <div class="min-w-0 lg:col-span-2">
                            <div class="text-[10px] font-black uppercase tracking-wide text-gray-400 lg:hidden">Meja</div>
                            <div class="text-sm font-semibold text-gray-700" x-text="order.table?.table_number ? 'Meja ' + order.table.table_number : '-'"></div>
                        </div>
                        <div class="min-w-0 lg:col-span-2">
                            <div class="text-[10px] font-black uppercase tracking-wide text-gray-400 lg:hidden">Metode</div>
                            <span class="inline-flex rounded-lg bg-gray-100 px-2 py-1 text-xs font-bold uppercase text-gray-600" x-text="paymentMethodLabel(order.payment_method)"></span>
                        </div>
                        <div class="min-w-0 lg:col-span-2">
                            <div class="text-[10px] font-black uppercase tracking-wide text-gray-400 lg:hidden">Status</div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold" :class="paymentStatusClass(order.payment_status)" x-text="paymentStatusLabel(order.payment_status)"></span>
                        </div>
                        <div class="min-w-0 lg:col-span-2 lg:text-right">
                            <div class="text-[10px] font-black uppercase tracking-wide text-gray-400 lg:hidden">Total</div>
                            <div class="font-black text-gray-900" x-text="formatCurrency(order.total_price)"></div>
                        </div>
                        <div class="col-start-2 row-span-2 row-start-1 flex items-center justify-end text-primary-600 lg:col-span-1 lg:col-start-auto lg:row-auto lg:block lg:text-right">
                            <svg class="ml-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </template>

                <div x-show="orders.length === 0 && !loading" class="px-6 py-16 text-center">
                    <svg class="mx-auto mb-3 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                    <p class="font-bold text-gray-900">Tidak ada transaksi</p>
                    <p class="mt-1 text-sm text-gray-500">Data akan muncul sesuai filter yang dipilih.</p>
                </div>

                <div x-show="loading && orders.length === 0" class="px-6 py-16 text-center">
                    <svg class="mx-auto h-8 w-8 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
            </div>

            <x-admin-pagination label="transaksi" />
        </section>

        <aside class="rounded-2xl border border-gray-100 bg-white shadow-sm xl:sticky xl:top-6 xl:self-start">
            <template x-if="!selectedOrder">
                <div class="p-8 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14l2 2 4-4m5 2a8 8 0 11-16 0 8 8 0 0116 0z"></path></svg>
                    </div>
                    <p class="font-black text-gray-900">Pilih transaksi</p>
                    <p class="mt-1 text-sm text-gray-500">Detail tagihan dan aksi pembayaran akan tampil di sini.</p>
                </div>
            </template>

            <template x-if="selectedOrder">
                <div class="p-4 sm:p-5">
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-bold uppercase tracking-wider text-gray-500">Tagihan</div>
                            <h2 class="mt-1 break-words text-xl font-black leading-tight text-gray-900 sm:text-2xl">#<span x-text="selectedOrder.order_number || selectedOrder.id"></span></h2>
                            <p class="text-sm text-gray-500" x-text="formatDateTime(selectedOrder.created_at)"></p>
                        </div>
                        <span class="flex-shrink-0 rounded-full px-3 py-1 text-xs font-black" :class="paymentStatusClass(selectedOrder.payment_status)" x-text="paymentStatusLabel(selectedOrder.payment_status)"></span>
                    </div>

                    <div class="mb-5 rounded-2xl bg-gray-50 p-4">
                        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <div class="text-xs font-bold uppercase text-gray-400">Meja</div>
                                <div class="font-bold text-gray-900" x-text="selectedOrder.table?.table_number || '-'"></div>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase text-gray-400">Metode</div>
                                <div class="font-bold text-gray-900" x-text="paymentMethodLabel(selectedOrder.payment_method)"></div>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase text-gray-400">Tipe</div>
                                <div class="font-bold text-gray-900" x-text="selectedOrder.order_type === 'dine_in' ? 'Dine In' : 'Delivery'"></div>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase text-gray-400">Status Order</div>
                                <div class="font-bold text-gray-900" x-text="selectedOrder.order_status"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5 space-y-3">
                        <template x-for="item in selectedOrder.items" :key="item.id">
                            <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-3">
                                <div class="min-w-0">
                                    <div class="font-bold text-gray-900"><span x-text="item.quantity"></span>x <span x-text="item.menu_name || item.menu?.name"></span></div>
                                    <div x-show="item.variant_selected" class="text-xs text-gray-500" x-text="'Varian: ' + item.variant_selected"></div>
                                    <div x-show="item.note" class="text-xs italic text-primary-600" x-text="'Catatan: ' + item.note"></div>
                                </div>
                                <div class="flex-shrink-0 text-right text-sm font-bold text-gray-900" x-text="formatCurrency(item.subtotal || (item.price_at_time * item.quantity))"></div>
                            </div>
                        </template>
                    </div>

                    <div class="mb-5 space-y-2 rounded-2xl bg-gray-900 p-4 text-white">
                        <div class="flex justify-between text-sm text-gray-300">
                            <span>Subtotal</span>
                            <span x-text="formatCurrency(orderSubtotal(selectedOrder))"></span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-300">
                            <span>Pajak</span>
                            <span x-text="formatCurrency(selectedOrder.tax_amount || 0)"></span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-300">
                            <span>Layanan</span>
                            <span x-text="formatCurrency(selectedOrder.service_charge || 0)"></span>
                        </div>
                        <div class="flex justify-between text-sm text-green-300">
                            <span>Diskon</span>
                            <span x-text="'-' + formatCurrency(selectedOrder.discount_amount || 0)"></span>
                        </div>
                        <div class="border-t border-white/10 pt-3">
                            <div class="flex items-end justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Bayar</span>
                                <span class="text-2xl font-black" x-text="formatCurrency(selectedOrder.total_price)"></span>
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedOrder.payment_status !== 'paid'" class="mb-5">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Metode Pembayaran</label>
                        <div class="grid grid-cols-3 gap-2 rounded-2xl border border-gray-100 bg-white p-1">
                            <button type="button" @click="selectedPaymentMethod = 'cash'" class="min-h-11 rounded-xl px-2 text-xs font-black uppercase transition" :class="selectedPaymentMethod === 'cash' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">Tunai</button>
                            <button type="button" @click="selectedPaymentMethod = 'qris'" class="min-h-11 rounded-xl px-2 text-xs font-black uppercase transition" :class="selectedPaymentMethod === 'qris' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">QRIS</button>
                            <button type="button" @click="selectedPaymentMethod = 'card'" class="min-h-11 rounded-xl px-2 text-xs font-black uppercase transition" :class="selectedPaymentMethod === 'card' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">Kartu</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button @click="printReceipt()" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700 transition hover:bg-gray-50">Cetak Struk</button>
                        <button @click="confirmPayment(selectedOrder)" :disabled="selectedOrder.payment_status === 'paid' || confirming" class="rounded-xl bg-primary-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-primary-500/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-text="confirming ? 'Memproses...' : (selectedOrder.payment_status === 'paid' ? 'Sudah Lunas' : 'Konfirmasi ' + paymentMethodLabel(selectedPaymentMethod))"></span>
                        </button>
                    </div>
                </div>
            </template>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cashierManager() {
    return {
        orders: [],
        selectedOrder: null,
        loading: false,
        confirming: false,
        restaurantName: @json(\App\Models\SystemSetting::getValue('restaurant_name', 'RestoApp')),
        restaurantAddress: @json(\App\Models\SystemSetting::getValue('restaurant_address', '')),
        restaurantPhone: @json(\App\Models\SystemSetting::getValue('restaurant_phone', '')),
        receiptSlogan: @json(\App\Models\SystemSetting::getValue('receipt_slogan', 'Terima kasih')),
        printerSettings: {
            thermal_print_method: 'browser',
            thermal_printer_connection: 'windows',
            thermal_printer_name: '',
            thermal_printer_share_name: '',
            thermal_printer_ip: '',
            thermal_printer_port: 9100,
            thermal_printer_bluetooth_port: '',
            print_bridge_url: '',
            print_bridge_token: ''
        },
        paymentStatus: 'pending',
        paymentMethod: '',
        selectedPaymentMethod: 'cash',
        search: '',
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },

        init() {
            this.fetchOrders();

            if (window.Echo) {
                window.Echo.private('orders')
                    .listen('.order.updated', (e) => {
                        if (!e.order) return;

                        const idx = this.orders.findIndex(order => order.id === e.order.id);
                        if (idx !== -1) {
                            this.orders[idx] = e.order;
                        }

                        if (this.selectedOrder?.id === e.order.id) {
                            this.selectedOrder = e.order;
                            this.selectedPaymentMethod = e.order.payment_method || 'cash';
                        }
                    });
            }
        },

        async fetchOrders(page = 1) {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    page,
                    per_page: this.pagination.per_page,
                });

                if (this.paymentStatus) params.append('payment_status', this.paymentStatus);
                if (this.paymentMethod) params.append('payment_method', this.paymentMethod);
                if (this.search) params.append('search', this.search);

                const res = await fetch('/api/staff/orders?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });

                if (res.ok) {
                    const json = await res.json();
                    this.orders = json.data.data || json.data;
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };

                    if (this.selectedOrder) {
                        this.selectedOrder = this.orders.find(order => order.id === this.selectedOrder.id) || this.selectedOrder;
                        this.selectedPaymentMethod = this.selectedOrder.payment_method || 'cash';
                    }
                }
            } catch (e) {
                console.error('Gagal memuat transaksi kasir', e);
            } finally {
                this.loading = false;
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchOrders(page);
        },

        selectOrder(order) {
            this.selectedOrder = order;
            this.selectedPaymentMethod = order.payment_method || 'cash';
        },

        setPaymentStatus(status) {
            this.paymentStatus = status;
            this.fetchOrders();
        },

        setPaymentMethod(method) {
            this.paymentMethod = method;
            this.fetchOrders();
        },

        hasActiveFilter() {
            return this.paymentStatus !== 'pending' || this.paymentMethod !== '' || this.search !== '';
        },

        clearFilters() {
            this.paymentStatus = 'pending';
            this.paymentMethod = '';
            this.search = '';
            this.fetchOrders();
        },

        async confirmPayment(order) {
            const paymentMethod = this.selectedPaymentMethod || order.payment_method || 'cash';
            const confirmed = await window.restoConfirm({
                variant: 'success',
                title: `Konfirmasi pembayaran ${this.paymentMethodLabel(paymentMethod)}?`,
                message: `Order #${order.order_number || order.id} akan ditandai lunas sebesar ${this.formatCurrency(order.total_price)}.`,
                confirmText: 'Konfirmasi',
                cancelText: 'Batal'
            });

            if (!confirmed) return;

            this.confirming = true;

            try {
                const res = await fetch(`/api/staff/orders/${order.id}/payment/confirm`, {
                    method: 'POST',
                    headers: window.restoAuthHeaders({
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }),
                    body: JSON.stringify({ payment_method: paymentMethod })
                });

                const json = await res.json().catch(() => ({}));

                if (!res.ok) {
                    await window.restoAlert({ variant: 'danger', title: 'Gagal konfirmasi pembayaran', message: json.message || 'Pembayaran tidak dapat dikonfirmasi.' });
                    return;
                }

                order.payment_status = 'paid';
                order.payment_method = json.data?.payment_method || paymentMethod;
                order.order_status = json.data?.order_status || 'Diproses';
                this.selectedOrder = order;

                await window.restoAlert({ variant: 'success', title: 'Pembayaran lunas', message: `Order #${order.order_number || order.id} berhasil ditandai lunas dengan ${this.paymentMethodLabel(order.payment_method)}.` });
                this.fetchOrders(this.pagination.current_page);
            } catch (e) {
                await window.restoAlert({ variant: 'danger', title: 'Gagal konfirmasi pembayaran', message: 'Terjadi kesalahan saat memproses pembayaran.' });
            } finally {
                this.confirming = false;
            }
        },

        async fetchReceiptSettings() {
            try {
                const res = await fetch(`/api/staff/settings/receipt?t=${Date.now()}`, {
                    cache: 'no-store',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if (!res.ok) return;

                const json = await res.json();
                const data = json.data || {};
                this.restaurantName = data.restaurant_name ?? this.restaurantName;
                this.restaurantAddress = data.restaurant_address ?? this.restaurantAddress;
                this.restaurantPhone = data.restaurant_phone ?? this.restaurantPhone;
                this.receiptSlogan = data.receipt_slogan ?? this.receiptSlogan;
                this.printerSettings = {
                    ...this.printerSettings,
                    thermal_print_method: data.thermal_print_method ?? this.printerSettings.thermal_print_method,
                    thermal_printer_connection: data.thermal_printer_connection ?? this.printerSettings.thermal_printer_connection,
                    thermal_printer_name: data.thermal_printer_name ?? this.printerSettings.thermal_printer_name,
                    thermal_printer_share_name: data.thermal_printer_share_name ?? this.printerSettings.thermal_printer_share_name,
                    thermal_printer_ip: data.thermal_printer_ip ?? this.printerSettings.thermal_printer_ip,
                    thermal_printer_port: data.thermal_printer_port ?? this.printerSettings.thermal_printer_port,
                    thermal_printer_bluetooth_port: data.thermal_printer_bluetooth_port ?? this.printerSettings.thermal_printer_bluetooth_port,
                    print_bridge_url: data.print_bridge_url ?? this.printerSettings.print_bridge_url,
                    print_bridge_token: data.print_bridge_token ?? this.printerSettings.print_bridge_token
                };
            } catch (e) {
                console.error('Gagal memuat pengaturan struk', e);
            }
        },

        async printReceipt() {
            if (!this.selectedOrder) return;

            await this.fetchReceiptSettings();

            const receipt = this.buildReceiptPayload(this.selectedOrder);
            const method = this.printerSettings.thermal_print_method || 'browser';

            if (method !== 'browser') {
                await this.printViaBridge(receipt);
                return;
            }

            this.printViaBrowser(receipt);
        },

        buildReceiptPayload(order) {
            const subtotal = this.orderSubtotal(order);
            const discount = Number(order.discount_amount || 0);
            const tax = Number(order.tax_amount || 0);
            const serviceCharge = Number(order.service_charge || 0);

            return {
                restaurant: {
                    name: this.restaurantName || 'RestoApp',
                    address: this.restaurantAddress || '',
                    phone: this.restaurantPhone || '',
                    slogan: this.receiptSlogan || 'Terima kasih'
                },
                order: {
                    id: order.id,
                    order_number: order.order_number || order.id,
                    created_at: order.created_at,
                    created_at_label: this.formatDateTime(order.created_at),
                    payment_method: order.payment_method,
                    payment_status: order.payment_status
                },
                items: (order.items || []).map(item => ({
                    quantity: Number(item.quantity || 0),
                    name: item.menu_name || item.menu?.name || '-',
                    variant_selected: item.variant_selected || '',
                    subtotal: Number(item.subtotal || (Number(item.price_at_time || 0) * Number(item.quantity || 0))),
                })),
                totals: {
                    subtotal,
                    tax,
                    service_charge: serviceCharge,
                    discount,
                    total: Number(order.total_price || 0)
                }
            };
        },

        printViaBrowser(receipt) {
            const printWindow = window.open('', '_blank', 'width=420,height=640');
            if (!printWindow) {
                window.restoAlert({ variant: 'danger', title: 'Popup print diblokir', message: 'Izinkan popup browser untuk mencetak struk.' });
                return;
            }

            printWindow.document.write(this.receiptHtml(receipt));
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        },

        async printViaBridge(receipt) {
            const url = String(this.printerSettings.print_bridge_url || '').trim();
            if (!url) {
                await window.restoAlert({
                    variant: 'danger',
                    title: 'Print bridge belum diatur',
                    message: 'Isi URL Print Bridge di Pengaturan untuk metode Windows, ESC/POS, USB, IP, atau Bluetooth.'
                });
                return;
            }

            const payload = {
                driver: this.printerSettings.thermal_print_method,
                connection: this.printerSettings.thermal_printer_connection,
                printer: {
                    name: this.printerSettings.thermal_printer_name,
                    share_name: this.printerSettings.thermal_printer_share_name,
                    ip: this.printerSettings.thermal_printer_ip,
                    port: Number(this.printerSettings.thermal_printer_port || 9100),
                    bluetooth_port: this.printerSettings.thermal_printer_bluetooth_port
                },
                receipt,
                html: this.receiptHtml(receipt),
                escpos_text: this.receiptEscposText(receipt),
                escpos_base64: this.receiptEscposBase64(receipt)
            };

            try {
                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                };
                if (this.printerSettings.print_bridge_token) {
                    headers['X-Print-Token'] = this.printerSettings.print_bridge_token;
                }

                const res = await fetch(url, {
                    method: 'POST',
                    headers,
                    body: JSON.stringify(payload)
                });

                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    await window.restoAlert({ variant: 'danger', title: 'Gagal print', message: json.message || 'Print bridge menolak permintaan cetak.' });
                    return;
                }

                await window.restoAlert({ variant: 'success', title: 'Struk dikirim', message: 'Data struk sudah dikirim ke print bridge.' });
            } catch (e) {
                await window.restoAlert({ variant: 'danger', title: 'Print bridge tidak tersambung', message: 'Pastikan aplikasi print bridge lokal berjalan dan URL sudah benar.' });
            }
        },

        receiptHtml(receipt) {
            const restaurantName = this.escapeHtml(receipt.restaurant.name);
            const restaurantAddress = this.escapeHtml(receipt.restaurant.address);
            const restaurantPhone = this.escapeHtml(receipt.restaurant.phone);
            const receiptSlogan = this.escapeHtml(receipt.restaurant.slogan);
            const items = receipt.items.map(item => `
                <tr>
                    <td>${this.escapeHtml(item.quantity)}x ${this.escapeHtml(item.name)}</td>
                    <td style="text-align:right">${this.formatCurrency(item.subtotal)}</td>
                </tr>
                ${item.variant_selected ? `<tr><td colspan="2" style="font-size:11px;color:#555">Varian: ${this.escapeHtml(item.variant_selected)}</td></tr>` : ''}
            `).join('');

            return `
                <html>
                    <head>
                        <title>Struk #${this.escapeHtml(receipt.order.order_number)}</title>
                        <style>
                            body { font-family: Arial, sans-serif; width: 300px; margin: 0 auto; padding: 16px; color: #111; }
                            h1 { font-size: 18px; text-align: center; margin: 0 0 4px; }
                            .muted { color: #555; font-size: 12px; text-align: center; }
                            table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
                            td { padding: 4px 0; vertical-align: top; }
                            .summary { border-top: 1px dashed #111; margin-top: 10px; padding-top: 10px; font-size: 13px; }
                            .row { display: flex; justify-content: space-between; gap: 12px; padding: 3px 0; }
                            .discount { color: #047857; }
                            .total { border-top: 1px dashed #111; margin-top: 6px; padding-top: 8px; font-size: 15px; font-weight: 700; }
                        </style>
                    </head>
                    <body>
                        <h1>${restaurantName}</h1>
                        ${restaurantAddress ? `<div class="muted">${restaurantAddress}</div>` : ''}
                        ${restaurantPhone ? `<div class="muted">Telp: ${restaurantPhone}</div>` : ''}
                        <div class="muted" style="margin-top:8px">Order #${this.escapeHtml(receipt.order.order_number)}</div>
                        <div class="muted">${this.escapeHtml(receipt.order.created_at_label)}</div>
                        <table>${items}</table>
                        <div class="summary">
                            <div class="row"><span>Subtotal</span><span>${this.formatCurrency(receipt.totals.subtotal)}</span></div>
                            <div class="row"><span>Pajak</span><span>${this.formatCurrency(receipt.totals.tax)}</span></div>
                            <div class="row"><span>Layanan</span><span>${this.formatCurrency(receipt.totals.service_charge)}</span></div>
                            <div class="row discount"><span>Diskon</span><span>-${this.formatCurrency(receipt.totals.discount)}</span></div>
                            <div class="row total"><span>Total</span><span>${this.formatCurrency(receipt.totals.total)}</span></div>
                        </div>
                        ${receiptSlogan ? `<p class="muted" style="margin-top:18px">${receiptSlogan}</p>` : ''}
                    </body>
                </html>
            `;
        },

        receiptEscposText(receipt) {
            const width = 32;
            const line = '-'.repeat(width);
            const center = (text) => {
                text = String(text || '').slice(0, width);
                const left = Math.max(0, Math.floor((width - text.length) / 2));
                return ' '.repeat(left) + text;
            };
            const row = (left, right) => {
                left = String(left || '');
                right = String(right || '');
                const space = Math.max(1, width - left.length - right.length);
                return `${left}${' '.repeat(space)}${right}`;
            };
            const lines = [
                center(receipt.restaurant.name),
                receipt.restaurant.address ? center(receipt.restaurant.address) : '',
                receipt.restaurant.phone ? center(`Telp: ${receipt.restaurant.phone}`) : '',
                '',
                center(`Order #${receipt.order.order_number}`),
                center(receipt.order.created_at_label),
                ''
            ].filter(line => line !== null);

            receipt.items.forEach(item => {
                lines.push(row(`${item.quantity}x ${item.name}`.slice(0, 20), this.formatCurrency(item.subtotal)));
                if (item.variant_selected) {
                    lines.push(`Varian: ${item.variant_selected}`.slice(0, width));
                }
            });

            lines.push(line);
            lines.push(row('Subtotal', this.formatCurrency(receipt.totals.subtotal)));
            lines.push(row('Pajak', this.formatCurrency(receipt.totals.tax)));
            lines.push(row('Layanan', this.formatCurrency(receipt.totals.service_charge)));
            lines.push(row('Diskon', `-${this.formatCurrency(receipt.totals.discount)}`));
            lines.push(line);
            lines.push(row('Total', this.formatCurrency(receipt.totals.total)));
            lines.push('');
            if (receipt.restaurant.slogan) lines.push(center(receipt.restaurant.slogan));
            lines.push('', '', '');

            return lines.join('\n');
        },

        receiptEscposBase64(receipt) {
            const encoder = new TextEncoder();
            const bytes = [
                0x1b, 0x40,
                ...encoder.encode(this.receiptEscposText(receipt)),
                0x0a, 0x0a, 0x0a,
                0x1d, 0x56, 0x00
            ];

            let binary = '';
            bytes.forEach(byte => {
                binary += String.fromCharCode(byte);
            });

            return btoa(binary);
        },

        paymentMethodLabel(method) {
            return { cash: 'Tunai', qris: 'QRIS', card: 'Kartu' }[method] || 'Belum pilih';
        },

        paymentStatusLabel(status) {
            return { pending: 'Belum Bayar', paid: 'Lunas', failed: 'Gagal', all: 'Semua Status' }[status] || status;
        },

        paymentStatusClass(status) {
            return {
                pending: 'bg-yellow-100 text-yellow-700',
                paid: 'bg-green-100 text-green-700',
                failed: 'bg-red-100 text-red-700'
            }[status] || 'bg-gray-100 text-gray-700';
        },

        orderSubtotal(order) {
            return (order?.items || []).reduce((sum, item) => {
                return sum + Number(item.subtotal || (Number(item.price_at_time || 0) * Number(item.quantity || 0)));
            }, 0);
        },

        escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(val || 0));
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
        }
    }
}
</script>
@endpush
