@extends('layouts.kds')

@section('title', 'KDS - Antrean Dapur')

@section('content')
<div x-data="kdsManager()" x-init="init()" class="h-full overflow-y-auto p-3 sm:p-4 lg:p-6">
    <div class="grid auto-rows-max grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
    
    <template x-for="order in activeOrders" :key="order.id">
        <div class="flex min-h-[22rem] max-h-[calc(100vh-7.5rem)] min-w-0 flex-col overflow-hidden rounded-2xl border-t-4 bg-gray-800 shadow-xl"
             :class="getTimerColorBorderClass(order.created_at)">
            
            <!-- Card Header -->
            <div class="rounded-t-2xl border-b border-gray-700 bg-gray-800 p-3 sm:p-4">
                <div class="mb-3 grid grid-cols-[minmax(0,1fr)_auto] items-start gap-3">
                    <h2 class="min-w-0 break-words text-xl font-extrabold leading-tight text-white sm:text-2xl">#<span x-text="order.order_number || order.id"></span></h2>
                    <span class="max-w-[5.5rem] rounded px-2.5 py-1 text-center text-[11px] font-bold uppercase leading-tight sm:max-w-none sm:text-xs"
                          :class="order.order_type === 'dine_in' ? 'bg-blue-900/50 text-blue-400' : 'bg-purple-900/50 text-purple-400'"
                          x-text="order.order_type === 'dine_in' ? 'Dine In' : 'Takeaway'">
                    </span>
                </div>
                
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <div class="min-w-0 font-medium text-gray-400">
                        <span x-show="order.order_type === 'dine_in'">Meja <span class="text-lg font-bold text-white" x-text="order.table?.table_number || order.table_id || '-'"></span></span>
                        <span x-show="order.order_type !== 'dine_in'" class="text-white">Bungkus</span>
                    </div>
                    <!-- Real-time Timer Badge -->
                    <div class="max-w-full rounded-lg px-3 py-1 font-mono text-sm font-bold shadow-sm" :class="getTimerColorBadgeClass(order.created_at)" x-text="getElapsedTime(order.created_at)"></div>
                </div>
            </div>

            <!-- Items List -->
            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto bg-gray-900/50 p-2 sm:p-3">
                <template x-for="item in order.items" :key="item.id">
                    <div class="flex cursor-pointer select-none gap-3 rounded-xl border border-gray-700 bg-gray-800 p-3 transition-colors"
                         :class="{'opacity-50 line-through bg-gray-800/50': item.status === 'Selesai'}"
                         @click="toggleItemStatus(order, item)">
                        
                        <!-- Qty Bubble -->
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-lg font-bold"
                             :class="item.status === 'Selesai' ? 'bg-gray-700 text-gray-500' : 'bg-primary-600 text-white'"
                             x-text="item.quantity"></div>
                        
                        <div class="min-w-0 flex-1">
                            <h3 class="break-words font-bold leading-tight text-gray-100" x-text="item.menu?.name || item.menu_name"></h3>
                            <div x-show="item.variant_selected" class="text-xs text-gray-400 mt-1" x-text="'Varian: ' + item.variant_selected"></div>
                            
                            <!-- Catatan Khusus -->
                            <div x-show="item.note" class="mt-2 flex gap-2 rounded-lg border border-red-800/50 bg-red-900/30 p-2 text-sm font-medium text-red-300">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="break-words" x-text="item.note"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer Action -->
            <div class="mt-auto rounded-b-2xl border-t border-gray-700 bg-gray-800 p-3 sm:p-4">
                <template x-if="order.order_status === 'Diterima'">
                    <button @click="markOrderAs(order.id, 'Diproses')" :disabled="isUpdatingOrder(order.id)" class="min-h-[3.25rem] w-full rounded-xl bg-blue-600 px-3 py-3 text-base font-bold uppercase tracking-wide text-white shadow-lg transition-colors hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-blue-600 sm:text-lg">
                        <span x-text="isUpdatingOrder(order.id) ? 'Memproses...' : 'Proses Pesanan'"></span>
                    </button>
                </template>
                <template x-if="order.order_status === 'Diproses'">
                    <button @click="markOrderAs(order.id, 'Dimasak')" :disabled="isUpdatingOrder(order.id)" class="min-h-[3.25rem] w-full rounded-xl bg-blue-600 px-3 py-3 text-base font-bold uppercase tracking-wide text-white shadow-lg transition-colors hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-blue-600 sm:text-lg">
                        <span x-text="isUpdatingOrder(order.id) ? 'Memproses...' : 'Mulai Masak'"></span>
                    </button>
                </template>
                <template x-if="order.order_status === 'Dimasak'">
                    <!-- Disable button if not all items are checked -->
                    <button @click="markOrderAs(order.id, 'Selesai')" 
                            :disabled="!isAllItemsDone(order) || isUpdatingOrder(order.id)"
                            class="min-h-[3.25rem] w-full rounded-xl px-3 py-3 text-base font-bold uppercase tracking-wide shadow-lg transition-colors disabled:cursor-not-allowed disabled:opacity-50 sm:text-lg"
                            :class="isAllItemsDone(order) ? 'bg-green-600 hover:bg-green-500 text-white' : 'bg-gray-700 text-gray-400'">
                        <span x-text="isUpdatingOrder(order.id) ? 'Memproses...' : 'Selesai Masak'"></span>
                    </button>
                </template>
            </div>
        </div>
    </template>
    </div>

    <!-- Empty State -->
    <div x-show="activeOrders.length === 0" class="flex h-full min-h-[calc(100vh-8rem)] flex-col items-center justify-center text-center text-gray-500">
        <svg class="w-24 h-24 text-gray-700 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
        <h2 class="text-3xl font-bold text-gray-600 mb-2">Dapur Kosong</h2>
        <p class="text-lg">Belum ada pesanan yang masuk. Waktunya bernapas lega!</p>
    </div>

</div>
@endsection

@push('scripts')
<script>
function kdsManager() {
    return {
        activeOrders: [],
        now: new Date(),
        timerInterval: null,
        updatingOrders: {},

        init() {
            this.fetchOrders();
            
            // Update time every second for reactive badges
            this.timerInterval = setInterval(() => {
                this.now = new Date();
            }, 1000);

            if (window.Echo) {
                window.Echo.private('orders')
                    .listen('.order.created', (e) => {
                        if (e.order) {
                            this.playBeepSound();
                            // Transform items to have 'status' if not present
                            if(e.order.items) {
                                e.order.items = e.order.items.map(i => ({...i, status: 'Menunggu'}));
                            }
                            const idx = this.activeOrders.findIndex(o => o.id === e.order.id);
                            if (idx !== -1) {
                                this.activeOrders[idx] = e.order;
                            } else {
                                this.activeOrders.push(e.order);
                            }
                        }
                    })
                    .listen('.order.status.updated', (e) => {
                        if (e.order) {
                            const idx = this.activeOrders.findIndex(o => o.id === e.order.id);
                            // Only keep it if it's not "Selesai" or "Disajikan"
                            if (['Selesai', 'Disajikan'].includes(e.order.order_status)) {
                                if (idx !== -1) this.activeOrders.splice(idx, 1);
                            } else {
                                if (idx !== -1) {
                                    this.activeOrders[idx].order_status = e.order.order_status;
                                } else {
                                    this.fetchOrders(); // Re-fetch to be safe
                                }
                            }
                        }
                    });
            }
        },

        async fetchOrders() {
            try {
                // Fetching active kitchen orders (Diterima, Diproses, Dimasak)
                const res = await fetch('/api/staff/orders?order_status=active_kitchen', {
                    headers: window.restoAuthHeaders({ 'Accept': 'application/json' })
                });
                if(res.ok) {
                    const json = await res.json();
                    let data = json.data.data || json.data;
                    
                    // Filter frontend fallback just in case
                    data = data.filter(o => !['Selesai', 'Disajikan'].includes(o.order_status));
                    
                    // Add local status to items for toggling UI
                    data.forEach(o => {
                        o.items.forEach(i => {
                            if(!i.status) i.status = 'Menunggu';
                        });
                    });

                    this.activeOrders = data;
                }
            } catch (e) { console.error('Gagal fetch KDS orders', e); }
        },

        isUpdatingOrder(orderId) {
            return Boolean(this.updatingOrders[orderId]);
        },

        async markOrderAs(id, status) {
            if (this.isUpdatingOrder(id)) return;

            this.updatingOrders = { ...this.updatingOrders, [id]: true };

            try {
                const res = await fetch(`/api/staff/orders/${id}/status`, {
                    method: 'PATCH',
                    headers: { 
                        ...window.restoAuthHeaders(), 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ status: status })
                });
                
                if(res.ok) {
                    const idx = this.activeOrders.findIndex(o => o.id === id);
                    if(idx !== -1) {
                        if(status === 'Selesai') {
                            // Remove from board
                            this.activeOrders.splice(idx, 1);
                        } else {
                            this.activeOrders[idx].order_status = status;
                        }
                    }
                }
            } catch (e) { console.error(e); }
            finally {
                const { [id]: _finished, ...remaining } = this.updatingOrders;
                this.updatingOrders = remaining;
            }
        },

        toggleItemStatus(order, item) {
            if(order.order_status === 'Diterima') {
                this.markOrderAs(order.id, 'Diproses');
                return;
            }
            if(order.order_status === 'Diproses') {
                this.markOrderAs(order.id, 'Dimasak');
            }
            // Toggle local item status
            item.status = item.status === 'Selesai' ? 'Menunggu' : 'Selesai';
        },

        isAllItemsDone(order) {
            if(!order.items || order.items.length === 0) return false;
            return order.items.every(i => i.status === 'Selesai');
        },

        getElapsedMinutes(isoString) {
            if(!isoString) return 0;
            const created = new Date(isoString).getTime();
            const nowTime = this.now.getTime();
            return Math.floor((nowTime - created) / 60000);
        },

        getElapsedTime(isoString) {
            if(!isoString) return '00:00';
            const created = new Date(isoString).getTime();
            const nowTime = this.now.getTime();
            const totalSeconds = Math.floor((nowTime - created) / 1000);
            
            const m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
            const s = (totalSeconds % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        getTimerColorBorderClass(isoString) {
            const mins = this.getElapsedMinutes(isoString);
            if(mins > 25) return 'border-red-500';
            if(mins > 15) return 'border-yellow-500';
            return 'border-green-500';
        },

        getTimerColorBadgeClass(isoString) {
            const mins = this.getElapsedMinutes(isoString);
            if(mins > 25) return 'bg-red-500 text-white animate-pulse';
            if(mins > 15) return 'bg-yellow-500 text-gray-900';
            return 'bg-green-500 text-white';
        },

        playBeepSound() {
            try {
                // Short beep using web audio API
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime); // A5
                
                gain.gain.setValueAtTime(0.5, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.3);
            } catch (e) { console.log('Audio disabled by browser'); }
        }
    }
}
</script>
@endpush
