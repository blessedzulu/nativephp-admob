{{--
    <x-admob::banner slot="home_footer" position="bottom" />

    Convenience wrapper for a banner ad. The banner itself is a NATIVE overlay
    anchored to the screen (top|bottom) - this component renders no visible
    pixels. It is fully client-driven: on init it loads + shows the banner ONCE
    (not on every Livewire re-render), and on navigation it hides the overlay.
    Every action goes through the /_admob/call endpoint, which runs the PHP
    Admob facade, so slot resolution + the consent gate + frequency caps + the
    ADMOB_ENABLED kill-switch all apply. Requires config('admob.js_api') (the
    default). No Livewire dependency: the teardown events are configurable, and
    you can always drive show/hide manually via the facade.

    Auto-hide listens on BOTH window and document (Livewire dispatches
    livewire:navigating on window; Inertia dispatches inertia:* on document) and
    cleans the listeners up on teardown via an AbortController. Inertia/Vue/React
    SPAs should prefer the <admob-banner> Web Component (JS API) whose own
    connect/disconnect lifecycle drives show/hide.

    Attributes:
      slot     - the configured banner slot name (required). Read from
                 $attributes because `slot` is a reserved Blade component variable.
      position - 'bottom' (default) | 'top'
      offset   - extra gap (dp) from the screen edge, to clear chrome like a
                 native bottom-nav. Defaults to config('admob.banner.offset.*').
--}}
@props(['position' => 'bottom', 'offset' => null])

@php
    $admobSlot = (string) $attributes->get('slot');
    $endpoint = '/'.ltrim((string) config('admob.js_api_prefix', '_admob'), '/').'/call';
    $hideOn = (array) config('admob.banner.hide_on_events', ['livewire:navigating', 'inertia:before', 'pagehide']);
    $showExtra = ['position' => $position];
    if (! is_null($offset)) {
        $showExtra['offset'] = (int) $offset;
    }
@endphp

<div
    wire:key="admob-banner-{{ $admobSlot }}"
    x-data="{
        _ac: null,
        _call(action, extra = {}) {
            const run = () => fetch(@js($endpoint), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                },
                body: JSON.stringify({ kind: 'ad', format: 'banner', slot: @js($admobSlot), action, ...extra }),
            }).catch(() => {});
            // Serialize onto the shared queue so concurrent banners don't race
            // NativePHP's URL-keyed body capture (which would 422 some calls).
            const q = (window.__admobCallQueue || Promise.resolve()).then(run, run);
            window.__admobCallQueue = q.catch(() => {});
            return q;
        },
        async _mount() {
            await this._call('load');
            await this._call('show', @js($showExtra));
        }
    }"
    x-init="
        if (@js($admobSlot)) { _mount(); }
        _ac = new AbortController();
        const opts = { signal: _ac.signal };
        @foreach ($hideOn as $event)
            window.addEventListener(@js($event), () => _call('hide'), opts);
            document.addEventListener(@js($event), () => _call('hide'), opts);
        @endforeach
    "
    x-on:destroy="_ac && _ac.abort()"
></div>
