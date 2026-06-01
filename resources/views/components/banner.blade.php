{{--
    <x-admob::banner slot="home_footer" position="bottom" />

    Convenience wrapper for a banner ad. The banner itself is a NATIVE overlay
    anchored to the screen (top|bottom) - this component renders no visible
    pixels. On render it loads + shows the banner for the given slot. When the
    page is navigated away from, it tears the overlay down by calling
    Admob.HideBanner through NativePHP's JS bridge (POST /_native/api/call) in
    response to the configured DOM events (config('admob.banner.hide_on_events'),
    default ['livewire:navigating']). No Livewire dependency: the event names are
    configurable, and you can always drive show/hide manually via the facade.

    Attributes:
      slot     - the configured banner slot name (required). Read from
                 $attributes because `slot` is a reserved Blade component variable.
      position - 'bottom' (default) | 'top'
--}}
@props(['position' => 'bottom'])

@php
    $admobSlot = (string) $attributes->get('slot');
    app('admob')->banner($admobSlot)->load()->show($position);
    $hideOn = (array) config('admob.banner.hide_on_events', ['livewire:navigating']);
@endphp

<div
    wire:key="admob-banner-{{ $admobSlot }}"
    x-data="{
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
    @foreach ($hideOn as $event)
        x-on:{{ $event }}.window="hideBanner()"
    @endforeach
></div>
