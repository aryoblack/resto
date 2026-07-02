@extends('layouts.customer')

@section('title', 'RestoApp - Pesan Cepat & Mudah')

@section('content')
<div x-data="customerApp()" x-init="init()" class="h-screen w-full bg-gray-50 flex flex-col md:flex-row overflow-hidden font-sans text-gray-900">
    
    <!-- Sidebar Navigation (Desktop Only) -->
    <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-100 flex-shrink-0 z-30 relative shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
        <div class="p-6 border-b border-gray-50">
            <h1 class="text-2xl font-black text-primary-600 tracking-tight">Resto<span class="text-gray-900">App</span></h1>
            <div class="mt-2 text-sm text-gray-500 font-medium">Makan di Tempat</div>
            <div class="font-bold text-gray-900 text-sm flex items-center gap-1 mt-1">
                Meja <span x-text="selectedTableLabel()" class="bg-gray-100 px-2 py-0.5 rounded-md"></span>
                <button @click="scanTable()" class="text-primary-600 hover:text-primary-700 ml-1" title="Pilih meja">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                </button>
            </div>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2">
            <button @click="activeTab = 'home'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all" :class="activeTab === 'home' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="activeTab==='home' ? 'stroke-[2.5px]' : 'stroke-2'"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Beranda</span>
            </button>
            <button @click="openOrders()" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all" :class="activeTab === 'orders' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="activeTab==='orders' ? 'stroke-[2.5px]' : 'stroke-2'"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Pesanan Aktif</span>
            </button>
            <button @click="activeTab = 'cart'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all md:hidden" :class="activeTab === 'cart' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="activeTab==='cart' ? 'stroke-[2.5px]' : 'stroke-2'"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>Keranjang</span>
                <span x-show="cart.length > 0" class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full" x-text="cart.length"></span>
            </button>
            <button @click="activeTab = 'profile'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all" :class="activeTab === 'profile' ? 'bg-primary-50 text-primary-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="activeTab==='profile' ? 'stroke-[2.5px]' : 'stroke-2'"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>Profil</span>
            </button>
        </nav>
        
        <div class="p-4 mt-auto">
            <div class="bg-gradient-to-br from-gray-900 to-black p-4 rounded-2xl text-white shadow-lg relative overflow-hidden">
                <svg class="absolute -right-2 -bottom-2 w-20 h-20 text-white/10" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                <div class="relative z-10">
                    <p class="text-xs text-gray-300 mb-0.5">Poin Reward</p>
                    <h2 class="text-2xl font-bold font-heading leading-none" x-text="user ? user.poin : 0"></h2>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile Top Header (Visible only on small screens) -->
    <header class="md:hidden bg-white border-b border-gray-100 flex-shrink-0 z-20 sticky top-0 w-full shadow-sm">
        <div x-show="activeTab === 'home'" class="px-4 py-3 flex items-center justify-between">
            <div>
                <div class="text-xs text-gray-500 font-medium">Makan di Tempat</div>
                <div class="font-bold text-gray-900 text-sm flex items-center gap-1">
                    Meja <span x-text="selectedTableLabel()"></span>
                    <button @click="scanTable()" class="text-primary-600 bg-primary-50 p-1 rounded-md" title="Pilih meja">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    </button>
                </div>
            </div>
            <button @click="activeTab = 'home'; $nextTick(() => $refs.mobileMenuSearch?.focus())" class="p-2 text-gray-600 bg-gray-50 rounded-full hover:bg-gray-100 border border-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
        </div>
        
        <!-- Other Mobile Headers -->
        <div x-show="activeTab === 'detail'" class="px-4 py-3 flex items-center gap-3">
            <button @click="activeTab = 'home'" class="p-2 -ml-2 text-gray-600 rounded-full hover:bg-gray-50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <h1 class="font-bold text-gray-900 text-lg">Detail Menu</h1>
        </div>
        <div x-show="activeTab === 'cart'" class="px-4 py-3 flex items-center justify-between">
            <h1 class="font-bold text-gray-900 text-lg">Keranjang</h1>
            <button x-show="cart.length > 0" @click="clearCart()" class="text-sm text-red-500 font-medium bg-red-50 px-2 py-1 rounded-md">Kosongkan</button>
        </div>
        <div x-show="['profile', 'orders', 'reservations'].includes(activeTab)" class="px-4 py-3 flex items-center gap-3">
            <h1 class="font-bold text-gray-900 text-lg capitalize" x-text="activeTab === 'orders' ? 'Pesanan Saya' : (activeTab === 'reservations' ? 'Reservasi' : 'Profil')"></h1>
        </div>
    </header>

    <!-- Main Scrollable Content Area -->
    <main class="flex-1 h-[100dvh] overflow-y-auto overflow-x-hidden relative flex flex-col w-full md:pb-0" :class="{'pb-20': true}">
        
        <!-- Desktop Header area for specific tabs -->
        <div class="hidden md:flex bg-white/80 backdrop-blur-md sticky top-0 z-20 px-8 py-5 border-b border-gray-100 justify-between items-center" x-show="activeTab !== 'detail'">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 capitalize" x-text="pageTitle()"></h2>
                <p class="text-gray-500 text-sm mt-0.5" x-show="activeTab === 'home'">Pilih menu favorit dan nikmati hidangan spesial kami.</p>
            </div>
            
            <div class="flex items-center gap-4" x-show="activeTab === 'home'">
                <div class="relative">
                    <input type="text" x-model.debounce.150ms="searchQuery" placeholder="Cari menu..." class="pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 w-64 transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            
            <div x-show="activeTab === 'cart'" class="flex items-center">
                <button x-show="cart.length > 0" @click="clearCart()" class="text-sm text-red-600 hover:bg-red-50 font-bold px-4 py-2 rounded-xl border border-red-200 transition-colors">Kosongkan Keranjang</button>
            </div>
        </div>

        <div class="flex-1 w-full max-w-[1600px] mx-auto flex">
            <!-- Left Side: Main Tab Content -->
            <div class="flex-1 w-full" :class="(activeTab === 'home' || activeTab === 'detail') ? 'md:border-r md:border-gray-100 md:pr-0' : ''">
                
                <!-- Tab: Home -->
                <div x-show="activeTab === 'home'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0" class="pb-8 md:p-8">
                    
                    <!-- Banner Promo -->
                    <div x-show="activePromo" class="px-4 md:px-0 pt-4 md:pt-0 mb-6 md:mb-10" style="display: none;">
                        <div class="bg-gradient-to-r from-primary-500 via-primary-600 to-orange-600 rounded-[20px] p-6 md:p-10 text-white shadow-xl shadow-primary-500/20 relative overflow-hidden group">
                            <div class="relative z-10 w-2/3 md:w-1/2">
                                <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg text-xs md:text-sm font-bold tracking-wider mb-3 uppercase">Promo Hari Ini</span>
                                <h2 class="text-2xl md:text-4xl font-black font-heading leading-tight mb-2 md:mb-3" x-text="promoTitle(activePromo)"></h2>
                                <p class="text-sm md:text-base opacity-90 mb-5 md:mb-6 leading-relaxed" x-text="promoDescription(activePromo)"></p>
                                <button x-show="activePromo?.code" @click="claimPromo(activePromo.code)" class="bg-white text-primary-600 text-sm md:text-base font-bold px-6 py-2.5 rounded-full hover:bg-gray-50 hover:scale-105 transition-all shadow-md">Klaim Sekarang</button>
                            </div>
                            <!-- Decorative Elements -->
                            <div class="absolute -right-10 -bottom-10 w-48 h-48 md:w-64 md:h-64 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-all duration-700"></div>
                            <div class="absolute right-10 top-1/2 transform -translate-y-1/2 opacity-20 md:opacity-40 w-32 md:w-64">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                  <path fill="#FFFFFF" d="M47.7,-57.2C59.6,-47.3,65.6,-29.4,69.5,-10.8C73.4,7.8,75.2,27.1,66.3,42.2C57.4,57.3,37.8,68.2,16.5,73.5C-4.8,78.8,-27.8,78.5,-44.6,67.7C-61.4,56.9,-72.1,35.6,-74.6,14.6C-77.1,-6.4,-71.4,-27.1,-58.5,-42.6C-45.6,-58.1,-25.5,-68.4,-6.2,-61.2C13.1,-54,26.2,-25.3,47.7,-57.2Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="px-4 md:px-0 mb-6 md:mb-8">
                        <div class="relative mb-4 md:hidden">
                            <input x-ref="mobileMenuSearch" type="text" x-model.debounce.150ms="searchQuery" placeholder="Cari menu..." class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-11 pr-4 text-sm font-semibold shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <svg class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <h3 class="font-bold text-gray-900 text-lg md:text-xl">Kategori Menu</h3>
                        </div>
                        <div class="flex overflow-x-auto gap-3 pb-2 -mx-4 px-4 md:mx-0 md:px-0 scrollbar-hide snap-x">
                            <button @click="selectCategory(null)" class="snap-start flex-shrink-0 flex items-center justify-center px-5 py-2.5 md:py-3 rounded-full border-2 transition-all" :class="!selectedCategory ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-500/30' : 'bg-white text-gray-600 border-gray-100 hover:border-primary-300 hover:bg-primary-50/50'">
                                <span class="font-bold text-sm md:text-base">Semua</span>
                            </button>
                            <template x-for="cat in categories" :key="cat.id">
                                <button @click="selectCategory(cat.id)" class="snap-start flex-shrink-0 flex items-center justify-center px-5 py-2.5 md:py-3 rounded-full border-2 transition-all" :class="selectedCategory === cat.id ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-500/30' : 'bg-white text-gray-600 border-gray-100 hover:border-primary-300 hover:bg-primary-50/50'">
                                    <span class="font-bold text-sm md:text-base" x-text="cat.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Menu Grid -->
                    <div class="px-4 md:px-0">
                        <h3 class="font-bold text-gray-900 mb-4 md:mb-6 text-lg md:text-xl" x-text="selectedCategory ? 'Menu Pilihan' : 'Rekomendasi Chef'"></h3>
                        <!-- Responsive Grid -->
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-[repeat(auto-fill,minmax(180px,220px))] md:gap-4 xl:gap-5">
                            <template x-for="menu in filteredMenus" :key="menu.id">
                                <div class="bg-white rounded-2xl shadow-sm hover:shadow-lg border border-gray-100 hover:border-primary-200 overflow-hidden flex flex-col group relative transition-all duration-300 hover:-translate-y-0.5 cursor-pointer min-w-0" @click="if(menu.stock > 0) openMenuDetail(menu)">
                                    <!-- Out of stock overlay -->
                                    <div x-show="menu.stock <= 0" class="absolute inset-0 bg-white/70 z-10 flex items-center justify-center backdrop-blur-[2px]">
                                        <span class="bg-red-500 text-white text-sm md:text-base font-bold px-4 py-1.5 rounded-full shadow-lg transform -rotate-12 border-2 border-white">HABIS</span>
                                    </div>

                                    <div class="relative h-32 bg-gray-100 overflow-hidden sm:h-36 md:h-32">
                                        <img :src="menu.image_url || 'https://ui-avatars.com/api/?name='+menu.name+'&background=F3F4F6&color=9CA3AF'" class="absolute inset-0 h-full w-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                                        <!-- Rating Badge -->
                                        <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-md px-2.5 py-1 rounded-full flex items-center gap-1.5 shadow-sm border border-white/20">
                                            <svg class="w-3.5 h-3.5 text-yellow-400 drop-shadow-sm" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                            <span class="text-xs font-bold text-gray-800">4.8</span>
                                        </div>
                                    </div>
                                    <div class="p-3 md:p-3.5 flex flex-col flex-1 bg-white min-h-[154px]">
                                        <h4 class="font-bold text-gray-900 text-sm md:text-base mb-1.5 leading-snug line-clamp-2 min-h-[2.5rem] group-hover:text-primary-600 transition-colors" x-text="menu.name"></h4>
                                        <p class="text-xs text-gray-500 line-clamp-2 mb-3 min-h-[2rem]" x-text="menu.description"></p>
                                        <div class="mt-auto pt-3 border-t border-gray-50">
                                            <span class="block font-black text-primary-600 text-base md:text-lg leading-tight tracking-tight whitespace-nowrap mb-3" x-text="formatCurrency(menu.price)"></span>
                                            <button @click.stop="if(menu.stock > 0) addToCart(menu)" :disabled="menu.stock <= 0" class="w-full h-10 rounded-xl bg-primary-600 text-white flex items-center justify-center gap-2 font-bold text-sm hover:bg-primary-700 active:scale-[0.98] transition-all duration-200 disabled:opacity-50 disabled:bg-gray-200 disabled:text-gray-500 shadow-sm shadow-primary-500/20">
                                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"></path></svg>
                                                <span x-text="menu.stock > 0 ? 'Tambah' : 'Habis'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div x-show="filteredMenus.length === 0" class="text-center py-16 bg-white rounded-3xl border border-gray-100 border-dashed mt-4">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <p class="text-gray-500 font-medium text-lg">Menu tidak ditemukan.</p>
                            <button @click="selectCategory(null)" class="mt-4 text-primary-600 font-bold hover:underline">Lihat Semua Menu</button>
                        </div>
                    </div>
                </div>

                <!-- Tab: Detail Menu (Full page on mobile, Modal on desktop) -->
                <div x-show="activeTab === 'detail'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-8" x-transition:enter-end="opacity-100 transform translate-y-0" class="bg-white h-full pb-24 md:p-8 md:pb-8">
                    <template x-if="selectedMenu">
                        <div class="md:bg-white md:rounded-[2rem] md:shadow-xl md:border md:border-gray-100 md:overflow-hidden md:grid md:grid-cols-[minmax(220px,420px)_minmax(340px,1fr)] md:h-auto md:max-h-[calc(100vh-8rem)]">
                            <!-- Desktop: Two column layout for detail -->
                            <div class="w-full h-64 md:h-[420px] xl:h-[480px] md:w-auto md:min-w-0 bg-gray-100 relative group">
                                <img :src="selectedMenu.image_url || 'https://ui-avatars.com/api/?name='+selectedMenu.name+'&background=F3F4F6&color=9CA3AF'" class="w-full h-full object-cover">
                                <button @click="activeTab = 'home'" class="absolute top-4 left-4 md:top-6 md:left-6 w-10 h-10 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-gray-900 shadow-lg hover:bg-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                            </div>
                            
                            <div class="p-5 md:p-7 xl:p-8 md:w-auto min-w-0 flex flex-col h-full overflow-y-auto">
                                <div class="mb-6">
                                    <div class="inline-block px-3 py-1 bg-primary-50 text-primary-700 font-bold text-xs rounded-full mb-3 uppercase tracking-wider" x-text="categories.find(c => c.id === selectedMenu.category_id)?.name || 'Menu'"></div>
                                    <h2 class="text-3xl md:text-3xl xl:text-4xl font-black text-gray-900 mb-2 leading-tight" x-text="selectedMenu.name"></h2>
                                    <span class="font-black text-2xl md:text-3xl text-primary-600" x-text="formatCurrency(selectedMenu.price)"></span>
                                </div>
                                
                                <p class="text-gray-600 text-sm md:text-base leading-relaxed mb-8" x-text="selectedMenu.description || 'Hidangan lezat spesial dari dapur kami, disiapkan dengan bahan-bahan pilihan berkualitas.'"></p>

                                <!-- Variants -->
                                <div class="mb-8 bg-gray-50 p-5 rounded-2xl border border-gray-100">
                                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                        Pilihan Porsi
                                    </h3>
                                    <div class="space-y-3">
                                        <template x-for="option in variantOptions(selectedMenu)" :key="variantKey(option)">
                                        <label class="flex items-start justify-between gap-3 p-4 border-2 bg-white rounded-xl cursor-pointer transition-all" :class="isVariantSelected(option) ? 'border-primary-500 shadow-sm' : 'border-transparent hover:border-gray-200'">
                                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                                <div class="relative flex items-center justify-center w-5 h-5">
                                                    <input type="radio" name="variant" :value="variantKey(option)" x-model="selectedVariant" class="sr-only">
                                                    <div class="w-5 h-5 border-2 rounded-full transition-colors" :class="isVariantSelected(option) ? 'border-primary-500' : 'border-gray-300'"></div>
                                                    <div x-show="isVariantSelected(option)" class="absolute w-2.5 h-2.5 bg-primary-500 rounded-full"></div>
                                                </div>
                                                <span class="text-sm md:text-base font-bold text-gray-900 leading-snug" x-text="option.variant_name || option.name"></span>
                                            </div>
                                            <span class="flex-shrink-0 text-xs md:text-sm px-2 py-1 rounded-md" :class="Number(option.extra_price || 0) > 0 ? 'font-bold text-primary-600 bg-primary-50' : 'text-gray-500 font-medium bg-gray-100'" x-text="formatVariantPrice(option)"></span>
                                        </label>
                                        </template>
                                    </div>
                                </div>

                                <!-- Catatan -->
                                <div class="mb-10 md:mb-auto">
                                    <h3 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Catatan Khusus (Opsional)
                                    </h3>
                                    <textarea x-model="detailNote" class="w-full border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base p-4 bg-white shadow-sm transition-shadow" rows="3" placeholder="Contoh: Tolong jangan pakai bawang merah, pedas sedang saja..."></textarea>
                                </div>

                                <!-- Add to Cart -->
                                <div class="flex flex-col gap-3 mt-6 pt-6 border-t border-gray-100">
                                    <div class="grid grid-cols-[3rem_minmax(0,1fr)_3rem] items-center w-full h-12 bg-gray-100 rounded-xl p-1 border border-gray-200">
                                        <button @click="if(detailQty > 1) detailQty--" class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center text-gray-600 hover:bg-gray-50 text-xl font-medium transition-colors">-</button>
                                        <span class="text-center font-black text-lg text-gray-900" x-text="detailQty"></span>
                                        <button @click="detailQty++" class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center text-gray-600 hover:bg-gray-50 text-xl font-medium transition-colors">+</button>
                                    </div>
                                    <button @click="addToCartFromDetail()" class="w-full min-h-[54px] bg-primary-600 text-white font-bold px-4 rounded-xl shadow-lg shadow-primary-500/25 flex items-center justify-between gap-3 hover:bg-primary-700 active:scale-[0.98] transition-all">
                                        <span class="text-sm md:text-base text-left">Tambah ke Keranjang</span>
                                        <span class="text-sm md:text-base font-black whitespace-nowrap" x-text="formatCurrency(calculateDetailTotal())"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Tab: Orders / Tracking -->
                <div x-show="activeTab === 'orders'" class="p-3 sm:p-4 md:p-8 space-y-4 pb-36 md:pb-24">
                    <template x-for="order in activeOrders" :key="order.id">
                        <div class="bg-white p-4 sm:p-5 md:p-8 rounded-2xl md:rounded-[2rem] shadow-sm hover:shadow-md border border-gray-100 overflow-hidden relative transition-shadow">
                            <!-- Progress Bar Header -->
                            <div class="mb-7 sm:mb-8 relative">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4">
                                    <div class="min-w-0">
                                        <span class="text-xs text-gray-500 font-medium">Nomor Pesanan</span>
                                        <h3 class="max-w-full break-all text-lg font-black leading-tight text-gray-900 sm:text-xl" x-text="'#' + orderDisplayNumber(order)"></h3>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 sm:block sm:flex-shrink-0 sm:text-right">
                                        <span class="text-xs text-gray-500 font-medium">Status</span>
                                        <div class="inline-flex max-w-full items-center rounded-lg border border-primary-100 bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary-600 sm:mt-1 sm:text-sm" x-text="customerOrderStatusLabel(order.order_status)"></div>
                                    </div>
                                </div>
                                
                                <!-- Visual Tracker -->
                                <div class="relative mt-6">
                                    <div class="absolute z-0 h-2 rounded-full bg-gray-100" style="left: 10%; right: 10%; top: 0.625rem;">
                                        <div class="h-full rounded-full bg-primary-500 shadow-[0_0_10px_rgba(255,122,47,0.5)] transition-all duration-1000 ease-out" :style="'width: ' + getOrderProgress(order.order_status) + '%'"></div>
                                    </div>
                                    <div class="relative z-10 grid grid-cols-5 gap-1 text-center">
                                        <div class="flex min-w-0 flex-col items-center">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-500 shadow-md ring-4 ring-white">
                                                <div class="h-2 w-2 rounded-full bg-white"></div>
                                            </div>
                                            <span class="mt-3 max-w-full text-[10px] font-bold leading-tight text-gray-500 sm:text-xs">Diterima</span>
                                        </div>
                                        <div class="flex min-w-0 flex-col items-center">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full shadow-md ring-4 ring-white transition-colors duration-1000" :class="getOrderProgress(order.order_status) >= 25 ? 'bg-primary-500' : 'bg-gray-200'">
                                                <div class="h-2 w-2 rounded-full" :class="getOrderProgress(order.order_status) >= 25 ? 'bg-white' : 'bg-transparent'"></div>
                                            </div>
                                            <span class="mt-3 max-w-full text-[10px] font-bold leading-tight text-gray-500 sm:text-xs">Diproses</span>
                                        </div>
                                        <div class="flex min-w-0 flex-col items-center">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full shadow-md ring-4 ring-white transition-colors duration-1000" :class="getOrderProgress(order.order_status) >= 50 ? 'bg-primary-500' : 'bg-gray-200'">
                                                <div class="h-2 w-2 rounded-full" :class="getOrderProgress(order.order_status) >= 50 ? 'bg-white' : 'bg-transparent'"></div>
                                            </div>
                                            <span class="mt-3 max-w-full text-[10px] font-bold leading-tight text-gray-500 sm:text-xs">Dimasak</span>
                                        </div>
                                        <div class="flex min-w-0 flex-col items-center">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full shadow-md ring-4 ring-white transition-colors duration-1000" :class="getOrderProgress(order.order_status) >= 75 ? 'bg-primary-500' : 'bg-gray-200'">
                                                <div class="h-2 w-2 rounded-full" :class="getOrderProgress(order.order_status) >= 75 ? 'bg-white' : 'bg-transparent'"></div>
                                            </div>
                                            <span class="mt-3 max-w-full text-[10px] font-bold leading-tight text-gray-500 sm:text-xs">Siap Saji</span>
                                        </div>
                                        <div class="flex min-w-0 flex-col items-center">
                                            <div class="flex h-6 w-6 items-center justify-center rounded-full shadow-md ring-4 ring-white transition-colors duration-1000" :class="getOrderProgress(order.order_status) >= 100 ? 'bg-success' : 'bg-gray-200'">
                                                <svg x-show="getOrderProgress(order.order_status) >= 100" class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                            <span class="mt-3 max-w-full text-[10px] font-bold leading-tight text-gray-500 sm:text-xs">Disajikan</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QRIS Payment -->
                            <div x-show="order.payment_method === 'qris' && order.payment_status === 'pending'" class="bg-gradient-to-b from-blue-50 to-white border border-blue-100 rounded-2xl p-6 text-center mb-6 shadow-sm">
                                <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 text-blue-600 rounded-full mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                </div>
                                <h4 class="font-black text-lg text-gray-900 mb-1">Menunggu Pembayaran QRIS</h4>
                                <p class="text-sm text-gray-600 mb-5 max-w-sm mx-auto">Selesaikan pembayaran memakai instruksi dari payment gateway untuk pesanan ini.</p>
                                <div x-show="paymentQrImage(order)" class="w-48 h-48 bg-white mx-auto p-3 rounded-2xl border-2 border-dashed border-blue-200 shadow-sm flex items-center justify-center">
                                    <img :src="paymentQrImage(order)" class="w-full h-full opacity-90 mix-blend-multiply" alt="QR pembayaran">
                                </div>
                                <a x-show="paymentUrl(order)" :href="paymentUrl(order)" target="_blank" rel="noopener" class="mt-4 inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700">
                                    Buka Pembayaran
                                </a>
                                <div x-show="!paymentQrImage(order) && !paymentUrl(order)" class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-bold text-yellow-700">
                                    Instruksi pembayaran belum tersedia. Coba muat ulang pesanan.
                                </div>
                                <div class="mt-5">
                                    <span class="text-sm text-gray-500 font-medium">Total Pembayaran</span>
                                    <p class="font-black text-primary-600 text-2xl mt-1" x-text="formatCurrency(order.total_price)"></p>
                                </div>
                            </div>

                            <!-- Items -->
                            <div class="bg-gray-50 rounded-2xl p-3 sm:p-4 md:p-6">
                                <h4 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider">Detail Pesanan</h4>
                                <div class="space-y-3">
                                    <template x-for="item in order.items" :key="item.id">
                                        <div class="grid grid-cols-[2rem_minmax(0,1fr)_auto] items-center gap-3 border-b border-gray-200 pb-3 text-sm last:border-0 last:pb-0 md:text-base">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-100 bg-white font-bold text-gray-900 shadow-sm" x-text="item.quantity + 'x'"></span>
                                            <div class="min-w-0 text-gray-800">
                                                <span class="block break-words font-medium leading-snug" x-text="item.menu?.name || item.menu_name"></span>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-sm font-bold text-gray-900 md:text-base" x-text="formatCurrency(item.price_at_time * item.quantity)"></div>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-5 space-y-2 border-t border-gray-200 pt-4 text-sm">
                                    <div class="flex items-center justify-between text-gray-500">
                                        <span>Subtotal</span>
                                        <span class="font-bold text-gray-900" x-text="formatCurrency(orderSubtotal(order))"></span>
                                    </div>
                                    <div class="flex items-center justify-between text-gray-500">
                                        <span>Pajak</span>
                                        <span class="font-bold text-gray-900" x-text="formatCurrency(order.tax_amount || 0)"></span>
                                    </div>
                                    <div class="flex items-center justify-between text-gray-500">
                                        <span>Layanan</span>
                                        <span class="font-bold text-gray-900" x-text="formatCurrency(order.service_charge || 0)"></span>
                                    </div>
                                    <div x-show="Number(order.discount_amount || 0) > 0" class="flex items-center justify-between text-green-600">
                                        <span>Diskon</span>
                                        <span class="font-bold" x-text="'- ' + formatCurrency(order.discount_amount || 0)"></span>
                                    </div>
                                    <div class="flex flex-wrap items-end justify-between gap-2 border-t border-gray-200 pt-3">
                                        <span class="text-sm font-black uppercase tracking-wide text-gray-500">Total Pembayaran</span>
                                        <span class="text-right text-lg font-black text-primary-600 sm:text-xl" x-text="formatCurrency(order.total_price)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="ordersLoading" class="flex flex-col items-center justify-center text-center py-24 bg-white rounded-[2rem] border border-gray-100">
                        <svg class="h-8 w-8 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <p class="mt-4 font-bold text-gray-900">Memuat riwayat pesanan...</p>
                    </div>
                    <div x-show="activeOrders.length === 0 && !ordersLoading" class="flex flex-col items-center justify-center text-center py-24 bg-white rounded-[2rem] border border-gray-100">
                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Pesanan</h3>
                        <p class="text-gray-500 max-w-xs mx-auto">Pesanan aktif dan riwayat pesanan akun Anda akan muncul di sini.</p>
                        <button @click="activeTab = 'home'" class="mt-6 text-primary-600 font-bold hover:bg-primary-50 px-6 py-2 rounded-full transition-colors">Pesan Makanan</button>
                    </div>
                </div>

                <!-- Tab: Profile -->
                <div x-show="activeTab === 'profile'" class="p-4 md:p-8 space-y-6 md:space-y-8 pb-24 md:max-w-2xl mx-auto">
                    <!-- Auth Wall if not logged in -->
                    <template x-if="!user">
                        <div class="bg-white p-8 md:p-12 rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 text-center">
                            <div class="w-24 h-24 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <h3 class="font-black text-2xl text-gray-900 mb-3">Masuk ke Akun Anda</h3>
                            <p class="text-gray-500 mb-8 max-w-sm mx-auto leading-relaxed">Nikmati kemudahan pesan meja, kumpulkan poin loyalitas, dan dapatkan diskon spesial khusus member.</p>
                            <button @click="openAuthModal('login')" class="w-full md:w-auto md:px-12 bg-gray-900 text-white font-bold py-4 rounded-full hover:bg-gray-800 hover:shadow-lg transition-all transform hover:-translate-y-0.5">Masuk / Daftar Sekarang</button>
                        </div>
                    </template>

                    <!-- Profile Info if logged in -->
                    <template x-if="user">
                        <div class="space-y-6">
                            <div class="bg-white p-6 md:p-8 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-6">
                                <div class="w-20 h-20 bg-gradient-to-br from-primary-100 to-primary-200 rounded-full flex items-center justify-center font-black text-3xl text-primary-700 shadow-inner" x-text="user.name.charAt(0)"></div>
                                <div>
                                    <h3 class="font-black text-2xl text-gray-900 mb-1" x-text="user.name"></h3>
                                    <p class="text-gray-500 font-medium" x-text="user.email"></p>
                                    <p class="mt-1 text-sm font-bold text-gray-400" x-text="user.phone || 'Nomor telepon belum diisi'"></p>
                                    <div class="mt-3 inline-block px-3 py-1 bg-green-50 text-green-700 text-xs font-bold rounded-full border border-green-100">Member Aktif</div>
                                </div>
                            </div>

                            <!-- Loyalty Points -->
                            <div class="bg-gradient-to-br from-gray-900 via-gray-800 to-black p-8 rounded-[2rem] shadow-xl text-white relative overflow-hidden group">
                                <svg class="absolute -right-6 -bottom-6 w-48 h-48 text-white/5 group-hover:scale-110 transition-transform duration-700" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                <div class="relative z-10 flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-medium text-gray-400 mb-1 tracking-widest uppercase">RestoApp Rewards</p>
                                        <h2 class="text-4xl md:text-5xl font-black font-heading text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-yellow-500 drop-shadow-sm" x-text="user.poin + ' Poin'"></h2>
                                        <p class="text-sm text-gray-300 mt-3 max-w-xs">Tukarkan poin Anda dengan hidangan gratis atau diskon eksklusif!</p>
                                    </div>
                                    <div class="hidden md:block w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/20">
                                        <svg class="w-8 h-8 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"></path></svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Profile -->
                            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
                                <button @click="openReservations()" class="w-full flex items-center justify-between p-5 border-b border-gray-50 hover:bg-gray-50 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-transform">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <span class="font-bold text-gray-900 text-lg">Reservasi Meja</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-300 group-hover:text-primary-500 transition-colors group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                                <button @click="openOrders()" class="w-full flex items-center justify-between p-5 border-b border-gray-50 hover:bg-gray-50 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:-rotate-3 transition-transform">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        </div>
                                        <span class="font-bold text-gray-900 text-lg">Riwayat Pesanan</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-300 group-hover:text-blue-500 transition-colors group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                                <button @click="enablePushNotifications()" class="w-full flex items-center justify-between p-5 border-b border-gray-50 hover:bg-gray-50 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-transform">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 01-6 0"></path></svg>
                                        </div>
                                        <span class="font-bold text-gray-900 text-lg">Aktifkan Notifikasi</span>
                                    </div>
                                    <span class="text-sm font-black" :class="pushSubscribed ? 'text-green-600' : 'text-gray-400'" x-text="pushSubscribed ? 'Aktif' : 'Nonaktif'"></span>
                                </button>
                                <button @click="logoutCustomer()" class="w-full flex items-center justify-between p-5 hover:bg-gray-50 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-transform">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        </div>
                                        <span class="font-bold text-red-500 text-lg">Keluar Akun</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Tab: Reservations -->
                <div x-show="activeTab === 'reservations'" class="p-4 md:p-8 space-y-6 pb-24 md:max-w-4xl mx-auto">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
                        <section class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm md:p-6">
                            <div class="mb-5">
                                <h3 class="text-xl font-black text-gray-900">Buat Reservasi Meja</h3>
                                <p class="mt-1 text-sm text-gray-500">Pilih meja, tanggal, dan jam kedatangan Anda.</p>
                            </div>

                            <div x-show="reservationError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700" x-text="reservationError"></div>

                            <form @submit.prevent="createReservation()" class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700">Meja</label>
                                    <select x-model="reservationForm.table_id" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                        <option value="">Pilih meja</option>
                                        <template x-for="table in tables" :key="table.id">
                                            <option :value="table.id" :disabled="table.available === false" x-text="'Meja ' + table.table_number + (table.available === false ? ' - sudah direservasi' : (table.status === 'occupied' ? ' - sedang dipakai' : ''))"></option>
                                        </template>
                                    </select>
                                    <p class="mt-2 text-xs font-medium text-gray-500">Meja yang sudah direservasi pada tanggal dan jam yang dipilih otomatis dinonaktifkan.</p>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-gray-700">Tanggal</label>
                                        <input type="date" x-model="reservationForm.date" @change="fetchTables()" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-bold text-gray-700">Jam</label>
                                        <input type="time" x-model="reservationForm.time" @change="fetchTables()" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700">Jumlah Orang</label>
                                    <input type="number" min="1" x-model="reservationForm.number_of_people" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-bold text-gray-700">Catatan</label>
                                    <textarea rows="3" x-model="reservationForm.notes" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="Contoh: dekat jendela, kursi anak, dll."></textarea>
                                </div>

                                <div x-show="selectedReservationTableUnavailable()" class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-bold text-yellow-800">Meja ini sudah direservasi pada tanggal dan jam tersebut. Silakan pilih meja atau jam lain.</div>

                                <button type="submit" :disabled="reservationLoading || selectedReservationTableUnavailable()" class="flex w-full items-center justify-center rounded-xl bg-primary-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-primary-500/20 transition-all hover:bg-primary-700 disabled:opacity-70">
                                    <svg x-show="reservationLoading" class="-ml-1 mr-2 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-text="reservationLoading ? 'Menyimpan...' : 'Kirim Reservasi'"></span>
                                </button>
                            </form>
                        </section>

                        <section class="rounded-[2rem] border border-gray-100 bg-white p-5 shadow-sm md:p-6">
                            <div class="mb-5 flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-xl font-black text-gray-900">Reservasi Saya</h3>
                                    <p class="mt-1 text-sm text-gray-500">Status reservasi akan diperbarui oleh admin.</p>
                                </div>
                                <button @click="fetchReservations()" class="rounded-xl bg-gray-100 p-2 text-gray-500 hover:bg-gray-200 hover:text-gray-900" title="Refresh">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="{'animate-spin': reservationLoading}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </button>
                            </div>

                            <div x-show="reservations.length === 0" class="rounded-2xl border border-dashed border-gray-200 p-8 text-center">
                                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <p class="font-bold text-gray-900">Belum ada reservasi</p>
                                <p class="mt-1 text-sm text-gray-500">Reservasi yang Anda buat akan tampil di sini.</p>
                            </div>

                            <div class="space-y-3">
                                <template x-for="reservation in reservations" :key="reservation.id">
                                    <article class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-black text-gray-900">Meja <span x-text="reservation.table?.table_number || reservation.table_id"></span></p>
                                                <p class="mt-1 text-sm text-gray-500"><span x-text="formatDate(reservation.date)"></span> pukul <span x-text="reservation.time"></span></p>
                                                <p class="mt-1 text-sm text-gray-500"><span x-text="reservation.number_of_people"></span> orang</p>
                                            </div>
                                            <span class="rounded-full px-3 py-1 text-xs font-black" :class="reservationStatusClass(reservation.status)" x-text="reservationStatusLabel(reservation.status)"></span>
                                        </div>
                                        <p x-show="reservation.notes" class="mt-3 rounded-xl bg-white px-3 py-2 text-sm text-gray-600" x-text="reservation.notes"></p>
                                        <button x-show="reservation.status !== 'cancelled'" @click="cancelReservation(reservation)" class="mt-3 text-sm font-bold text-red-500 hover:text-red-600">Batalkan reservasi</button>
                                    </article>
                                </template>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <!-- Right Side: Cart (Desktop Only - acts as sidebar. On mobile it's a tab) -->
            <aside class="hidden md:flex flex-col w-[360px] xl:w-[400px] bg-white border-l border-gray-100 z-10 sticky top-[73px] h-[calc(100vh-73px)]" x-show="activeTab === 'home' || activeTab === 'detail' || activeTab === 'cart'">
                <div class="flex flex-col h-full bg-gray-50/50">
                    <div class="p-6 pb-2">
                        <h3 class="text-xl font-black text-gray-900 flex items-center gap-3">
                            <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            Keranjang Anda
                            <span x-show="cart.length > 0" class="ml-auto bg-gray-900 text-white text-xs font-bold px-2.5 py-1 rounded-full" x-text="cart.length + ' Item'"></span>
                        </h3>
                    </div>

                    <!-- Desktop Cart Content -->
                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6 scrollbar-hide">
                        <template x-if="cart.length === 0">
                            <div class="flex flex-col items-center justify-center h-full text-center opacity-70">
                                <img src="https://illustrations.popsy.co/gray/caterpillar.svg" class="w-40 h-40 opacity-50 mb-4" alt="Empty Cart">
                                <h4 class="font-bold text-gray-900 text-lg mb-1">Masih Kosong Nih</h4>
                                <p class="text-sm text-gray-500 max-w-[200px]">Yuk tambahkan menu lezat pilihanmu ke sini!</p>
                            </div>
                        </template>

                        <template x-if="cart.length > 0">
                            <div class="space-y-4">
                                <template x-for="(item, index) in cart" :key="index">
                                    <div class="flex gap-4 items-start bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:border-primary-200 transition-colors group">
                                        <img :src="item.menu.image_url" class="w-20 h-20 rounded-xl object-cover bg-gray-100 shadow-inner">
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start mb-1">
                                                <h4 class="font-bold text-sm text-gray-900 leading-tight pr-4" x-text="item.menu.name"></h4>
                                                <button @click="removeFromCart(index)" class="text-gray-300 hover:text-red-500 transition-colors absolute right-8">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                            <div class="text-[11px] text-gray-500 mb-2.5 font-medium">
                                                <span class="bg-gray-100 px-1.5 py-0.5 rounded" x-text="variantLabel(item)"></span>
                                                <template x-if="item.note">
                                                    <div class="text-orange-500 italic mt-1 line-clamp-1" :title="item.note" x-text="'Catatan: ' + item.note"></div>
                                                </template>
                                            </div>
                                            <div class="flex justify-between items-center mt-auto">
                                                <span class="font-black text-primary-600 text-sm" x-text="formatCurrency(calculateItemPrice(item))"></span>
                                                <div class="flex items-center gap-1 bg-gray-50 rounded-lg p-0.5 border border-gray-200">
                                                    <button @click="if(item.qty > 1) { item.qty--; calculateTotal(); } else { removeFromCart(index) }" class="w-7 h-7 rounded-md bg-white shadow-sm flex items-center justify-center text-gray-700 font-bold hover:bg-gray-100 transition-colors">-</button>
                                                    <span class="text-sm font-bold w-6 text-center" x-text="item.qty"></span>
                                                    <button @click="item.qty++; calculateTotal();" class="w-7 h-7 rounded-md bg-gray-900 shadow-sm flex items-center justify-center text-white font-bold hover:bg-black transition-colors">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Voucher -->
                                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mt-6">
                                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Kode Voucher</label>
                                    <div class="flex gap-2 relative">
                                        <input type="text" x-model="voucherCode" :placeholder="activePromo?.code || 'Masukkan kode'" class="flex-1 border-gray-200 rounded-xl text-sm pl-10 pr-3 py-2.5 uppercase bg-gray-50 focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-medium transition-all">
                                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                        <button @click="applyVoucher()" class="bg-gray-900 hover:bg-black text-white px-5 py-2.5 rounded-xl text-sm font-bold disabled:opacity-50 transition-colors shadow-sm" :disabled="!voucherCode">Pakai</button>
                                    </div>
                                    <div x-show="appliedDiscount > 0" x-transition class="flex items-center gap-1.5 text-green-600 text-xs mt-2.5 font-bold bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Diskon diterapkan: <span x-text="formatCurrency(appliedDiscount)"></span>
                                    </div>
                                </div>

                                <!-- Payment Method -->
                                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                                    <label class="block text-xs font-bold text-gray-500 mb-3 uppercase tracking-wider">Bayar Pakai Apa?</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-2 cursor-pointer transition-all" :class="paymentMethod === 'cash' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-100 text-gray-500 hover:border-gray-200 hover:bg-gray-50'">
                                            <input type="radio" name="payment_desktop" value="cash" x-model="paymentMethod" class="sr-only">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                            <span class="text-xs font-bold">Tunai di Kasir</span>
                                        </label>
                                        <label class="border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-2 cursor-pointer transition-all" :class="paymentMethod === 'qris' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-100 text-gray-500 hover:border-gray-200 hover:bg-gray-50'">
                                            <input type="radio" name="payment_desktop" value="qris" x-model="paymentMethod" class="sr-only">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                            <span class="text-xs font-bold">QRIS (Digital)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Desktop Checkout Area -->
                    <div x-show="cart.length > 0" class="bg-white border-t border-gray-200 p-6 shadow-[0_-10px_30px_rgba(0,0,0,0.03)] rounded-tl-3xl z-20 mt-auto">
                        <div class="space-y-2.5 text-sm text-gray-600 mb-5">
                            <div class="flex justify-between">
                                <span class="font-medium">Subtotal</span>
                                <span class="font-bold text-gray-900" x-text="formatCurrency(subtotal)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span x-text="`Pajak (${formatPercentage(taxPercentage)})`"></span>
                                <span class="font-medium" x-text="formatCurrency(tax)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span x-text="`Layanan (${formatPercentage(serviceChargePercentage)})`"></span>
                                <span class="font-medium" x-text="formatCurrency(serviceCharge)"></span>
                            </div>
                            <div class="flex justify-between text-green-600" x-show="appliedDiscount > 0">
                                <span>Diskon Promo</span>
                                <span class="font-bold" x-text="'-' + formatCurrency(appliedDiscount)"></span>
                            </div>
                            <div class="pt-3 mt-3 border-t border-dashed border-gray-200 flex justify-between items-end">
                                <span class="font-bold text-gray-500 uppercase tracking-wider text-xs">Total Pembayaran</span>
                                <span class="font-black text-2xl text-primary-600 leading-none" x-text="formatCurrency(total)"></span>
                            </div>
                        </div>
                        <button @click="checkout()" :disabled="checkoutLoading" class="w-full bg-primary-600 text-white font-black py-4 px-4 rounded-xl shadow-lg shadow-primary-500/40 flex justify-center items-center hover:bg-primary-700 hover:-translate-y-1 active:scale-95 transition-all disabled:opacity-70 disabled:hover:translate-y-0 text-lg">
                            <svg x-show="checkoutLoading" class="animate-spin -ml-1 mr-3 h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="checkoutLoading ? 'Sedang Memproses...' : 'Proses Pesanan'"></span>
                        </button>
                    </div>
                </div>
            </aside>
            
            <!-- Mobile Cart Tab Content (visible only when cart tab active on mobile) -->
            <div class="md:hidden w-full bg-gray-50 pb-32" x-show="activeTab === 'cart'">
                <template x-if="cart.length === 0">
                    <div class="flex flex-col items-center justify-center h-full text-gray-500 px-6 text-center py-20">
                        <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        <h3 class="font-bold text-lg text-gray-900 mb-1">Keranjang Kosong</h3>
                        <p class="text-sm">Yuk pilih menu favoritmu dan rasakan kelezatannya sekarang!</p>
                        <button @click="activeTab = 'home'" class="mt-6 text-primary-600 font-bold px-6 py-2 border-2 border-primary-600 rounded-full hover:bg-primary-50 transition-colors">Mulai Pesan</button>
                    </div>
                </template>

                <template x-if="cart.length > 0">
                    <div class="px-4 py-4 space-y-6">
                        <!-- Mobile Cart Items -->
                        <div class="space-y-4">
                            <template x-for="(item, index) in cart" :key="index">
                                <div class="flex gap-4 items-start bg-white p-3 rounded-2xl shadow-sm border border-gray-100">
                                    <img :src="item.menu.image_url" class="w-16 h-16 rounded-xl object-cover bg-gray-100">
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-1">
                                            <h4 class="font-bold text-sm text-gray-900" x-text="item.menu.name"></h4>
                                            <button @click="removeFromCart(index)" class="text-gray-400 hover:text-red-500 p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                        <div class="text-[11px] text-gray-500 mb-2 font-medium">
                                            <span class="bg-gray-100 px-1.5 py-0.5 rounded" x-text="variantLabel(item)"></span>
                                            <template x-if="item.note">
                                                <div class="text-orange-500 italic mt-1 line-clamp-1" x-text="'Cat: ' + item.note"></div>
                                            </template>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="font-black text-primary-600 text-sm" x-text="formatCurrency(calculateItemPrice(item))"></span>
                                            <div class="flex items-center gap-2">
                                                <button @click="if(item.qty > 1) { item.qty--; calculateTotal(); } else { removeFromCart(index) }" class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 font-bold bg-gray-50">-</button>
                                                <span class="text-sm font-bold w-4 text-center" x-text="item.qty"></span>
                                                <button @click="item.qty++; calculateTotal();" class="w-7 h-7 rounded-lg bg-gray-900 flex items-center justify-center text-white font-bold">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Mobile Voucher -->
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                            <label class="block text-[11px] font-bold text-gray-500 mb-2 uppercase tracking-widest">Punya Kode Voucher?</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="voucherCode" placeholder="Masukkan kode" class="flex-1 border-gray-200 rounded-xl text-sm px-3 py-2.5 uppercase bg-gray-50 focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all font-medium">
                                <button @click="applyVoucher()" class="bg-gray-900 text-white px-5 py-2.5 rounded-xl text-sm font-bold disabled:opacity-50 transition-colors" :disabled="!voucherCode">Pakai</button>
                            </div>
                            <div x-show="appliedDiscount > 0" class="flex items-center gap-1 mt-3 text-green-600 text-xs font-bold bg-green-50 px-3 py-2 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Diskon diterapkan: <span x-text="formatCurrency(appliedDiscount)"></span>
                            </div>
                        </div>

                        <!-- Mobile Payment -->
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                            <label class="block text-[11px] font-bold text-gray-500 mb-3 uppercase tracking-widest">Metode Pembayaran</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-2 cursor-pointer transition-all" :class="paymentMethod === 'cash' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-100 text-gray-500'">
                                    <input type="radio" name="payment_mobile" value="cash" x-model="paymentMethod" class="sr-only">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    <span class="text-xs font-bold">Tunai (Kasir)</span>
                                </label>
                                <label class="border-2 rounded-xl p-3 flex flex-col items-center justify-center gap-2 cursor-pointer transition-all" :class="paymentMethod === 'qris' ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm' : 'border-gray-100 text-gray-500'">
                                    <input type="radio" name="payment_mobile" value="qris" x-model="paymentMethod" class="sr-only">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                    <span class="text-xs font-bold">QRIS Digital</span>
                                </label>
                            </div>
                        </div>

                        <!-- Mobile Summary -->
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wider">Ringkasan Pesanan</h3>
                            <div class="space-y-3 text-sm text-gray-600">
                                <div class="flex justify-between items-center">
                                    <span>Subtotal</span>
                                    <span class="font-medium text-gray-900" x-text="formatCurrency(subtotal)"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span x-text="`Pajak (${formatPercentage(taxPercentage)})`"></span>
                                    <span class="font-medium text-gray-900" x-text="formatCurrency(tax)"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span x-text="`Layanan (${formatPercentage(serviceChargePercentage)})`"></span>
                                    <span class="font-medium text-gray-900" x-text="formatCurrency(serviceCharge)"></span>
                                </div>
                                <div class="flex justify-between items-center text-green-600 bg-green-50 p-2 rounded-lg -mx-2 px-2" x-show="appliedDiscount > 0">
                                    <span class="font-medium">Diskon</span>
                                    <span class="font-bold" x-text="'-' + formatCurrency(appliedDiscount)"></span>
                                </div>
                                <div class="pt-4 mt-2 border-t border-dashed border-gray-200 flex justify-between items-end">
                                    <span class="font-bold text-gray-900">Total Pembayaran</span>
                                    <span class="font-black text-xl text-primary-600" x-text="formatCurrency(total)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Mobile Fixed Checkout Bar -->
            <div x-show="activeTab === 'cart' && cart.length > 0" class="md:hidden fixed bottom-[4.5rem] left-0 right-0 bg-white border-t border-gray-100 p-4 z-30 shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">
                <button @click="checkout()" :disabled="checkoutLoading" class="w-full bg-primary-600 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-primary-500/30 flex justify-center items-center active:scale-95 transition-transform disabled:opacity-70 text-[15px]">
                    <svg x-show="checkoutLoading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="checkoutLoading ? 'Memproses...' : 'Pesan Sekarang - ' + formatCurrency(total)"></span>
                </button>
            </div>
        </div>
    </main>

    <!-- Table Selection Modal -->
    <div x-show="tableModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/60 p-0 backdrop-blur-sm sm:items-center sm:p-4" style="display: none;">
        <div @click="closeTableModal()" class="absolute inset-0"></div>
        <section class="relative w-full rounded-t-[2rem] bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-[2rem] sm:p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-primary-600">Meja Restoran</p>
                    <h3 class="mt-1 text-2xl font-black text-gray-900">Pilih meja Anda</h3>
                    <p class="mt-1 text-sm leading-relaxed text-gray-500">Pilih meja yang masih tersedia dari database.</p>
                </div>
                <button @click="closeTableModal()" class="rounded-full bg-gray-100 p-2 text-gray-500 hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form @submit.prevent="saveTableSelection()" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700">Nomor Meja</label>
                    <select x-model="tableForm.table_id" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Pilih meja tersedia</option>
                        <template x-for="table in availableOrderTables()" :key="table.id">
                            <option :value="table.id" x-text="'Meja ' + table.table_number"></option>
                        </template>
                    </select>
                    <p x-show="tables.length > 0 && availableOrderTables().length === 0" class="mt-2 text-xs font-bold text-red-500">Belum ada meja tersedia. Silakan hubungi pelayan.</p>
                </div>

                <div x-show="availableOrderTables().length > 0">
                    <p class="mb-2 text-xs font-black uppercase tracking-wider text-gray-400">Meja tersedia</p>
                    <div class="grid max-h-44 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4">
                        <template x-for="table in availableOrderTables()" :key="table.id">
                            <button type="button" @click="tableForm.table_id = table.id" class="rounded-xl border px-3 py-2 text-sm font-black transition-colors" :class="String(tableForm.table_id) === String(table.id) ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                                <span>Meja </span><span x-text="table.table_number"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <button type="submit" :disabled="!tableForm.table_id || availableOrderTables().length === 0" class="flex w-full items-center justify-center rounded-xl bg-primary-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-primary-500/20 transition-all hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
                    Gunakan Meja
                </button>
            </form>
        </section>
    </div>

    <!-- Customer Auth Modal -->
    <div x-show="authModalOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/60 p-0 backdrop-blur-sm sm:items-center sm:p-4" style="display: none;">
        <div @click="closeAuthModal()" class="absolute inset-0"></div>
        <section class="relative w-full rounded-t-[2rem] bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-[2rem] sm:p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-primary-600" x-text="authMode === 'login' ? 'Masuk Member' : 'Daftar Member'"></p>
                    <h3 class="mt-1 text-2xl font-black text-gray-900" x-text="authMode === 'login' ? 'Masuk ke akun' : 'Buat akun customer'"></h3>
                    <p class="mt-1 text-sm leading-relaxed text-gray-500" x-text="authMode === 'login' ? 'Gunakan akun customer untuk menyimpan poin dan riwayat pesanan.' : 'Daftar cepat untuk mulai mengumpulkan poin reward.'"></p>
                </div>
                <button @click="closeAuthModal()" class="rounded-full bg-gray-100 p-2 text-gray-500 hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div x-show="authError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700" x-text="authError"></div>

            <form @submit.prevent="submitAuth()" class="space-y-4">
                <div x-show="authMode === 'register'">
                    <label class="mb-1 block text-sm font-bold text-gray-700">Nama Lengkap</label>
                    <input type="text" x-model="authForm.name" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="Nama Anda" :required="authMode === 'register'">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700">Email</label>
                    <input type="email" x-model="authForm.email" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="customer@restoapp.com" required>
                </div>
                <div x-show="authMode === 'register'">
                    <label class="mb-1 block text-sm font-bold text-gray-700">Nomor Telepon / WhatsApp</label>
                    <input type="tel" x-model="authForm.phone" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="08xxxxxxxxxx" :required="authMode === 'register'">
                    <p class="mt-1 text-xs font-medium text-gray-400">Dipakai restoran untuk konfirmasi reservasi atau pesanan.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700">Password</label>
                    <input type="password" x-model="authForm.password" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="Minimal 8 karakter" required>
                </div>
                <div x-show="authMode === 'register'">
                    <label class="mb-1 block text-sm font-bold text-gray-700">Konfirmasi Password</label>
                    <input type="password" x-model="authForm.password_confirmation" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="Ulangi password" :required="authMode === 'register'">
                </div>

                <button type="submit" :disabled="authLoading" class="flex w-full items-center justify-center rounded-xl bg-primary-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-primary-500/20 transition-all hover:bg-primary-700 disabled:opacity-70">
                    <svg x-show="authLoading" class="-ml-1 mr-2 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="authLoading ? 'Memproses...' : (authMode === 'login' ? 'Masuk' : 'Daftar & Masuk')"></span>
                </button>
            </form>

            <div class="mt-5 text-center text-sm text-gray-500">
                <span x-text="authMode === 'login' ? 'Belum punya akun?' : 'Sudah punya akun?'"></span>
                <button @click="switchAuthMode(authMode === 'login' ? 'register' : 'login')" class="font-black text-primary-600 hover:text-primary-700" x-text="authMode === 'login' ? 'Daftar sekarang' : 'Masuk di sini'"></button>
            </div>
        </section>
    </div>

    <!-- Modern Confirmation Modal -->
    <div x-show="confirmDialog.open" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/60 p-0 backdrop-blur-sm sm:items-center sm:p-4" style="display: none;">
        <div @click="resolveConfirm(false)" class="absolute inset-0"></div>
        <section class="relative w-full rounded-t-[2rem] bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-[2rem] sm:p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl" :class="confirmDialog.variant === 'danger' ? 'bg-red-50 text-red-500' : 'bg-primary-50 text-primary-600'">
                    <svg x-show="confirmDialog.variant === 'danger'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                    <svg x-show="confirmDialog.variant !== 'danger'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-xl font-black text-gray-900" x-text="confirmDialog.title"></h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500" x-text="confirmDialog.message"></p>
                </div>
                <button @click="resolveConfirm(false)" class="rounded-full bg-gray-100 p-2 text-gray-500 hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <button @click="resolveConfirm(false)" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-black text-gray-700 transition-colors hover:bg-gray-50" x-text="confirmDialog.cancelText"></button>
                <button @click="resolveConfirm(true)" class="rounded-xl px-4 py-3 text-sm font-black text-white shadow-lg transition-colors" :class="confirmDialog.variant === 'danger' ? 'bg-red-500 shadow-red-500/20 hover:bg-red-600' : 'bg-primary-600 shadow-primary-500/20 hover:bg-primary-700'" x-text="confirmDialog.confirmText"></button>
            </div>
        </section>
    </div>

    <!-- Modern Notice Modal -->
    <div x-show="noticeDialog.open" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/60 p-0 backdrop-blur-sm sm:items-center sm:p-4" style="display: none;">
        <div @click="closeNotice()" class="absolute inset-0"></div>
        <section class="relative w-full rounded-t-[2rem] bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-[2rem] sm:p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl" :class="noticeDialog.variant === 'success' ? 'bg-green-50 text-green-600' : (noticeDialog.variant === 'danger' ? 'bg-red-50 text-red-500' : 'bg-primary-50 text-primary-600')">
                    <svg x-show="noticeDialog.variant === 'success'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="noticeDialog.variant === 'danger'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                    <svg x-show="!['success', 'danger'].includes(noticeDialog.variant)" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a6 6 0 100-12 6 6 0 000 12z"></path></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-xl font-black text-gray-900" x-text="noticeDialog.title"></h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500" x-text="noticeDialog.message"></p>
                </div>
                <button @click="closeNotice()" class="rounded-full bg-gray-100 p-2 text-gray-500 hover:bg-gray-200 hover:text-gray-900" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <button @click="closeNotice()" class="mt-6 w-full rounded-xl px-4 py-3 text-sm font-black text-white shadow-lg transition-colors" :class="noticeDialog.variant === 'danger' ? 'bg-red-500 shadow-red-500/20 hover:bg-red-600' : 'bg-primary-600 shadow-primary-500/20 hover:bg-primary-700'">
                Mengerti
            </button>
        </section>
    </div>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="md:hidden bg-white border-t border-gray-100 flex justify-around px-2 py-1.5 pb-safe fixed bottom-0 left-0 right-0 z-40 w-full shadow-[0_-10px_20px_rgba(0,0,0,0.06)]">
        <button @click="activeTab = 'home'" class="flex w-16 flex-col items-center justify-center p-1.5 transition-colors" :class="activeTab === 'home' ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600'">
            <div class="relative mb-1 transition-transform" :class="activeTab === 'home' ? 'scale-110 -translate-y-1' : ''">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="activeTab==='home' ? 'stroke-[2.5px]' : 'stroke-2'"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </div>
            <span class="text-[10px] font-bold">Beranda</span>
        </button>
        <button @click="openOrders()" class="flex w-16 flex-col items-center justify-center p-1.5 transition-colors" :class="activeTab === 'orders' ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600'">
            <div class="relative mb-1 transition-transform" :class="activeTab === 'orders' ? 'scale-110 -translate-y-1' : ''">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="activeTab==='orders' ? 'stroke-[2.5px]' : 'stroke-2'"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <span class="text-[10px] font-bold">Pesanan</span>
        </button>
        <button @click="activeTab = 'cart'" class="flex w-16 flex-col items-center justify-center p-1.5 transition-colors" :class="activeTab === 'cart' ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600'">
            <div class="relative mb-1 transition-transform" :class="activeTab === 'cart' ? 'scale-110 -translate-y-1' : ''">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="activeTab==='cart' ? 'stroke-[2.5px]' : 'stroke-2'"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span x-show="cart.length > 0" class="absolute -top-1.5 -right-2 bg-red-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full border-[1.5px] border-white min-w-[18px] text-center" x-text="cart.length"></span>
            </div>
            <span class="text-[10px] font-bold">Keranjang</span>
        </button>
        <button @click="activeTab = 'profile'" class="flex w-16 flex-col items-center justify-center p-1.5 transition-colors" :class="activeTab === 'profile' ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600'">
            <div class="relative mb-1 transition-transform" :class="activeTab === 'profile' ? 'scale-110 -translate-y-1' : ''">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="activeTab==='profile' ? 'stroke-[2.5px]' : 'stroke-2'"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <span class="text-[10px] font-bold">Profil</span>
        </button>
    </nav>
</div>
@endsection

@push('scripts')
<script>
function customerApp() {
    return {
        activeTab: 'home',
        tableId: null,
        tableStorageTtlMs: 6 * 60 * 60 * 1000,
        tables: [],
        categories: [],
        menus: [],
        activePromos: [],
        activePromo: null,
        selectedCategory: null,
        searchQuery: '',
        
        // Detail View
        selectedMenu: null,
        selectedVariant: null,
        detailQty: 1,
        detailNote: '',

        // Cart
        cart: JSON.parse(localStorage.getItem('cart') || '[]'),
        subtotal: 0,
        taxPercentage: 11,
        serviceChargePercentage: 5,
        tax: 0,
        serviceCharge: 0,
        total: 0,
        voucherCode: '',
        appliedDiscount: 0,
        paymentMethod: 'cash',
        checkoutLoading: false,
        promoClaimLoading: false,

        // Orders
        activeOrders: JSON.parse(localStorage.getItem('activeOrders') || '[]'),
        ordersLoading: false,
        activeOrdersRefreshTimer: null,

        // Reservations
        reservations: [],
        reservationLoading: false,
        reservationError: '',
        reservationForm: {
            table_id: '',
            date: '',
            time: '19:00',
            number_of_people: 2,
            notes: ''
        },
        tableModalOpen: false,
        tableForm: {
            table_id: ''
        },

        // User
        user: null, // null if guest
        authToken: localStorage.getItem('customer_token') || null,
        authModalOpen: false,
        authMode: 'login',
        authLoading: false,
        authError: '',
        pushSubscribed: false,
        customerChannel: null,
        authForm: {
            name: '',
            email: '',
            phone: '',
            password: '',
            password_confirmation: ''
        },
        confirmDialog: {
            open: false,
            title: '',
            message: '',
            confirmText: 'Ya',
            cancelText: 'Batal',
            variant: 'danger',
            resolver: null
        },
        noticeDialog: {
            open: false,
            title: '',
            message: '',
            variant: 'info'
        },

        init() {
            this.hydrateStoredTable();
            this.activeOrders = this.filterActiveOrders(this.activeOrders);
            this.saveActiveOrders();

            // Read table_id from URL query params if exists
            const urlParams = new URLSearchParams(window.location.search);
            const urlTableId = urlParams.get('table_id');
            if (urlTableId) {
                this.saveTableSelectionValue(urlTableId, { allowOccupiedCheckout: true });
                // Remove param from URL to clean it up
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            this.fetchCategories();
            this.fetchMenus();
            this.fetchActivePromos();
            this.fetchTables();
            this.fetchBillingSettings();
            this.calculateTotal();
            this.setDefaultReservationDate();
            this.refreshStoredActiveOrders();
            this.startActiveOrdersRefresh();

            this.loadSavedCustomer();
        },

        pageTitle() {
            const titles = {
                home: 'Eksplor Menu',
                orders: 'Pesanan Saya',
                profile: 'Profil Pengguna',
                reservations: 'Reservasi Meja',
                cart: 'Keranjang',
                detail: 'Detail Menu'
            };

            return titles[this.activeTab] || 'RestoApp';
        },

        setDefaultReservationDate() {
            if (this.reservationForm.date) return;

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.reservationForm.date = tomorrow.toISOString().slice(0, 10);
        },

        authHeaders(headers = {}) {
            return {
                ...headers,
                ...(this.authToken ? { 'Authorization': 'Bearer ' + this.authToken } : {})
            };
        },

        setCustomerAuthToken(token) {
            this.authToken = token;
            window.restoAuthToken = token || null;

            if (token) {
                localStorage.setItem('customer_token', token);
            } else {
                localStorage.removeItem('customer_token');
            }

            this.syncEchoAuthToken();
        },

        syncEchoAuthToken() {
            const headers = {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                ...(this.authToken ? { Authorization: `Bearer ${this.authToken}` } : {})
            };

            if (window.Echo?.connector?.options?.auth) {
                window.Echo.connector.options.auth.headers = headers;
            }

            if (window.Echo?.connector?.pusher?.config?.auth) {
                window.Echo.connector.pusher.config.auth.headers = headers;
            }
        },

        startCustomerRealtime() {
            if (!window.Echo || !this.user?.id || this.customerChannel === this.user.id) return;

            this.stopCustomerRealtime();
            this.syncEchoAuthToken();
            this.customerChannel = this.user.id;

            window.Echo.private(`customer.${this.user.id}`)
                .listen('.order.status.updated', (event) => {
                    if (event.order) {
                        this.upsertActiveOrder(event.order);
                    }
                })
                .listen('.reservation.updated', (event) => {
                    if (event.reservation) {
                        this.upsertReservation(event.reservation);
                    }
                });
        },

        stopCustomerRealtime() {
            if (!window.Echo || !this.customerChannel) return;

            window.Echo.leave(`customer.${this.customerChannel}`);
            this.customerChannel = null;
        },

        upsertActiveOrder(order) {
            const index = this.activeOrders.findIndex(item => item.id === order.id);
            const merged = index === -1 ? order : { ...this.activeOrders[index], ...order };

            if (this.isClosedCustomerOrder(merged)) {
                if (index !== -1) this.activeOrders.splice(index, 1);
            } else if (index === -1) {
                this.activeOrders.unshift(merged);
            } else {
                this.activeOrders[index] = merged;
            }

            this.saveActiveOrders();
        },

        upsertReservation(reservation) {
            const index = this.reservations.findIndex(item => item.id === reservation.id);
            if (index === -1) {
                this.reservations.unshift(reservation);
            } else {
                this.reservations[index] = { ...this.reservations[index], ...reservation };
            }
        },

        async fetchBillingSettings() {
            try {
                const res = await fetch('/api/settings/billing', {
                    headers: { 'Accept': 'application/json' }
                });

                const json = await res.json();
                if (!res.ok) return;

                this.taxPercentage = this.normalizePercentage(json.data?.tax_percentage, this.taxPercentage);
                this.serviceChargePercentage = this.normalizePercentage(
                    json.data?.service_charge_percentage,
                    this.serviceChargePercentage
                );
                this.calculateTotal();
            } catch (e) {
                console.error('Error fetching billing settings', e);
            }
        },

        normalizePercentage(value, fallback) {
            const number = Number(value);
            return Number.isFinite(number) && number >= 0 ? number : fallback;
        },

        hydrateStoredTable() {
            const storedTableId = localStorage.getItem('table_id');
            const expiresAt = parseInt(localStorage.getItem('table_id_expires_at') || '0', 10);

            if (!storedTableId || !expiresAt || Date.now() > expiresAt) {
                this.clearTableSelection();
                return;
            }

            this.tableId = storedTableId;
            this.reservationForm.table_id = storedTableId;
            this.tableForm.table_id = storedTableId;
        },

        saveTableSelectionValue(tableId, options = {}) {
            const normalizedTableId = String(tableId || '').trim();
            if (!normalizedTableId) return;

            const expiresAt = Date.now() + this.tableStorageTtlMs;
            this.tableId = normalizedTableId;
            this.reservationForm.table_id = normalizedTableId;
            this.tableForm.table_id = normalizedTableId;
            localStorage.setItem('table_id', normalizedTableId);
            localStorage.setItem('table_id_expires_at', String(expiresAt));

            if (options.allowOccupiedCheckout || this.canUseOccupiedTableSession()) {
                localStorage.setItem('table_id_allow_occupied_until', String(expiresAt));
            } else {
                localStorage.removeItem('table_id_allow_occupied_until');
            }
        },

        clearTableSelection() {
            this.tableId = null;
            this.reservationForm.table_id = '';
            this.tableForm.table_id = '';
            localStorage.removeItem('table_id');
            localStorage.removeItem('table_id_expires_at');
            localStorage.removeItem('table_id_allow_occupied_until');
        },

        confirmAction(options = {}) {
            this.confirmDialog = {
                open: true,
                title: options.title || 'Konfirmasi tindakan',
                message: options.message || 'Apakah Anda yakin ingin melanjutkan?',
                confirmText: options.confirmText || 'Ya, lanjutkan',
                cancelText: options.cancelText || 'Batal',
                variant: options.variant || 'danger',
                resolver: null
            };

            return new Promise((resolve) => {
                this.confirmDialog.resolver = resolve;
            });
        },

        resolveConfirm(confirmed) {
            const resolver = this.confirmDialog.resolver;
            this.confirmDialog.open = false;
            this.confirmDialog.resolver = null;

            if (resolver) {
                resolver(confirmed);
            }
        },

        showNotice(options = {}) {
            this.noticeDialog = {
                open: true,
                title: options.title || 'Informasi',
                message: options.message || '',
                variant: options.variant || 'info'
            };
        },

        closeNotice() {
            this.noticeDialog.open = false;
        },

        async loadSavedCustomer() {
            if (!this.authToken) {
                this.user = null;
                return;
            }

            try {
                const res = await fetch('/api/user', {
                    headers: this.authHeaders({ 'Accept': 'application/json' })
                });

                if (!res.ok) {
                    this.clearCustomerSession();
                    return;
                }

                const user = await res.json();
                if (user.role !== 'customer') {
                    this.clearCustomerSession();
                    return;
                }

                this.user = user;
                this.startCustomerRealtime();
                this.checkPushSubscription();
                await this.refreshLoyaltyBalance();
                await this.fetchReservations();
                await this.fetchCustomerOrders();
            } catch (e) {
                console.error('Gagal memuat profil customer', e);
                this.clearCustomerSession();
            }
        },

        async refreshLoyaltyBalance() {
            if (!this.authToken || !this.user) return;

            try {
                const res = await fetch('/api/customer/loyalty/balance', {
                    headers: this.authHeaders({ 'Accept': 'application/json' })
                });
                if (res.ok) {
                    const json = await res.json();
                    this.user.poin = json.data?.balance ?? this.user.poin ?? 0;
                }
            } catch (e) {
                console.error('Gagal mengambil saldo poin', e);
            }
        },

        async checkPushSubscription() {
            if (!('serviceWorker' in navigator) || !window.swRegistration) return;

            try {
                const subscription = await window.swRegistration.pushManager.getSubscription();
                this.pushSubscribed = Boolean(subscription);
            } catch (e) {
                console.error('Gagal mengecek subscription notifikasi', e);
            }
        },

        async enablePushNotifications() {
            if (!this.authToken || !this.user) {
                this.openAuthModal('login');
                return;
            }

            if (!window.subscribeToPushNotifications) {
                this.showNotice({
                    title: 'Notifikasi belum tersedia',
                    message: 'Browser belum siap menerima push notification. Coba muat ulang halaman.',
                    variant: 'danger'
                });
                return;
            }

            try {
                const keyRes = await fetch('/api/push/vapid-public-key', {
                    headers: { 'Accept': 'application/json' }
                });
                const keyJson = await keyRes.json();
                const publicKey = keyJson.public_key || '';

                if (!publicKey) {
                    this.showNotice({
                        title: 'Konfigurasi notifikasi belum lengkap',
                        message: 'VAPID public key belum diatur di server.',
                        variant: 'danger'
                    });
                    return;
                }

                const subscription = await window.subscribeToPushNotifications(publicKey);
                this.pushSubscribed = Boolean(subscription);
                this.showNotice({
                    title: this.pushSubscribed ? 'Notifikasi aktif' : 'Notifikasi tidak aktif',
                    message: this.pushSubscribed ? 'Update pesanan bisa dikirim ke perangkat ini.' : 'Izin notifikasi ditolak atau browser tidak mendukung.',
                    variant: this.pushSubscribed ? 'success' : 'info'
                });
            } catch (e) {
                console.error('Gagal mengaktifkan notifikasi', e);
                this.showNotice({
                    title: 'Notifikasi gagal diaktifkan',
                    message: 'Tidak bisa menyimpan subscription notifikasi.',
                    variant: 'danger'
                });
            }
        },

        openAuthModal(mode = 'login') {
            this.switchAuthMode(mode);
            this.authModalOpen = true;
        },

        closeAuthModal() {
            this.authModalOpen = false;
            this.authError = '';
        },

        switchAuthMode(mode) {
            this.authMode = mode;
            this.authError = '';
            this.authForm.password = '';
            this.authForm.password_confirmation = '';
        },

        async submitAuth() {
            this.authLoading = true;
            this.authError = '';

            try {
                const payload = this.authMode === 'login'
                    ? {
                        email: this.authForm.email,
                        password: this.authForm.password
                    }
                    : {
                        name: this.authForm.name,
                        email: this.authForm.email,
                        phone: this.authForm.phone,
                        password: this.authForm.password,
                        password_confirmation: this.authForm.password_confirmation
                    };

                const res = await fetch(`/api/auth/${this.authMode === 'login' ? 'login' : 'register'}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const json = await res.json();
                if (!res.ok) {
                    this.authError = json.message || Object.values(json.errors || {})?.[0]?.[0] || 'Gagal memproses akun.';
                    return;
                }

                if (json.user?.role !== 'customer') {
                    this.authError = 'Gunakan akun customer untuk masuk aplikasi pelanggan.';
                    return;
                }

                this.setCustomerAuthToken(json.access_token);
                this.user = {
                    ...json.user,
                    poin: json.user.poin ?? 0
                };
                this.closeAuthModal();
                this.startCustomerRealtime();
                this.checkPushSubscription();
                await this.refreshLoyaltyBalance();
                await this.fetchReservations();
                await this.fetchCustomerOrders();
            } catch (e) {
                console.error('Auth error', e);
                this.authError = 'Tidak bisa terhubung ke server. Coba lagi.';
            } finally {
                this.authLoading = false;
            }
        },

        async logoutCustomer() {
            const confirmed = await this.confirmAction({
                title: 'Keluar dari akun?',
                message: 'Anda akan keluar dari akun customer di perangkat ini. Keranjang lokal tetap tersimpan.',
                confirmText: 'Keluar',
                cancelText: 'Tetap masuk',
                variant: 'danger'
            });
            if (!confirmed) return;

            try {
                if (this.authToken) {
                    await fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: this.authHeaders({ 'Accept': 'application/json' })
                    });
                }
            } catch (e) {
                console.error('Logout error', e);
            } finally {
                this.clearCustomerSession();
                this.activeTab = 'profile';
            }
        },

        clearCustomerSession() {
            this.stopCustomerRealtime();
            this.user = null;
            this.setCustomerAuthToken(null);
            this.reservations = [];
            this.pushSubscribed = false;
            this.clearTableSelection();
        },

        saveActiveOrders() {
            this.activeOrders = this.filterActiveOrders(this.activeOrders);
            localStorage.setItem('activeOrders', JSON.stringify(this.activeOrders));
        },

        orderDisplayNumber(order) {
            return order?.order_number || order?.id || '-';
        },

        isClosedCustomerOrder(order) {
            if (!order) return true;

            return order.order_status === 'Dibatalkan'
                || (order.order_status === 'Disajikan' && order.payment_status === 'paid');
        },

        filterActiveOrders(orders) {
            return (Array.isArray(orders) ? orders : []).filter(order => !this.isClosedCustomerOrder(order));
        },

        isTrackableLocalOrder(order) {
            if (!order?.id || !order?.table_id) return false;

            return !this.isClosedCustomerOrder(order);
        },

        startActiveOrdersRefresh() {
            if (this.activeOrdersRefreshTimer) return;

            this.activeOrdersRefreshTimer = setInterval(() => {
                if (document.hidden || this.activeOrders.length === 0) return;

                this.refreshStoredActiveOrders({ silent: true });
            }, 10000);
        },

        async refreshStoredActiveOrders() {
            const ordersToRefresh = this.activeOrders.filter(order => {
                return this.isTrackableLocalOrder(order);
            });

            if (ordersToRefresh.length === 0) return;

            try {
                const refreshedOrders = await Promise.all(ordersToRefresh.map(async (order) => {
                    const params = new URLSearchParams({ table_id: order.table_id });
                    const res = await fetch(`/api/customer/orders/${order.id}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!res.ok) return null;

                    const json = await res.json();
                    return json.data || null;
                }));

                let changed = false;
                refreshedOrders.filter(Boolean).forEach((freshOrder) => {
                    const index = this.activeOrders.findIndex(order => order.id === freshOrder.id);
                    if (index !== -1) {
                        this.activeOrders[index] = freshOrder;
                        changed = true;
                    }
                });

                if (changed) {
                    this.saveActiveOrders();
                }
            } catch (e) {
                console.error('Gagal memperbarui nomor pesanan lokal', e);
            }
        },

        orderSubtotal(order) {
            return (order.items || []).reduce((sum, item) => {
                return sum + (Number(item.price_at_time || 0) * Number(item.quantity || 0));
            }, 0);
        },

        async openOrders() {
            this.activeTab = 'orders';
            await this.refreshStoredActiveOrders();

            if (this.user) {
                await this.fetchCustomerOrders();
            }
        },

        async fetchCustomerOrders() {
            if (!this.authToken || !this.user) return;

            this.ordersLoading = true;
            try {
                const res = await fetch('/api/customer/orders', {
                    headers: this.authHeaders({ 'Accept': 'application/json' })
                });

                if (res.ok) {
                    const json = await res.json();
                    this.activeOrders = this.filterActiveOrders(json.data || []);
                    this.saveActiveOrders();
                    this.loadPaymentInstructionsForPendingOrders();
                }
            } catch (e) {
                console.error('Gagal mengambil riwayat pesanan', e);
            } finally {
                this.ordersLoading = false;
            }
        },

        async loadPaymentInstructionsForPendingOrders() {
            if (!this.authToken || !this.user) return;

            const pendingOrders = this.activeOrders.filter(order => {
                return order.payment_method === 'qris'
                    && order.payment_status === 'pending'
                    && !this.paymentUrl(order)
                    && !this.paymentInstruction(order).qris_url;
            });

            await Promise.all(pendingOrders.map(order => this.refreshPaymentInstruction(order)));
        },

        async refreshPaymentInstruction(order) {
            try {
                const res = await fetch(`/api/customer/orders/${order.id}/payment/initiate`, {
                    method: 'POST',
                    headers: this.authHeaders({
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }),
                    body: JSON.stringify({ payment_method: order.payment_method || 'qris' })
                });

                if (!res.ok) return;

                const json = await res.json();
                const index = this.activeOrders.findIndex(item => item.id === order.id);
                if (index === -1) return;

                this.activeOrders[index] = {
                    ...this.activeOrders[index],
                    payment_method: json.data?.payment_method || this.activeOrders[index].payment_method,
                    payment_instruction: json.data || {}
                };
                this.saveActiveOrders();
            } catch (e) {
                console.error('Gagal memuat instruksi pembayaran', e);
            }
        },

        scanTable() {
            this.tableForm.table_id = this.tableId || '';
            this.tableModalOpen = true;
            this.fetchTables();
        },

        closeTableModal() {
            this.tableModalOpen = false;
        },

        availableOrderTables() {
            return this.tables.filter(table => table.status === 'available' && table.available !== false);
        },

        hasOpenOrderForTable(tableId) {
            const normalizedTableId = String(tableId || '').trim();
            if (!normalizedTableId) return false;

            return this.activeOrders.some(order => {
                const orderTableId = String(order?.table_id || '').trim();
                const isClosed = ['Disajikan', 'Dibatalkan'].includes(order?.order_status) || order?.payment_status === 'paid';

                return orderTableId === normalizedTableId && !isClosed;
            });
        },

        canUseOccupiedTableSession() {
            const allowedUntil = parseInt(localStorage.getItem('table_id_allow_occupied_until') || '0', 10);
            return Boolean(allowedUntil && Date.now() <= allowedUntil);
        },

        canCheckoutWithSelectedTable(table) {
            if (!table || table.available === false) return false;
            if (table.status === 'available') return true;

            return table.status === 'occupied'
                && (this.hasOpenOrderForTable(table.id) || this.canUseOccupiedTableSession());
        },

        selectedTable() {
            if (!this.tableId) return null;
            return this.tables.find(table => String(table.id) === String(this.tableId)) || null;
        },

        selectedTableLabel() {
            const table = this.selectedTable();
            if (table) return table.table_number;
            return this.tableId || 'Pilih Meja';
        },

        saveTableSelection() {
            const tableId = String(this.tableForm.table_id || '').trim();
            if (!tableId) return;

            const selected = this.availableOrderTables().find(table => String(table.id) === tableId);
            if (!selected) {
                this.showNotice({
                    title: 'Meja tidak tersedia',
                    message: 'Pilih meja yang masih tersedia dari daftar.',
                    variant: 'danger'
                });
                return;
            }

            this.saveTableSelectionValue(tableId);
            this.closeTableModal();
        },

        async fetchCategories() {
            try {
                const res = await fetch('/api/customer/categories', {
                    headers: { 'Accept': 'application/json' }
                });
                if(res.ok) {
                    const json = await res.json();
                    this.categories = json.data;
                }
            } catch (e) {
                console.error('Failed to fetch categories:', e);
            }
        },

        async fetchMenus() {
            try {
                const res = await fetch('/api/customer/menus', {
                    headers: { 'Accept': 'application/json' }
                });
                if(res.ok) {
                    const json = await res.json();
                    this.menus = json.data;
                    this.syncCartWithMenus();
                }
            } catch (e) {
                console.error('Failed to fetch menus:', e);
            }
        },

        async fetchActivePromos() {
            try {
                const res = await fetch('/api/promos/active', {
                    headers: { 'Accept': 'application/json' }
                });
                if(res.ok) {
                    const json = await res.json();
                    this.activePromos = json.data || [];
                    this.activePromo = this.activePromos.find(promo => promo.code) || this.activePromos[0] || null;
                }
            } catch (e) {
                console.error('Failed to fetch active promos:', e);
            }
        },

        async fetchTables() {
            try {
                const params = new URLSearchParams();
                if (this.reservationForm.date) params.append('date', this.reservationForm.date);
                if (this.reservationForm.time) params.append('time', this.reservationForm.time);

                const res = await fetch('/api/customer/tables?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const json = await res.json();
                    this.tables = json.data || [];
                }
            } catch (e) {
                console.error('Failed to fetch tables:', e);
            }
        },

        selectedReservationTableUnavailable() {
            if (!this.reservationForm.table_id) return false;

            const selected = this.tables.find(table => String(table.id) === String(this.reservationForm.table_id));
            return selected?.available === false;
        },

        async openReservations() {
            if (!this.user) {
                this.openAuthModal('login');
                return;
            }

            this.activeTab = 'reservations';
            this.reservationForm.table_id = this.reservationForm.table_id || this.tableId || '';
            this.setDefaultReservationDate();
            await Promise.all([
                this.fetchTables(),
                this.fetchReservations()
            ]);
        },

        async fetchReservations() {
            if (!this.authToken || !this.user) return;

            this.reservationLoading = true;
            try {
                const res = await fetch('/api/customer/reservations', {
                    headers: this.authHeaders({ 'Accept': 'application/json' })
                });
                if (res.ok) {
                    const json = await res.json();
                    this.reservations = json.data || [];
                }
            } catch (e) {
                console.error('Gagal mengambil reservasi', e);
            } finally {
                this.reservationLoading = false;
            }
        },

        async createReservation() {
            if (!this.user) {
                this.openAuthModal('login');
                return;
            }

            this.reservationLoading = true;
            this.reservationError = '';

            if (this.selectedReservationTableUnavailable()) {
                this.reservationError = 'Meja sudah direservasi pada tanggal dan jam tersebut. Silakan pilih meja atau waktu lain.';
                return;
            }

            try {
                const res = await fetch('/api/customer/reservations', {
                    method: 'POST',
                    headers: this.authHeaders({
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }),
                    body: JSON.stringify({
                        table_id: parseInt(this.reservationForm.table_id),
                        date: this.reservationForm.date,
                        time: this.reservationForm.time,
                        number_of_people: parseInt(this.reservationForm.number_of_people),
                        notes: this.reservationForm.notes || null
                    })
                });

                const json = await res.json();
                if (!res.ok) {
                    this.reservationError = json.message || Object.values(json.errors || {})?.[0]?.[0] || 'Gagal membuat reservasi.';
                    return;
                }

                this.reservations.unshift(json.data);
                this.reservationForm.notes = '';
                this.showNotice({
                    title: 'Reservasi terkirim',
                    message: 'Reservasi berhasil dibuat dan sedang menunggu konfirmasi admin.',
                    variant: 'success'
                });
            } catch (e) {
                console.error('Gagal membuat reservasi', e);
                this.reservationError = 'Tidak bisa membuat reservasi. Coba lagi.';
            } finally {
                this.reservationLoading = false;
            }
        },

        async cancelReservation(reservation) {
            const confirmed = await this.confirmAction({
                title: 'Batalkan reservasi?',
                message: `Reservasi Meja ${reservation.table?.table_number || reservation.table_id} pada ${this.formatDate(reservation.date)} pukul ${reservation.time} akan dibatalkan.`,
                confirmText: 'Batalkan',
                cancelText: 'Kembali',
                variant: 'danger'
            });
            if (!confirmed) return;

            try {
                const res = await fetch(`/api/customer/reservations/${reservation.id}`, {
                    method: 'DELETE',
                    headers: this.authHeaders({ 'Accept': 'application/json' })
                });

                if (res.ok) {
                    const json = await res.json();
                    const idx = this.reservations.findIndex(item => item.id === reservation.id);
                    if (idx !== -1) this.reservations[idx] = json.data;
                } else {
                    const json = await res.json();
                    this.showNotice({
                        title: 'Reservasi gagal dibatalkan',
                        message: json.message || 'Gagal membatalkan reservasi.',
                        variant: 'danger'
                    });
                }
            } catch (e) {
                console.error('Gagal membatalkan reservasi', e);
                this.showNotice({
                    title: 'Reservasi gagal dibatalkan',
                    message: 'Tidak bisa membatalkan reservasi. Coba lagi.',
                    variant: 'danger'
                });
            }
        },

        reservationStatusLabel(status) {
            return {
                pending: 'Menunggu',
                confirmed: 'Dikonfirmasi',
                cancelled: 'Dibatalkan'
            }[status] || status;
        },

        reservationStatusClass(status) {
            return {
                pending: 'bg-yellow-100 text-yellow-700',
                confirmed: 'bg-green-100 text-green-700',
                cancelled: 'bg-red-100 text-red-700'
            }[status] || 'bg-gray-100 text-gray-700';
        },

        get filteredMenus() {
            const query = this.normalizeSearch(this.searchQuery);

            return this.menus.filter(menu => {
                const matchesCategory = !this.selectedCategory || menu.category_id === this.selectedCategory;
                const searchable = this.normalizeSearch(`${menu.name || ''} ${menu.description || ''}`);
                const matchesSearch = !query || searchable.includes(query);

                return matchesCategory && matchesSearch;
            });
        },

        normalizeSearch(value) {
            return String(value || '').trim().toLowerCase();
        },

        paymentInstruction(order) {
            return order?.payment_instruction || {};
        },

        paymentUrl(order) {
            const instruction = this.paymentInstruction(order);
            return instruction.redirect_url || instruction.payment_url || '';
        },

        paymentQrImage(order) {
            const instruction = this.paymentInstruction(order);
            if (instruction.qris_url) return instruction.qris_url;

            const url = this.paymentUrl(order);
            return url ? `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(url)}` : '';
        },

        selectCategory(id) {
            this.selectedCategory = id;
        },

        openMenuDetail(menu) {
            this.selectedMenu = menu;
            const defaultVariant = this.variantOptions(menu)[0];
            this.selectedVariant = defaultVariant ? this.variantKey(defaultVariant) : null;
            this.detailQty = 1;
            this.detailNote = '';
            this.activeTab = 'detail';
            window.scrollTo(0,0);
        },

        addToCart(menu) {
            const defaultVariant = this.variantOptions(menu)[0];
            this.pushToCart({
                menu: menu,
                variant: defaultVariant ? this.variantKey(defaultVariant) : null,
                variant_id: defaultVariant?.id || null,
                variant_name: defaultVariant?.variant_name || defaultVariant?.name || null,
                variant_extra_price: Number(defaultVariant?.extra_price || 0),
                qty: 1,
                note: ''
            });
            this.showNotice({
                title: 'Masuk keranjang',
                message: 'Menu berhasil ditambahkan ke keranjang.',
                variant: 'success'
            });
        },

        addToCartFromDetail() {
            const selectedOption = this.selectedVariantOption();
            this.pushToCart({
                menu: this.selectedMenu,
                variant: this.selectedVariant,
                variant_id: selectedOption?.id || null,
                variant_name: selectedOption?.variant_name || selectedOption?.name || null,
                variant_extra_price: Number(selectedOption?.extra_price || 0),
                qty: this.detailQty,
                note: this.detailNote
            });
            this.activeTab = 'home';
            this.showNotice({
                title: 'Masuk keranjang',
                message: 'Pesanan berhasil dimasukkan ke keranjang.',
                variant: 'success'
            });
        },

        pushToCart(item) {
            // Check if exact same item exists
            const existing = this.cart.findIndex(i => i.menu.id === item.menu.id && (i.variant_id || null) === (item.variant_id || null) && this.variantLabel(i) === this.variantLabel(item) && i.note === item.note);
            if(existing !== -1) {
                this.cart[existing].qty += item.qty;
            } else {
                this.cart.push(item);
            }
            this.saveCart();
            this.calculateTotal();
        },

        syncCartWithMenus() {
            if (!Array.isArray(this.cart) || this.cart.length === 0 || !Array.isArray(this.menus) || this.menus.length === 0) {
                return;
            }

            let changed = false;
            this.cart = this.cart
                .map(item => {
                    const menu = this.menus.find(menu => String(menu.id) === String(item.menu?.id));
                    if (!menu) {
                        changed = true;
                        return null;
                    }

                    const variant = this.findMenuVariant(menu, item.variant_id, item.variant_name || item.variant_selected);
                    const normalized = {
                        ...item,
                        menu,
                        variant_id: variant?.id || null,
                        variant_name: variant?.variant_name || null,
                        variant_selected: variant?.variant_name || null,
                        variant_extra_price: Number(variant?.extra_price || 0)
                    };

                    if (JSON.stringify(normalized) !== JSON.stringify(item)) {
                        changed = true;
                    }

                    return normalized;
                })
                .filter(Boolean);

            if (changed) {
                this.saveCart();
                this.calculateTotal();
            }
        },

        findMenuVariant(menu, variantId, variantName = null) {
            const variants = Array.isArray(menu?.variants) ? menu.variants : [];

            if (variantId) {
                const byId = variants.find(variant => String(variant.id) === String(variantId));
                if (byId) return byId;
            }

            if (variantName) {
                const byName = variants.find(variant => String(variant.variant_name).toLowerCase() === String(variantName).toLowerCase());
                if (byName) return byName;
            }

            return null;
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
            this.saveCart();
            this.calculateTotal();
            if(this.cart.length === 0) this.appliedDiscount = 0;
        },

        async clearCart() {
            const confirmed = await this.confirmAction({
                title: 'Kosongkan keranjang?',
                message: 'Semua menu yang sudah dipilih akan dihapus dari keranjang.',
                confirmText: 'Kosongkan',
                cancelText: 'Batal',
                variant: 'danger'
            });
            if(!confirmed) return;
            this.cart = [];
            this.appliedDiscount = 0;
            this.saveCart();
            this.calculateTotal();
        },

        saveCart() {
            localStorage.setItem('cart', JSON.stringify(this.cart));
        },

        calculateItemPrice(item) {
            return Number(item.menu.price || 0) + Number(item.variant_extra_price || 0);
        },

        calculateDetailTotal() {
            const option = this.selectedVariantOption();
            return (Number(this.selectedMenu?.price || 0) + Number(option?.extra_price || 0)) * this.detailQty;
        },

        variantOptions(menu) {
            const variants = Array.isArray(menu?.variants) ? menu.variants : [];

            if (variants.length > 0) {
                return variants;
            }

            return [{
                id: null,
                variant_name: 'Regular Porsi',
                extra_price: 0
            }];
        },

        variantKey(option) {
            return option?.id ? String(option.id) : 'regular';
        },

        isVariantSelected(option) {
            return this.selectedVariant === this.variantKey(option);
        },

        selectedVariantOption() {
            if (!this.selectedMenu) return null;

            return this.variantOptions(this.selectedMenu).find(option => this.isVariantSelected(option))
                || this.variantOptions(this.selectedMenu)[0]
                || null;
        },

        formatVariantPrice(option) {
            const extraPrice = Number(option?.extra_price || 0);
            return extraPrice > 0 ? '+ ' + this.formatCurrency(extraPrice) : 'Harga Normal';
        },

        variantLabel(item) {
            return item.variant_name || item.variant_selected || 'Regular';
        },

        calculateTotal() {
            this.subtotal = this.cart.reduce((sum, item) => sum + (this.calculateItemPrice(item) * item.qty), 0);

            const discount = Math.min(Math.max(Number(this.appliedDiscount || 0), 0), this.subtotal);
            const taxableBase = Math.max(this.subtotal - discount, 0);

            this.tax = taxableBase * (this.taxPercentage / 100);
            this.serviceCharge = taxableBase * (this.serviceChargePercentage / 100);
            this.total = taxableBase + this.tax + this.serviceCharge;
        },

        promoTitle(promo) {
            if (!promo) return '';

            if (promo.type === 'percentage') {
                const value = Number(promo.value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
                return `Diskon ${value}%`;
            }

            return `Diskon ${this.formatCurrency(promo.value || 0)}`;
        },

        promoDescription(promo) {
            if (!promo) return '';

            const minimum = Number(promo.min_purchase || 0);
            const minimumText = minimum > 0 ? ` Minimal belanja ${this.formatCurrency(minimum)}.` : '';
            const codeText = promo.code ? ` dengan kode ${promo.code}` : '';
            return `${promo.name || 'Promo spesial'}${codeText}.${minimumText}`;
        },

        async claimPromo(code = '') {
            if (!code) return;

            this.voucherCode = String(code).trim().toUpperCase();

            if(this.cart.length === 0 || this.subtotal <= 0) {
                this.showNotice({
                    title: 'Voucher disiapkan',
                    message: `Voucher ${this.voucherCode} sudah disiapkan. Tambahkan menu terlebih dahulu, lalu tekan Pakai di keranjang.`,
                    variant: 'info'
                });
                return;
            }

            await this.applyVoucher();
        },

        async applyVoucher() {
            const code = String(this.voucherCode || '').trim().toUpperCase();
            if(!code) return;

            this.voucherCode = code;

            if(this.cart.length === 0 || this.subtotal <= 0) {
                this.showNotice({
                    title: 'Keranjang masih kosong',
                    message: 'Tambahkan menu ke keranjang terlebih dahulu sebelum memakai voucher.',
                    variant: 'info'
                });
                return;
            }

            try {
                const res = await fetch('/api/voucher/validate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        code: this.voucherCode,
                        cart_total: this.subtotal
                    })
                });
                
                const json = await res.json();
                if(res.ok) {
                    this.appliedDiscount = json.discount_amount || json.data?.discount_amount || 0;
                    this.calculateTotal();
                    this.showNotice({
                        title: 'Voucher digunakan',
                        message: 'Voucher berhasil digunakan untuk pesanan ini.',
                        variant: 'success'
                    });
                } else {
                    this.showNotice({
                        title: 'Voucher tidak valid',
                        message: json.message || 'Kode voucher tidak valid atau sudah kedaluwarsa.',
                        variant: 'danger'
                    });
                    this.appliedDiscount = 0;
                    this.calculateTotal();
                }
            } catch (e) {
                console.error(e);
                this.showNotice({
                    title: 'Voucher gagal dicek',
                    message: 'Tidak bisa memvalidasi voucher. Coba lagi.',
                    variant: 'danger'
                });
            }
        },

        async checkout() {
            this.hydrateStoredTable();

            if (!this.authToken || !this.user) {
                this.showNotice({
                    title: 'Login diperlukan',
                    message: 'Masuk atau daftar akun customer sebelum membuat pesanan.',
                    variant: 'info'
                });
                this.openAuthModal('login');
                return;
            }

            if(!this.tableId) {
                this.showNotice({
                    title: 'Meja belum dipilih',
                    message: 'Silakan pilih meja yang tersedia di halaman Beranda. Pilihan meja otomatis kedaluwarsa setelah 6 jam.',
                    variant: 'info'
                });
                this.activeTab = 'home';
                return;
            }

            await this.fetchTables();
            const selected = this.tables.find(table => String(table.id) === String(this.tableId));
            if (!this.canCheckoutWithSelectedTable(selected)) {
                this.clearTableSelection();
                this.showNotice({
                    title: 'Meja tidak tersedia',
                    message: 'Meja yang dipilih sudah tidak tersedia untuk sesi Anda. Silakan pilih meja lain.',
                    variant: 'danger'
                });
                this.activeTab = 'home';
                return;
            }

            this.checkoutLoading = true;
            
            try {
                const payload = {
                    table_id: parseInt(this.tableId),
                    order_type: 'dine_in',
                    payment_method: this.paymentMethod,
                    voucher_code: this.appliedDiscount > 0 ? this.voucherCode : null,
                    items: this.cart.map(c => {
                        const currentMenu = this.menus.find(menu => String(menu.id) === String(c.menu.id));
                        const variant = this.findMenuVariant(currentMenu, c.variant_id, c.variant_name || c.variant_selected);

                        return {
                            menu_id: c.menu.id,
                            quantity: c.qty,
                            variant_id: variant?.id || null,
                            variant_selected: variant?.variant_name || null,
                            note: c.note || null
                        };
                    })
                };

                const res = await fetch('/api/customer/orders', {
                    method: 'POST',
                    headers: this.authHeaders({ 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }),
                    body: JSON.stringify(payload)
                });

                if(res.ok) {
                    const json = await res.json();
                    let orderData = json.data;

                    if (this.paymentMethod) {
                        const paymentRes = await fetch(`/api/customer/orders/${orderData.id}/payment/initiate`, {
                            method: 'POST',
                            headers: this.authHeaders({
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }),
                            body: JSON.stringify({ payment_method: this.paymentMethod })
                        });

                        if (paymentRes.ok) {
                            const paymentJson = await paymentRes.json();
                            orderData.payment_method = paymentJson.data.payment_method;
                            orderData.payment_instruction = paymentJson.data;
                        }
                    }
                    
                    // Add or update active order with data from backend
                    const activeOrderIndex = this.activeOrders.findIndex(order => order.id === orderData.id);
                    if (activeOrderIndex !== -1) {
                        this.activeOrders[activeOrderIndex] = orderData;
                    } else {
                        this.activeOrders.unshift(orderData);
                    }
                    this.saveActiveOrders();

                    this.cart = [];
                    this.saveCart();
                    this.saveTableSelectionValue(this.tableId);
                    this.appliedDiscount = 0;
                    this.calculateTotal();
                    await this.refreshLoyaltyBalance();
                    await this.fetchCustomerOrders();
                    
                    this.activeTab = 'orders';
                    window.scrollTo(0,0);
                } else {
                    const error = await res.json();
                    this.showNotice({
                        title: 'Pesanan gagal dibuat',
                        message: error.message || 'Periksa stok dan keranjang Anda.',
                        variant: 'danger'
                    });
                }
            } catch (e) {
                console.error('Checkout error:', e);
                this.showNotice({
                    title: 'Pesanan gagal diproses',
                    message: 'Terjadi kesalahan saat memproses pesanan. Coba lagi.',
                    variant: 'danger'
                });
            } finally {
                this.checkoutLoading = false;
            }
        },

        getOrderProgress(status) {
            switch(status) {
                case 'Diterima': return 0;
                case 'Diproses': return 25;
                case 'Dimasak': return 50;
                case 'Selesai': return 75;
                case 'Disajikan': return 100;
                default: return 0;
            }
        },

        customerOrderStatusLabel(status) {
            return {
                Selesai: 'Siap Disajikan',
            }[status] || status;
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
        },

        formatPercentage(value) {
            return `${Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })}%`;
        },

        formatDate(value) {
            if (!value) return '-';

            return new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            }).format(new Date(value + 'T00:00:00'));
        }
    }
}
</script>
@endpush
