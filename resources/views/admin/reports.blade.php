@extends('layouts.admin')

@section('title', 'Laporan & Analitik')

@section('content')
<div x-data="reportManager()" x-init="init()" class="space-y-6">

    <!-- Filters -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Laporan</label>
            <select x-model="reportType" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                <option value="sales">Laporan Penjualan</option>
                <option value="stock">Laporan Stok Opname</option>
            </select>
        </div>
        
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
            <input type="date" x-model="startDate" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
        </div>
        
        <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
            <input type="date" x-model="endDate" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
        </div>

        <div class="flex gap-2 w-full md:w-auto">
            <button @click="generateReport()" class="flex-1 md:flex-none inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                Tampilkan
            </button>
            <button @click="exportData('excel')" class="inline-flex justify-center items-center px-3 py-2 border border-green-600 text-sm font-medium rounded-xl text-green-700 bg-green-50 hover:bg-green-100 transition-colors" title="Export Excel">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </button>
            <button @click="exportData('pdf')" class="inline-flex justify-center items-center px-3 py-2 border border-red-600 text-sm font-medium rounded-xl text-red-700 bg-red-50 hover:bg-red-100 transition-colors" title="Export PDF">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </button>
        </div>
    </div>

    <!-- Metrics Summary (only for sales) -->
    <div x-show="reportType === 'sales' && data.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-gray-500 text-sm font-medium">Total Omzet</h3>
            <p class="text-2xl font-bold text-gray-800 mt-1" x-text="formatCurrency(summary.total_revenue || 0)"></p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-gray-500 text-sm font-medium">Jumlah Pesanan</h3>
            <p class="text-2xl font-bold text-gray-800 mt-1" x-text="summary.order_count || 0"></p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-gray-500 text-sm font-medium">Rata-rata Nilai Pesanan</h3>
            <p class="text-2xl font-bold text-gray-800 mt-1" x-text="formatCurrency(summary.avg_order_value || 0)"></p>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden relative">
        <div x-show="loading" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <!-- Dynamic Headers based on report type -->
                    <tr x-show="reportType === 'sales'">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Jumlah Pesanan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Omzet</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Rata-rata Pesanan</th>
                    </tr>
                    <tr x-show="reportType === 'stock'">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bahan Baku</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Stok Sistem</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Satuan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Stok Minimum</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Sales Report Rows -->
                    <template x-if="reportType === 'sales'">
                        <template x-for="row in paginatedData()" :key="row.id || row.period_label">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="row.period_label"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="row.order_count"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900" x-text="formatCurrency(row.total_revenue)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700" x-text="formatCurrency(row.average_order_value)"></td>
                            </tr>
                        </template>
                    </template>
                    
                    <!-- Stock Report Rows -->
                    <template x-if="reportType === 'stock'">
                        <template x-for="row in paginatedData()" :key="row.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="row.ingredient_name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="row.current_stock"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="row.unit"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="row.min_stock"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                          :class="row.current_stock <= row.min_stock ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                          x-text="row.current_stock <= row.min_stock ? 'Kritis' : 'Aman'"></span>
                                </td>
                            </tr>
                        </template>
                    </template>

                    <!-- Empty state -->
                    <tr x-show="data.length === 0 && !loading">
                        <td :colspan="reportType === 'sales' ? 4 : 5" class="px-6 py-12 text-center text-gray-500">
                            Silakan klik Tampilkan untuk memuat data laporan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <x-admin-pagination
            mode="footer"
            show="data.length > 0"
            from="paginationFrom()"
            to="paginationTo()"
            total="data.length"
            label="baris"
            current="currentPage"
            last="lastPage()"
            prev-click="changePage(currentPage - 1)"
            next-click="changePage(currentPage + 1)"
            prev-disabled="currentPage <= 1"
            next-disabled="currentPage >= lastPage()"
        />
    </div>
</div>
@endsection

@push('scripts')
<script>
function reportManager() {
    return {
        reportType: 'sales',
        startDate: '',
        endDate: '',
        data: [],
        summary: {},
        loading: false,
        currentPage: 1,
        perPage: 15,

        init() {
            // Set default date range to current month
            const date = new Date();
            this.startDate = new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
            this.endDate = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
        },

        async generateReport() {
            this.loading = true;
            this.data = [];
            this.currentPage = 1;
            
            try {
                const params = new URLSearchParams();
                if (this.reportType === 'sales') {
                    params.append('date_from', this.startDate);
                    params.append('date_to', this.endDate);
                }
                
                const endpoint = this.reportType === 'sales' ? '/api/admin/reports/sales' : '/api/admin/reports/stock';
                
                const res = await fetch(`${endpoint}?${params.toString()}`, {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                
                if(res.ok) {
                    const json = await res.json();
                    this.data = this.reportType === 'sales' ? (json.data.data || []) : (json.data.items || []);
                    this.summary = json.data.summary || {};
                    this.currentPage = 1;
                }
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        paginatedData() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.data.slice(start, start + this.perPage);
        },

        lastPage() {
            return Math.max(1, Math.ceil(this.data.length / this.perPage));
        },

        changePage(page) {
            if (page >= 1 && page <= this.lastPage()) this.currentPage = page;
        },

        paginationFrom() {
            if (this.data.length === 0) return 0;
            return ((this.currentPage - 1) * this.perPage) + 1;
        },

        paginationTo() {
            return Math.min(this.currentPage * this.perPage, this.data.length);
        },

        async exportData(format) {
            const endpoint = this.reportType === 'sales'
                ? '/api/admin/reports/export/sales'
                : '/api/admin/reports/export/stock';

            const payload = { format };
            if (this.reportType === 'sales') {
                payload.date_from = this.startDate;
                payload.date_to = this.endDate;
            }

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    await window.restoAlert({ variant: 'danger', title: 'Gagal export laporan', message: err.message || 'File laporan tidak dapat dibuat.' });
                    return;
                }

                const blob = await res.blob();
                const disposition = res.headers.get('Content-Disposition') || '';
                const match = disposition.match(/filename="?([^"]+)"?/);
                const extension = format === 'excel' ? 'xlsx' : 'pdf';
                const filename = match?.[1] || `laporan-${this.reportType}.${extension}`;
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                console.error(e);
                await window.restoAlert({ variant: 'danger', title: 'Gagal export laporan', message: 'Terjadi kesalahan saat mengunduh laporan.' });
            }
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
