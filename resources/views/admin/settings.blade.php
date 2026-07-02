@extends('layouts.admin')

@section('title', 'Pengaturan Sistem')

@section('content')
<div x-data="settingsManager()" x-init="init()" class="pb-24 lg:pb-10">

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-gray-900 sm:text-xl">Pengaturan Sistem</h2>
            <p class="mt-1 text-sm text-gray-500">Konfigurasi operasional, keuangan, dan perangkat restoran Anda.</p>
        </div>
        <div class="hidden items-center gap-2 rounded-2xl border border-gray-100 bg-white px-4 py-3 text-sm text-gray-500 shadow-sm lg:flex">
            <svg class="h-5 w-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a6 6 0 100-12 6 6 0 000 12z"></path></svg>
            <span>Perubahan aktif setelah disimpan.</span>
        </div>
    </div>

    <form @submit.prevent="saveSettings" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
        
        <!-- General Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-4 border-b border-gray-100 bg-gray-50/50 sm:px-6">
                <h3 class="font-semibold text-gray-800">Informasi Restoran</h3>
            </div>
            <div class="grid gap-6 p-4 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Restoran</label>
                    <input type="text" x-model="settings.restaurant_name" required class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                </div>
                
                <div class="lg:row-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo Restoran (URL)</label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                        <div class="h-20 w-20 rounded-2xl bg-gray-100 border border-gray-200 flex-shrink-0 overflow-hidden flex items-center justify-center">
                            <template x-if="settings.restaurant_logo">
                                <img :src="settings.restaurant_logo" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!settings.restaurant_logo">
                                <svg class="w-8 h-8 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </template>
                        </div>
                        <input type="text" x-model="settings.restaurant_logo" placeholder="https://example.com/logo.png" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border sm:mt-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jam Operasional</label>
                    <input type="text" x-model="settings.operational_hours" placeholder="Senin - Minggu: 10.00 - 22.00" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon Restoran</label>
                    <input type="text" x-model="settings.restaurant_phone" placeholder="Contoh: 0812-3456-7890" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slogan Struk</label>
                    <input type="text" x-model="settings.receipt_slogan" placeholder="Contoh: Terima kasih atas kunjungan Anda" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Restoran</label>
                    <textarea x-model="settings.restaurant_address" rows="3" placeholder="Alamat lengkap yang akan tampil di struk" class="block w-full resize-none border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Informasi Kontak</label>
                    <input type="text" x-model="settings.contact_info" placeholder="Telepon / Email / Alamat" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                </div>
            </div>
        </div>

        <!-- Financial & Loyalty -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-4 border-b border-gray-100 bg-gray-50/50 sm:px-6">
                <h3 class="font-semibold text-gray-800">Keuangan & Loyalitas</h3>
            </div>
            <div class="p-4 grid grid-cols-1 gap-6 sm:p-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pajak (%)</label>
                    <div class="relative rounded-xl shadow-sm">
                        <input type="number" step="0.1" min="0" max="100" x-model="settings.tax_percentage" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border pr-10">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biaya Layanan (Service Charge) (%)</label>
                    <div class="relative rounded-xl shadow-sm">
                        <input type="number" step="0.1" min="0" max="100" x-model="settings.service_charge_percentage" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border pr-10">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">%</span>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Konversi Poin (Rp per 1 Poin)</label>
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="number" min="1" x-model="settings.point_conversion_rate" class="block w-full pl-10 border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Misal: 10000 berarti setiap pembelanjaan Rp 10.000, pelanggan mendapat 1 poin.</p>
                </div>
            </div>
        </div>

        <!-- Print Settings -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-4 border-b border-gray-100 bg-gray-50/50 sm:px-6">
                <h3 class="font-semibold text-gray-800">Printer Thermal</h3>
            </div>
            <div class="p-4 space-y-6 sm:p-6">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,280px)_minmax(0,1fr)] lg:items-start">
                    <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Ukuran Kertas Cetak QR Meja</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" value="80" x-model="settings.qr_print_paper_size" class="sr-only">
                            <span class="flex h-12 items-center justify-center rounded-xl border-2 text-sm font-bold transition-all" :class="settings.qr_print_paper_size === '80' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">80 mm</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" value="58" x-model="settings.qr_print_paper_size" class="sr-only">
                            <span class="flex h-12 items-center justify-center rounded-xl border-2 text-sm font-bold transition-all" :class="settings.qr_print_paper_size === '58' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">58 mm</span>
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Dipakai saat admin mencetak QR Code meja dari menu Meja.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Metode Print</label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <label class="cursor-pointer">
                                <input type="radio" value="browser" x-model="settings.thermal_print_method" class="sr-only">
                                <span class="flex min-h-16 flex-col justify-center rounded-xl border-2 px-4 py-3 text-sm transition-all" :class="settings.thermal_print_method === 'browser' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <span class="font-bold">Browser</span>
                                    <span class="text-xs opacity-80">Print biasa / PDF</span>
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="windows" x-model="settings.thermal_print_method" class="sr-only">
                                <span class="flex min-h-16 flex-col justify-center rounded-xl border-2 px-4 py-3 text-sm transition-all" :class="settings.thermal_print_method === 'windows' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <span class="font-bold">Windows</span>
                                    <span class="text-xs opacity-80">Local bridge printer</span>
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="escpos" x-model="settings.thermal_print_method" class="sr-only">
                                <span class="flex min-h-16 flex-col justify-center rounded-xl border-2 px-4 py-3 text-sm transition-all" :class="settings.thermal_print_method === 'escpos' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <span class="font-bold">ESC/POS</span>
                                    <span class="text-xs opacity-80">Raw thermal commands</span>
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="bridge" x-model="settings.thermal_print_method" class="sr-only">
                                <span class="flex min-h-16 flex-col justify-center rounded-xl border-2 px-4 py-3 text-sm transition-all" :class="settings.thermal_print_method === 'bridge' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                    <span class="font-bold">Print Bridge</span>
                                    <span class="text-xs opacity-80">Untuk hosting/cloud</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Printer Windows</label>
                        <input type="text" x-model="settings.thermal_printer_name" placeholder="Contoh: POS-80C" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                        <p class="mt-1 text-xs text-gray-500">Bisa diisi dari nama printer Windows yang tampil di Devices & Printers.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Share Printer</label>
                        <input type="text" x-model="settings.thermal_printer_share_name" placeholder="Contoh: \\\\KASIR\\POS-80" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                        <p class="mt-1 text-xs text-gray-500">Dipakai untuk printer USB yang dishare di jaringan.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Koneksi Printer</label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <label class="cursor-pointer">
                            <input type="radio" value="windows" x-model="settings.thermal_printer_connection" class="sr-only">
                            <span class="flex h-11 items-center justify-center rounded-xl border-2 text-sm font-bold transition-all" :class="settings.thermal_printer_connection === 'windows' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">Windows</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" value="usb" x-model="settings.thermal_printer_connection" class="sr-only">
                            <span class="flex h-11 items-center justify-center rounded-xl border-2 text-sm font-bold transition-all" :class="settings.thermal_printer_connection === 'usb' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">USB</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" value="network" x-model="settings.thermal_printer_connection" class="sr-only">
                            <span class="flex h-11 items-center justify-center rounded-xl border-2 text-sm font-bold transition-all" :class="settings.thermal_printer_connection === 'network' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">IP</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" value="bluetooth" x-model="settings.thermal_printer_connection" class="sr-only">
                            <span class="flex h-11 items-center justify-center rounded-xl border-2 text-sm font-bold transition-all" :class="settings.thermal_printer_connection === 'bluetooth' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">Bluetooth</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">IP Printer</label>
                        <input type="text" x-model="settings.thermal_printer_ip" placeholder="192.168.1.50" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Port IP</label>
                        <input type="number" min="1" max="65535" x-model="settings.thermal_printer_port" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Port Bluetooth / COM</label>
                        <input type="text" x-model="settings.thermal_printer_bluetooth_port" placeholder="COM5" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2" x-show="settings.thermal_print_method !== 'browser'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Print Bridge</label>
                        <input type="text" x-model="settings.print_bridge_url" placeholder="http://127.0.0.1:17777/print" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Token Bridge</label>
                        <input type="text" x-model="settings.print_bridge_token" placeholder="Token rahasia local bridge" class="block w-full border-gray-300 rounded-xl focus:ring-primary-500 focus:border-primary-500 sm:text-sm px-4 py-2 border">
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Action -->
        <aside class="xl:sticky xl:top-6 xl:self-start">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
                <h3 class="font-semibold text-gray-900">Simpan Perubahan</h3>
                <p class="mt-1 text-sm text-gray-500">Pastikan data restoran, pajak, dan printer sudah sesuai sebelum dipakai transaksi.</p>
                <button type="submit" :disabled="saving" class="mt-5 inline-flex w-full justify-center items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors disabled:opacity-50">
                <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Pengaturan'"></span>
                </button>
            </div>
        </aside>
    </form>
</div>
@endsection

@push('scripts')
<script>
function settingsManager() {
    return {
        settings: {
            restaurant_name: '',
            restaurant_logo: '',
            restaurant_address: '',
            restaurant_phone: '',
            receipt_slogan: '',
            operational_hours: '',
            contact_info: '',
            tax_percentage: 0,
            service_charge_percentage: 0,
            point_conversion_rate: 10000,
            qr_print_paper_size: '80',
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
        saving: false,

        async init() {
            try {
                const res = await fetch('/api/admin/settings', {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    if(json.data) {
                        // Merge fetched settings into state
                        for (const key in json.data) {
                            if (this.settings.hasOwnProperty(key)) {
                                this.settings[key] = json.data[key];
                            }
                        }
                    }
                }
            } catch (e) { console.error('Error fetching settings', e); }
        },

        async saveSettings() {
            this.saving = true;
            try {
                const res = await fetch('/api/admin/settings', {
                    method: 'POST',
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json', 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ settings: this.settings })
                });
                if(res.ok) {
                    await window.restoAlert({ variant: 'success', title: 'Pengaturan berhasil disimpan', message: 'Perubahan pengaturan sudah diterapkan.' });
                    // Optionally update the global state or refresh
                } else {
                    const err = await res.json();
                    await window.restoAlert({ variant: 'danger', title: 'Gagal menyimpan pengaturan', message: err.message || 'Cek kembali data Anda.' });
                }
            } catch (e) { console.error('Error saving settings', e); }
            finally { this.saving = false; }
        }
    }
}
</script>
@endpush
