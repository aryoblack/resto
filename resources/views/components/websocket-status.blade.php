{{--
    WebSocket Connection Status Indicator

    Displays a real-time indicator of the WebSocket connection state.
    Uses Alpine.js to react to the custom `ws:state-changed` DOM event
    dispatched by resources/js/bootstrap.js.

    States:
      - connected    → green dot + "Terhubung"
      - connecting   → yellow dot + "Menghubungkan..."
      - disconnected → red dot   + "Terputus"
      - unavailable  → red dot   + "Tidak Tersedia"
      - disabled     → gray dot  + "Realtime nonaktif"

    Usage:
      <x-websocket-status />
      <x-websocket-status :show-label="false" />

    Validates: Requirements 17.5 (connection status indicator)
--}}

@props([
    /** Whether to show the text label next to the dot indicator */
    'showLabel' => true,
])

<div
    x-data="{
        state: window.wsConnectionState ?? 'disabled',
        init() {
            window.addEventListener('ws:state-changed', (e) => {
                this.state = e.detail.state;
            });
        },
        get isVisible() {
            return this.state === 'connected';
        },
        get dotClass() {
            return {
                connected:    'bg-green-500',
                connecting:   'bg-yellow-400 animate-pulse',
                disconnected: 'bg-red-500',
                unavailable:  'bg-red-500',
                disabled:     'bg-gray-400',
            }[this.state] ?? 'bg-gray-400';
        },
        get label() {
            return {
                connected:    'Terhubung',
                connecting:   'Menghubungkan...',
                disconnected: 'Terputus',
                unavailable:  'Tidak Tersedia',
                disabled:     'Realtime nonaktif',
            }[this.state] ?? 'Tidak Diketahui';
        },
        get labelClass() {
            return {
                connected:    'text-green-700',
                connecting:   'text-yellow-700',
                disconnected: 'text-red-700',
                unavailable:  'text-red-700',
                disabled:     'text-gray-500',
            }[this.state] ?? 'text-gray-600';
        },
    }"
    x-show="isVisible"
    style="display: none;"
    class="inline-flex items-center gap-1.5"
    :title="'WebSocket: ' + label"
    role="status"
    :aria-label="'Status koneksi WebSocket: ' + label"
>
    {{-- Status dot --}}
    <span
        class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0"
        :class="dotClass"
    ></span>

    {{-- Optional text label --}}
    @if ($showLabel)
        <span
            class="text-xs font-medium"
            :class="labelClass"
            x-text="label"
        ></span>
    @endif
</div>
