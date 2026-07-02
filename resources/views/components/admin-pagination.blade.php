@props([
    'mode' => 'card',
    'show' => 'pagination.total > 0',
    'from' => 'pagination.from || 0',
    'to' => 'pagination.to || 0',
    'total' => 'pagination.total',
    'label' => 'data',
    'current' => 'pagination.current_page',
    'last' => 'pagination.last_page',
    'prevClick' => 'changePage(pagination.current_page - 1)',
    'nextClick' => 'changePage(pagination.current_page + 1)',
    'prevDisabled' => 'pagination.current_page <= 1',
    'nextDisabled' => 'pagination.current_page >= pagination.last_page',
])

@php
    $baseClass = $mode === 'footer'
        ? 'border-t border-primary-100 bg-primary-50/40 p-3 sm:flex sm:items-center sm:justify-between sm:gap-4 sm:px-4'
        : 'rounded-card border border-primary-100 bg-white p-3 shadow-card sm:flex sm:items-center sm:justify-between sm:gap-4 sm:px-4';
@endphp

<div {{ $attributes->merge(['class' => $baseClass]) }} x-show="{{ $show }}">
    <p class="text-center text-xs font-bold text-text-secondary sm:text-left sm:text-sm">
        <span class="inline-flex items-center justify-center rounded-button bg-white px-3 py-1.5 ring-1 ring-primary-100 sm:hidden">
            <span x-text="{{ $from }}"></span>-<span x-text="{{ $to }}"></span> dari <span x-text="{{ $total }}"></span> {{ $label }}
        </span>
        <span class="hidden sm:inline">
            Menampilkan <span class="font-black text-text" x-text="{{ $from }}"></span> hingga <span class="font-black text-text" x-text="{{ $to }}"></span> dari <span class="font-black text-text" x-text="{{ $total }}"></span> {{ $label }}
        </span>
    </p>
    <div class="mt-3 flex items-center gap-2 sm:mt-0 sm:w-auto sm:min-w-72">
        <button @click="{{ $prevClick }}" :disabled="{{ $prevDisabled }}" class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-button border border-primary-100 bg-white text-primary-700 shadow-sm transition hover:border-primary-500 hover:bg-primary-600 hover:text-white focus:outline-none focus:ring-4 focus:ring-primary-100 disabled:cursor-not-allowed disabled:border-gray-100 disabled:bg-gray-50 disabled:text-gray-300 disabled:shadow-none disabled:hover:bg-gray-50 disabled:hover:text-gray-300" title="Halaman sebelumnya" aria-label="Halaman sebelumnya">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <div class="min-w-0 flex-1 rounded-card bg-primary-600 px-3 py-2 text-center text-white shadow-sm shadow-primary-500/20 ring-1 ring-primary-500">
            <div class="text-[10px] font-black uppercase tracking-wide text-primary-100 sm:hidden">Halaman</div>
            <div class="text-sm font-black">
                <span class="hidden sm:inline">Halaman </span>
                <span x-text="{{ $current }}"></span>
                <span class="text-primary-100">/</span>
                <span x-text="{{ $last }}"></span>
            </div>
        </div>
        <button @click="{{ $nextClick }}" :disabled="{{ $nextDisabled }}" class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-button border border-primary-100 bg-white text-primary-700 shadow-sm transition hover:border-primary-500 hover:bg-primary-600 hover:text-white focus:outline-none focus:ring-4 focus:ring-primary-100 disabled:cursor-not-allowed disabled:border-gray-100 disabled:bg-gray-50 disabled:text-gray-300 disabled:shadow-none disabled:hover:bg-gray-50 disabled:hover:text-gray-300" title="Halaman berikutnya" aria-label="Halaman berikutnya">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"></path></svg>
        </button>
    </div>
</div>
