/**
 * NativePHP AdMob - JavaScript API
 *
 * Drive ads from JS (Inertia / Vue / React / vanilla) without writing
 * Livewire or Blade. Every call hits a thin same-origin endpoint
 * (POST {prefix}/call) that runs the PHP Admob facade, so slot resolution,
 * the UMP consent gate, frequency caps, and the ADMOB_ENABLED kill-switch all
 * apply server-side - this module duplicates none of that logic.
 *
 * Ad lifecycle events are delivered by the NativePHP runtime as `native-event`
 * DOM events. Subscribe with On/Off imported from the runtime and the Events
 * map exported here:
 *
 *   import { On } from '@nativephp/mobile';   // or your runtime import path
 *   import { Admob, Events } from './vendor/admob/admob.js';
 *
 *   On(Events.AdLoaded, ({ slot, format }) => console.log('loaded', slot));
 *   await Admob.interstitial('level_complete').load();
 *   if (await Admob.interstitial('level_complete').isReady()) {
 *       await Admob.interstitial('level_complete').show();
 *   }
 *
 * @example Web Component (works in Vue/React/vanilla):
 *   <admob-banner slot="home_footer" position="bottom"></admob-banner>
 */

// Endpoint prefix. If you changed config('admob.js_api_prefix'), override this
// before first use via setEndpoint('/your-prefix/call').
let endpoint = '/_admob/call';

export function setEndpoint(url) {
    endpoint = url;
}

async function call(body) {
    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(body),
        });

        return res.ok ? await res.json() : { ok: false, error: `http_${res.status}` };
    } catch (e) {
        return { ok: false, error: 'network_error' };
    }
}

function fullScreen(format, slot) {
    return {
        load: () => call({ kind: 'ad', format, slot, action: 'load' }),
        show: () => call({ kind: 'ad', format, slot, action: 'show' }),
        isReady: () => call({ kind: 'ad', format, slot, action: 'isReady' }).then((r) => r.ready === true),
    };
}

export const Admob = {
    banner: (slot) => ({
        load: () => call({ kind: 'ad', format: 'banner', slot, action: 'load' }),
        show: (position = 'bottom') => call({ kind: 'ad', format: 'banner', slot, action: 'show', position }),
        hide: () => call({ kind: 'ad', format: 'banner', slot, action: 'hide' }),
    }),
    interstitial: (slot) => fullScreen('interstitial', slot),
    rewarded: (slot) => fullScreen('rewarded', slot),
    rewardedInterstitial: (slot) => fullScreen('rewarded_interstitial', slot),
    appOpen: (slot) => fullScreen('app_open', slot),

    ump: {
        requestInfo: () => call({ kind: 'ump', action: 'requestInfo' }),
        showForm: () => call({ kind: 'ump', action: 'showForm' }),
        canRequestAds: () => call({ kind: 'ump', action: 'canRequestAds' }).then((r) => r.can_request === true),
        status: () => call({ kind: 'ump', action: 'status' }).then((r) => r.status ?? 'unknown'),
        reset: () => call({ kind: 'ump', action: 'reset' }),
    },

    att: {
        request: () => call({ kind: 'att', action: 'request' }),
        status: () => call({ kind: 'att', action: 'status' }).then((r) => r.status ?? 'unsupported'),
    },
};

/**
 * Fully-qualified PHP event-class names, as delivered to JS via `native-event`.
 * Use with On()/Off() from the NativePHP runtime.
 *
 * Note: AdShowThrottled is dispatched PHP-side (when a frequency cap blocks a
 * show); it does not arrive over `native-event` unless your app re-broadcasts
 * it. The constant is provided for completeness.
 */
export const Events = {
    AdLoaded: 'BlessedZulu\\NativePhpAdmob\\Events\\AdLoaded',
    AdFailedToLoad: 'BlessedZulu\\NativePhpAdmob\\Events\\AdFailedToLoad',
    AdShown: 'BlessedZulu\\NativePhpAdmob\\Events\\AdShown',
    AdDismissed: 'BlessedZulu\\NativePhpAdmob\\Events\\AdDismissed',
    AdFailedToShow: 'BlessedZulu\\NativePhpAdmob\\Events\\AdFailedToShow',
    AdImpression: 'BlessedZulu\\NativePhpAdmob\\Events\\AdImpression',
    AdClicked: 'BlessedZulu\\NativePhpAdmob\\Events\\AdClicked',
    UserEarnedReward: 'BlessedZulu\\NativePhpAdmob\\Events\\UserEarnedReward',
    AdShowThrottled: 'BlessedZulu\\NativePhpAdmob\\Events\\AdShowThrottled',
    ConsentChanged: 'BlessedZulu\\NativePhpAdmob\\Events\\ConsentChanged',
    ConsentFormShown: 'BlessedZulu\\NativePhpAdmob\\Events\\ConsentFormShown',
    ConsentFormDismissed: 'BlessedZulu\\NativePhpAdmob\\Events\\ConsentFormDismissed',
    TrackingAuthorizationGranted: 'BlessedZulu\\NativePhpAdmob\\Events\\TrackingAuthorizationGranted',
    TrackingAuthorizationDenied: 'BlessedZulu\\NativePhpAdmob\\Events\\TrackingAuthorizationDenied',
};

/**
 * <admob-banner slot="home_footer" position="bottom"></admob-banner>
 *
 * Framework-agnostic mirror of the Blade <x-admob::banner> component. Renders
 * no visible pixels (the banner is a native overlay). The element's own
 * lifecycle is the teardown signal: connect -> load + show, disconnect -> hide.
 * No navigation-event guessing needed.
 */
class AdmobBannerElement extends HTMLElement {
    async connectedCallback() {
        this._slot = this.getAttribute('slot') || '';
        this._position = this.getAttribute('position') || 'bottom';
        if (!this._slot) {
            return;
        }
        await Admob.banner(this._slot).load();
        await Admob.banner(this._slot).show(this._position);
    }

    disconnectedCallback() {
        if (this._slot) {
            Admob.banner(this._slot).hide();
        }
    }
}

/**
 * Register the <admob-banner> custom element. Called automatically on import;
 * safe to call again (no-op if already defined). Exposed for explicit control
 * (e.g. SSR guards).
 */
export function registerAdmobBanner() {
    if (typeof window !== 'undefined' && window.customElements && !window.customElements.get('admob-banner')) {
        window.customElements.define('admob-banner', AdmobBannerElement);
    }
}

registerAdmobBanner();

export default Admob;
