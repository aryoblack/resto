@props([
    'name' => null,
    'model',
    'options',
    'value' => 'option.id',
    'label' => 'option.name',
    'description' => "''",
    'placeholder' => 'Pilih data',
    'searchPlaceholder' => 'Cari...',
    'emptyText' => 'Data tidak ditemukan',
    'nullable' => false,
    'nullLabel' => 'Tidak ada',
    'onSelect' => '',
])

<div
    x-data="{
        open: false,
        search: '',
        placeholder: @js($placeholder),
        searchPlaceholder: @js($searchPlaceholder),
        emptyText: @js($emptyText),
        nullLabel: @js($nullLabel),
        get options() {
            return {!! $options !!} || [];
        },
        get filteredOptions() {
            const query = String(this.search || '').trim().toLowerCase();
            if (!query) return this.options;

            return this.options.filter((option) => {
                const label = String({!! $label !!}).toLowerCase();
                const description = String({!! $description !!}).toLowerCase();

                return label.includes(query) || description.includes(query);
            });
        },
        get selectedOption() {
            return this.options.find((option) => String({!! $value !!}) === String({!! $model !!})) || null;
        },
        labelFor(option) {
            return String({!! $label !!});
        },
        descriptionFor(option) {
            return String({!! $description !!});
        },
        select(option) {
            {!! $model !!} = String({!! $value !!});
            this.open = false;
            this.search = '';
            {!! $onSelect !!}
            this.$dispatch('searchable-select-selected', { value: {!! $value !!}, option });
        },
        clear() {
            {!! $model !!} = '';
            this.open = false;
            this.search = '';
            {!! $onSelect !!}
            this.$dispatch('searchable-select-selected', { value: '', option: null });
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.$refs.search?.focus());
            }
        }
    }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    @if($name)
        <input type="hidden" name="{{ $name }}" :value="{!! $model !!}">
    @endif

    <button
        type="button"
        @click="toggle()"
        class="flex min-h-12 w-full items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-left text-sm font-bold text-gray-900 shadow-sm transition hover:border-primary-200 focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-500/10"
        :class="open ? 'border-primary-500 ring-4 ring-primary-500/10' : ''"
    >
        <span class="min-w-0 flex-1 truncate" :class="selectedOption || {!! $nullable ? 'true' : 'false' !!} ? 'text-gray-900' : 'text-gray-400'">
            <span x-text="selectedOption ? labelFor(selectedOption) : (({!! $nullable ? 'true' : 'false' !!} && String({!! $model !!}) === '') ? nullLabel : placeholder)"></span>
        </span>
        <svg class="h-5 w-5 flex-shrink-0 text-gray-400 transition-transform" :class="open ? 'rotate-180 text-primary-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="translate-y-1 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-1 opacity-0"
        class="absolute z-[130] mt-2 w-full overflow-hidden rounded-2xl border border-primary-100 bg-white shadow-2xl ring-1 ring-black/5"
        style="display: none;"
    >
        <div class="border-b border-gray-100 p-2">
            <div class="flex h-10 items-center rounded-xl bg-gray-50 px-3 ring-1 ring-gray-100 focus-within:bg-white focus-within:ring-primary-200">
                <svg class="mr-2 h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input
                    x-ref="search"
                    type="text"
                    x-model="search"
                    :placeholder="searchPlaceholder"
                    class="h-full min-w-0 flex-1 border-0 bg-transparent p-0 text-sm font-semibold text-gray-900 outline-none placeholder:text-gray-400 focus:border-0 focus:outline-none focus:ring-0"
                >
            </div>
        </div>

        <div class="max-h-72 overflow-y-auto p-1">
            @if($nullable)
                <button
                    type="button"
                    @click="clear()"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-bold transition hover:bg-primary-50"
                    :class="String({!! $model !!}) === '' ? 'bg-primary-50 text-primary-700' : 'text-gray-700'"
                >
                    <span x-text="nullLabel"></span>
                    <svg x-show="String({!! $model !!}) === ''" class="h-4 w-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            @endif

            <template x-for="option in filteredOptions" :key="String({!! $value !!})">
                <button
                    type="button"
                    @click="select(option)"
                    class="flex w-full items-start justify-between gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-primary-50"
                    :class="String({!! $value !!}) === String({!! $model !!}) ? 'bg-primary-50 text-primary-700' : 'text-gray-900'"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-black" x-text="labelFor(option)"></span>
                        <span x-show="descriptionFor(option)" class="mt-0.5 block truncate text-xs font-semibold text-gray-500" x-text="descriptionFor(option)"></span>
                    </span>
                    <svg x-show="String({!! $value !!}) === String({!! $model !!})" class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            </template>

            <div x-show="filteredOptions.length === 0" class="px-3 py-6 text-center text-sm font-semibold text-gray-500">
                <span x-text="emptyText"></span>
            </div>
        </div>
    </div>
</div>
