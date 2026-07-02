@extends('layouts.admin')

@section('title', 'Dashboard Utama')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div x-data="dashboard()" x-init="fetchData()" class="space-y-6">

    {{-- Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Pendapatan -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-full bg-green-50 text-green-500 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-green-500 bg-green-50 px-2.5 py-1 rounded-full">Hari ini</span>
            </div>
            <h3 class="text-gray-500 text-sm font-medium">Pendapatan Hari Ini</h3>
            <p class="text-2xl font-bold text-gray-800 mt-1" x-text="formatCurrency(metrics.revenue)"></p>
        </div>

        <!-- Pesanan -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-500 text-sm font-medium">Pesanan Dibayar Hari Ini</h3>
            <p class="text-2xl font-bold text-gray-800 mt-1" x-text="metrics.orders"></p>
        </div>

        <!-- Pelanggan -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-500 text-sm font-medium">Pelanggan Hari Ini</h3>
            <p class="text-2xl font-bold text-gray-800 mt-1" x-text="metrics.customers"></p>
        </div>

        <!-- Stok Kritis -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-full bg-red-50 text-red-500 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-500 text-sm font-medium">Stok Kritis</h3>
            <p class="text-2xl font-bold text-gray-800 mt-1" x-text="metrics.critical_stock"></p>
        </div>
    </div>

    {{-- Charts and Realtime Orders --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart Area -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Pendapatan Per Jam</h3>
            <div class="relative h-72 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Realtime Orders -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Pesanan Masuk</h3>
                <span class="flex h-3 w-3 relative">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            
            <div class="flex-1 overflow-y-auto pr-2 space-y-3">
                <template x-for="order in recentOrders" :key="order.id">
                    <div class="p-4 rounded-xl border border-gray-100 hover:border-primary-100 hover:bg-primary-50 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="font-bold text-gray-800" x-text="'#' + (order.order_number || order.id)"></span>
                                <span class="text-xs text-gray-500 ml-2" x-text="formatTime(order.created_at)"></span>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800" x-text="order.status"></span>
                        </div>
                        <div class="text-sm text-gray-600 mb-2" x-text="(order.items_count || 0) + ' items - Meja ' + order.table_number"></div>
                        <div class="font-bold text-primary-600" x-text="formatCurrency(order.total)"></div>
                    </div>
                </template>
                <div x-show="recentOrders.length === 0" class="text-center text-gray-500 py-8 text-sm">
                    Belum ada pesanan baru
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboard() {
    return {
        metrics: {
            revenue: 0,
            orders: 0,
            customers: 0,
            critical_stock: 0
        },
        hourlyRevenue: [],
        recentOrders: [],
        chartInstance: null,
        loading: false,
        echoListening: false,

        async fetchData() {
            this.loading = true;
            try {
                await Promise.all([
                    this.fetchMetrics(),
                    this.fetchHourlyRevenue(),
                    this.fetchRecentOrders()
                ]);

                this.initChart();
                this.listenForOrders();
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchMetrics() {
            const res = await fetch('/api/admin/reports/dashboard', {
                headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
            });
            if (!res.ok) return;

            const json = await res.json();
            const data = json.data || {};
            this.metrics = {
                revenue: Number(data.today_revenue || 0),
                orders: Number(data.today_orders || 0),
                customers: Number(data.today_customers || 0),
                critical_stock: Number(data.critical_stock_count || 0)
            };
        },

        async fetchHourlyRevenue() {
            const res = await fetch('/api/admin/reports/hourly-revenue', {
                headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
            });
            if (!res.ok) return;

            const json = await res.json();
            this.hourlyRevenue = Array.isArray(json.data) ? json.data : [];
            if (this.chartInstance) this.initChart();
        },

        async fetchRecentOrders() {
            const params = new URLSearchParams({ per_page: 5 });
            const res = await fetch('/api/staff/orders?' + params.toString(), {
                headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
            });
            if (!res.ok) return;

            const json = await res.json();
            this.recentOrders = (json.data.data || json.data || []).map(order => this.normalizeOrder(order));
        },

        listenForOrders() {
            if (!window.Echo || this.echoListening) return;
            this.echoListening = true;

            window.Echo.private('orders')
                .listen('.order.created', (e) => {
                    if (!e.order) return;

                    this.upsertRecentOrder(this.normalizeOrder(e.order));
                    this.fetchMetrics();
                    this.fetchHourlyRevenue();
                })
                .listen('.order.status.updated', (e) => {
                    if (!e.order) return;

                    this.upsertRecentOrder(this.normalizeOrder(e.order));
                    this.fetchMetrics();
                    this.fetchHourlyRevenue();
                });
        },

        normalizeOrder(order) {
            const normalized = {
                id: order.id,
                order_number: order.order_number ?? null,
                created_at: order.created_at || order.updated_at || null,
                status: order.order_status || order.status || null,
                table_number: order.table?.table_number || order.table_number || order.table_id || null,
            };

            if (Array.isArray(order.items)) {
                normalized.items_count = order.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
            } else if (order.items_count !== undefined) {
                normalized.items_count = Number(order.items_count || 0);
            }

            if (order.total_price !== undefined || order.total !== undefined) {
                normalized.total = Number(order.total_price ?? order.total ?? 0);
            }

            return {
                ...normalized,
                created_at: normalized.created_at || new Date().toISOString(),
                status: normalized.status || '-',
                table_number: normalized.table_number || '-'
            };
        },

        upsertRecentOrder(order) {
            const idx = this.recentOrders.findIndex(item => item.id === order.id);
            const cleanOrder = Object.fromEntries(
                Object.entries(order).filter(([, value]) => value !== null && value !== undefined)
            );

            if (idx !== -1) {
                this.recentOrders[idx] = { ...this.recentOrders[idx], ...cleanOrder };
            } else {
                this.recentOrders.unshift(cleanOrder);
            }

            this.recentOrders = this.recentOrders.slice(0, 5);
        },

        initChart() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const labels = this.hourlyRevenue.map(item => String(item.hour).padStart(2, '0') + ':00');
            const values = this.hourlyRevenue.map(item => Number(item.revenue || 0));
            
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(255, 122, 47, 0.5)'); // Primary color
            gradient.addColorStop(1, 'rgba(255, 122, 47, 0)');

            if (this.chartInstance) {
                this.chartInstance.data.labels = labels;
                this.chartInstance.data.datasets[0].data = values;
                this.chartInstance.update();
                return;
            }

            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Pendapatan',
                        data: values,
                        borderColor: '#FF7A2F',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#FF7A2F',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [5, 5],
                                color: '#f3f4f6'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + (value/1000) + 'k';
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(val || 0));
        },
        
        formatTime(isoString) {
            const d = new Date(isoString);
            return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
        }
    };
}
</script>
@endpush

