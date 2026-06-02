{{--
    <x-admob::banner slot="home_footer" position="bottom" />

    Convenience wrapper for a banner ad. The banner itself is a NATIVE overlay
    anchored to the screen (top|bottom) - this component renders no visible
    pixels. On render it loads + shows the banner for the given slot. When the
    page is navigated away from, it tears the overlay down by calling
    Admob.HideBanner through NativePHP's JS bridge (POST /_native/api/call) in
    response to the configured navigation events.

    Auto-hide listens on BOTH window and document (Livewire dispatches
    livewire:navigating on window; Inertia dispatches inertia:* on document) and
    cleans the listeners up on teardown via an AbortController, so nothing
    accumulates across SPA navigations. The events come from
    config('admob.banner.hide_on_events'). No Livewire dependency: the events are
    configurable ([] to disable), and you can always drive show/hide manually via
    the facade. Inertia/Vue/React SPAs should prefer the <admob-banner> Web
    Component (JS API), whose own connect/disconnect lifecycle drives show/hide.

    Attributes:
      slot     - the configured banner slot name (required). Read from
                 $attributes because `slot` is a reserved Blade component variable.
      position - 'bottom' (default) | 'top'
--}}
@props(['position' => 'bottom'])

@php
    $admobSlot = (string) $attributes->get('slot');
    app('admob')->banner($admobSlot)->load()->show($position);
    $hideOn = (array) config('admob.banner.hide_on_events', ['livewire:navigating', 'inertia:before', 'pagehide']);
@endphp

<div
    wire:key="admob-banner-{{ $admobSlot }}"
    x-data="{
        _ac: null,
        hideBanner() {
            fetch('/_native/api/call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                },
                body: JSON.stringify({ method: 'Admob.HideBanner', params: { slot: @js($admobSlot) } }),
            }).catch(() => {});
        }
    }"
    x-init="
        _ac = new AbortController();
        const opts = { signal: _ac.signal };
        @foreach ($hideOn as $event)
            window.addEventListener(@js($event), () => hideBanner(), opts);
            document.addEventListener(@js($event), () => hideBanner(), opts);
        @endforeach
    "
    x-on:destroy="_ac && _ac.abort()"
></div>
