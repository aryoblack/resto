@extends('layouts.admin')

@section('title', 'Manajemen Meja')

@push('styles')
<style>
    .table-qr-preview svg,
    .qr-print-code svg {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }
</style>
@endpush

@section('content')
<div x-data="tableManager()" x-init="init()" class="space-y-6">

    <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Daftar Meja & QR Code</h2>
            <p class="text-sm text-gray-500">Kelola meja dan cetak QR Code untuk dipindai pelanggan.</p>
        </div>
        <button @click="openModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-primary-600 hover:bg-primary-700 transition-colors">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Meja
        </button>
    </div>

    <x-admin-pagination label="meja" />

    <!-- Tables Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6 relative">
        <div x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>

        <template x-for="table in tables" :key="table.id">
            <div class="bg-white rounded-2xl shadow-sm border flex flex-col overflow-hidden transition-all hover:shadow-md min-h-[286px]" :class="table.status === 'available' ? 'border-green-200' : 'border-red-200'">
                <!-- Header -->
                <div class="p-3 border-b flex justify-between items-center" :class="table.status === 'available' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100'">
                    <span class="font-bold text-gray-800" x-text="'Meja ' + table.table_number"></span>
                    <span class="inline-flex h-3 w-3">
                      <span class="inline-flex rounded-full h-3 w-3" :class="table.status === 'available' ? 'bg-green-500' : 'bg-red-500'"></span>
                    </span>
                </div>
                <!-- Body -->
                <div class="p-4 flex-1 flex flex-col items-center">
                    <div class="table-qr-preview w-32 h-32 bg-white p-2 rounded-xl border border-gray-200 shadow-inner mb-3 overflow-hidden" x-html="table.qr_code_svg || generatePlaceholderQR()"></div>

                    <div class="grid grid-cols-2 gap-2 w-full mb-2">
                        <button @click="regenerateQr(table.id)" class="inline-flex min-h-9 items-center justify-center gap-1 rounded-lg bg-primary-50 px-2 py-2 text-xs font-bold text-primary-700 hover:bg-primary-100 transition-colors">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>Regenerate</span>
                        </button>
                        <a :href="table.qr_scan_url || ('/scan/' + table.qr_code)" target="_blank" class="inline-flex min-h-9 items-center justify-center gap-1 rounded-lg bg-gray-100 px-2 py-2 text-xs font-bold text-gray-700 hover:bg-gray-200 transition-colors">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14m-4-4v10h10"></path></svg>
                            <span>Lihat App</span>
                        </a>
                    </div>
                    <button @click="printQr(table)" class="mt-auto inline-flex w-full min-h-10 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 shadow-sm hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0v4h12v-4H6z"></path></svg>
                        Cetak QR
                    </button>
                </div>
                <!-- Footer -->
                <div class="border-t border-gray-100 grid grid-cols-2 divide-x divide-gray-100 bg-gray-50">
                    <button @click="toggleStatus(table)" class="p-2 text-xs font-medium text-gray-600 hover:bg-gray-100 transition-colors flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        Ubah Status
                    </button>
                    <button @click="deleteTable(table.id)" class="p-2 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Hapus
                    </button>
                </div>
            </div>
        </template>
        
        <!-- Add New Table Card -->
        <button @click="openModal()" class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-2xl flex flex-col items-center justify-center min-h-[286px] hover:bg-gray-100 hover:border-primary-400 transition-colors text-gray-500 hover:text-primary-600 group">
            <svg class="w-10 h-10 mb-2 transform group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            <span class="font-medium text-sm">Tambah Meja</span>
        </button>
    </div>

    <!-- Modal Form -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" @click="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="isModalOpen" x-transition.scale.origin.bottom class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
                <form @submit.prevent="saveTable">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 mb-4">Tambah Meja Baru</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor / Nama Meja</label>
                            <input type="text" x-model="form.table_number" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl border-t border-gray-100">
                        <button type="submit" :disabled="saving" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            <span x-show="!saving">Simpan</span>
                            <span x-show="saving">Menyimpan...</span>
                        </button>
                        <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Printable QR Sheet -->
    <div class="qr-print-sheet" x-show="printTable" aria-hidden="true">
        <div class="text-center flex flex-col items-center justify-between bg-white" :style="printCardStyle()">
            <div>
                <div class="text-xl font-black text-gray-900">RestoApp</div>
                <div class="text-xs font-semibold text-gray-500 mt-1">Scan untuk pesan dari meja</div>
            </div>

            <div class="w-full flex flex-col items-center">
                <div class="text-3xl font-black text-gray-900 mb-3" x-text="printTable ? 'Meja ' + printTable.table_number : ''"></div>
                <div class="qr-print-code border border-gray-200 rounded-lg p-2 flex items-center justify-center" :style="printQrStyle()" x-html="printTable?.qr_code_svg || generatePlaceholderQR()"></div>
                <div class="mt-3 text-[10px] font-medium text-gray-600 break-all" :style="printUrlStyle()" x-text="printTable?.qr_scan_url"></div>
            </div>

            <div class="text-[10px] text-gray-500 leading-relaxed">
                Arahkan kamera HP ke QR Code ini, lalu pilih menu dan buat pesanan.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function tableManager() {
    return {
        tables: [],
        loading: false,
        saving: false,
        isModalOpen: false,
        printTable: null,
        printPaperSize: '80',
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
        form: { table_number: '' },

        init() {
            this.fetchTables();
            this.fetchPrintSettings();
            window.addEventListener('afterprint', () => {
                this.printTable = null;
            });
        },

        async fetchTables(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                const res = await fetch('/api/admin/tables?' + params.toString(), {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    this.tables = json.data.data || json.data;
                    if (json.meta) this.pagination = { ...this.pagination, ...json.meta };
                }
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) this.fetchTables(page);
        },

        async fetchPrintSettings() {
            try {
                const res = await fetch('/api/admin/settings', {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    const size = String(json.data?.qr_print_paper_size || '80');
                    this.printPaperSize = ['58', '80'].includes(size) ? size : '80';
                }
            } catch (e) { console.error(e); }
        },

        openModal() {
            this.form.table_number = '';
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        async saveTable() {
            this.saving = true;
            try {
                const res = await fetch('/api/admin/tables', {
                    method: 'POST',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ table_number: this.form.table_number })
                });
                if(res.ok) {
                    this.closeModal();
                    this.fetchTables(this.pagination.current_page);
                } else {
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menambah meja', message: 'Nomor meja mungkin sudah digunakan.' });
                }
            } catch (e) { console.error(e); }
            finally { this.saving = false; }
        },

        async deleteTable(id) {
            const confirmed = await window.restoConfirm({ variant: 'danger', title: 'Hapus meja?', message: 'Meja dan QR code terkait akan dihapus.', confirmText: 'Hapus' });
            if(!confirmed) return;
            try {
                await fetch(`/api/admin/tables/${id}`, {
                    method: 'DELETE',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                this.fetchTables(this.pagination.current_page);
            } catch (e) { console.error(e); }
        },

        async regenerateQr(id) {
            try {
                await fetch(`/api/admin/tables/${id}/regenerate-qr`, {
                    method: 'POST',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                this.fetchTables(this.pagination.current_page);
            } catch (e) { console.error(e); }
        },

        async toggleStatus(table) {
            const newStatus = table.status === 'available' ? 'occupied' : 'available';
            try {
                await fetch(`/api/admin/tables/${table.id}/status`, {
                    method: 'PATCH',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ status: newStatus })
                });
                table.status = newStatus;
            } catch (e) { console.error(e); }
        },

        printQr(table) {
            const printWindow = window.open('', '_blank', 'width=420,height=720');

            if(!printWindow) {
                window.restoAlert({ variant: 'danger', title: 'Popup print diblokir', message: 'Izinkan popup di browser untuk mencetak QR meja.' });
                return;
            }

            const width = this.printPaperWidth();
            const height = this.printPaperHeight();
            const qrSize = this.printQrMillimeters();
            const padding = this.printPaperSize === '58' ? 4 : 6;
            const titleSize = this.printPaperSize === '58' ? 16 : 20;
            const tableSize = this.printPaperSize === '58' ? 26 : 32;
            const url = this.escapeHtml(table.qr_scan_url || `/scan/${table.qr_code}`);
            const tableNumber = this.escapeHtml(table.table_number);
            const qrSvg = table.qr_code_svg || this.generatePlaceholderQR();

            printWindow.document.open();
            printWindow.document.write(`<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR Meja ${tableNumber}</title>
    <style>
        @page { size: ${width}mm ${height}mm; margin: 0; }
        * { box-sizing: border-box; }
        html, body {
            width: ${width}mm;
            min-height: ${height}mm;
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, sans-serif;
            color: #111827;
        }
        .label {
            width: ${width}mm;
            min-height: ${height}mm;
            padding: ${padding}mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
        }
        .brand {
            font-size: ${titleSize}px;
            font-weight: 800;
            line-height: 1.1;
        }
        .subtitle {
            margin-top: 2mm;
            font-size: 11px;
            font-weight: 700;
        }
        .table {
            margin-bottom: 3mm;
            font-size: ${tableSize}px;
            font-weight: 900;
            line-height: 1;
        }
        .qr {
            width: ${qrSize}mm;
            height: ${qrSize}mm;
            padding: 2mm;
            border: 1px solid #d1d5db;
            border-radius: 3mm;
        }
        .qr svg {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }
        .url {
            max-width: ${width - (padding * 2)}mm;
            margin-top: 3mm;
            font-size: 9px;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }
        .hint {
            font-size: 10px;
            line-height: 1.35;
        }
    </style>
</head>
<body onload="window.focus(); setTimeout(function(){ window.print(); }, 200);">
    <main class="label">
        <header>
            <div class="brand">RestoApp</div>
            <div class="subtitle">Scan untuk pesan dari meja</div>
        </header>
        <section>
            <div class="table">Meja ${tableNumber}</div>
            <div class="qr">${qrSvg}</div>
            <div class="url">${url}</div>
        </section>
        <footer class="hint">Arahkan kamera HP ke QR Code ini, lalu pilih menu dan buat pesanan.</footer>
    </main>
</body>
</html>`);
            printWindow.document.close();
        },

        printPaperWidth() {
            return this.printPaperSize === '58' ? 58 : 80;
        },

        printPaperHeight() {
            return this.printPaperSize === '58' ? 105 : 125;
        },

        printQrMillimeters() {
            return this.printPaperSize === '58' ? 42 : 58;
        },

        printCardStyle() {
            const width = this.printPaperWidth();
            const height = this.printPaperHeight();
            const padding = this.printPaperSize === '58' ? 5 : 7;

            return `width: ${width}mm; min-height: ${height}mm; padding: ${padding}mm; border: 1.5px solid #111827; border-radius: 8px;`;
        },

        printQrStyle() {
            const size = this.printQrMillimeters();
            return `width: ${size}mm; height: ${size}mm;`;
        },

        printUrlStyle() {
            return `max-width: ${this.printPaperWidth() - 10}mm;`;
        },

        setPrintPageSize() {
            const width = this.printPaperWidth();
            const height = this.printPaperHeight();
            let style = document.getElementById('qr-print-page-size');

            if(!style) {
                style = document.createElement('style');
                style.id = 'qr-print-page-size';
                document.head.appendChild(style);
            }

            style.textContent = `@media print { @page { size: ${width}mm ${height}mm; margin: 0; } }`;
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        generatePlaceholderQR() {
            // A simple placeholder SVG pattern for when real QR is missing
            return `<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="text-gray-300 w-full h-full fill-current"><rect width="100" height="100" rx="10" fill="none" stroke="currentColor" stroke-width="4"/><rect x="20" y="20" width="20" height="20"/><rect x="60" y="20" width="20" height="20"/><rect x="20" y="60" width="20" height="20"/><circle cx="70" cy="70" r="10"/></svg>`;
        }
    }
}
</script>
@endpush
