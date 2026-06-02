# NativePHP AdMob

Google AdMob plugin for [NativePHP Mobile](https://nativephp.com). Banner, interstitial, rewarded, rewarded interstitial, and app-open ads, with built-in UMP consent and iOS App Tracking Transparency.

> Status: alpha. All five ad formats plus UMP + ATT are implemented and Android device-verified. iOS is implemented but not yet hardware-tested - please report issues at the [issue tracker](https://github.com/blessedzulu/nativephp-admob/issues).

## Features

- Five ad formats: banner, interstitial, rewarded, rewarded interstitial, app open
- Fluent, slot-based API: `Admob::interstitial('level_complete')->load()->show()`
- Config-driven slot names - no raw `ca-app-pub-...` IDs in app code, no env-key convention
- `<x-admob::banner>` Blade component (no Livewire dependency)
- Per-format / per-slot frequency caps
- UMP (User Messaging Platform) consent flow baked in
- iOS App Tracking Transparency (ATT) prompt baked in
- `show()` silently no-ops until consent is granted - hard to misuse
- Automatic test ad mode outside production
- Typed Laravel events for every ad lifecycle moment
- `Admob::fake()` for tests - no devices required for unit tests

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13
- NativePHP Mobile `^3.0`
- An AdMob account and at least one ad unit per format you use

## Installation

```bash
composer require blessedzulu/nativephp-admob
php artisan vendor:publish --tag=nativephp-plugins-provider    # first plugin only
php artisan native:plugin:register blessedzulu/nativephp-admob
php artisan native:run                                          # rebuild
```

## Configuration

### Required: AdMob app ID

The plugin's manifest declares `ADMOB_APP_ID` as a required secret. Set it in your `.env` before running `native:run` or the build will fail with a clear error:

```dotenv
ADMOB_ENABLED=true
ADMOB_APP_ID=ca-app-pub-XXXXXXXXXXXXXXXX~YYYYYYYYYY
```

`ADMOB_ENABLED` is a real kill-switch: when `false`, every ad `load()` / `show()` / `hide()` no-ops across all formats (and the Blade/JS banner + JS API). Consent (UMP) and tracking (ATT) still run, so you can keep gathering consent while ads are toggled off.

The plugin's manifest takes care of writing this into the right places on each platform:

- **Android**: injected into `AndroidManifest.xml` as the `com.google.android.gms.ads.APPLICATION_ID` `<meta-data>` entry.
- **iOS**: injected into `Info.plist` as `GADApplicationIdentifier`.

You do not need to edit either of those files yourself.

### Where ad units are configured

Ad units live under named **slots** in `config/admob.php` - never as raw IDs in your app code. A slot is just a name you pick (`home_footer`, `level_complete`, ...) mapped to the AdMob ad unit ID for that placement.

The package has **no env-key convention**. It resolves a slot solely from `config('admob.slots.{format}.{name}')`. Where each ID comes from is entirely your choice - hardcode it, or read it from an env var you name yourself.

Publish the config and add your slots:

```bash
php artisan vendor:publish --tag=admob-config
```

```php
// config/admob.php
'slots' => [
    'banner' => [
        'home_footer' => env('ADMOB_BANNER_HOME_FOOTER'), // env name is yours; not required
    ],
    'interstitial' => [
        'level_complete' => 'ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY', // or hardcode
    ],
    // rewarded / rewarded_interstitial / app_open follow the same shape
],
```

Outside `production`, `test_mode` is on and these IDs are ignored in favour of Google's reserved test IDs, so you cannot accidentally serve a real ad in development.

### Displaying each format

| Format | How to display |
|--------|----------------|
| Banner | `<x-admob::banner slot="home_footer" position="bottom" />` (screen-anchored native overlay, one per slot) - or manually `Admob::banner('home_footer')->load()->show('bottom')` / `->hide()` |
| Interstitial | `Admob::interstitial('level_complete')->load();` then `->show()` when `->isReady()`; listen for lifecycle events |
| Rewarded | `Admob::rewarded('unlock_feature')->load()->show();` grant on the `UserEarnedReward` event |
| Rewarded interstitial | `Admob::rewardedInterstitial('session_break')->load()->show();` |
| App open | `Admob::appOpen('cold_start')->load()` on boot; the native lifecycle observer auto-shows on foreground |

### SKAdNetwork list (iOS)

The plugin ships a starter list of SKAdNetwork identifiers in its iOS Info.plist contribution. Google publishes the canonical list at [developers.google.com/admob/ios/privacy/strategies](https://developers.google.com/admob/ios/privacy/strategies) and updates it from time to time. Check that page before each App Store submission and add any new entries to your consumer app's Info.plist - your additions are merged with the plugin's defaults.

## PHP Usage

### Banner ads (available since v0.4.0-alpha — Android device-tested, iOS untested on hardware)

```php
use BlessedZulu\NativePhpAdmob\Facades\Admob;

// In a Livewire/Volt component's mount() or wherever you want a banner:
Admob::banner('home_footer')
    ->load()
    ->show('bottom');     // or ->show('top')

// Later, when navigating away or hiding:
Admob::banner('home_footer')->hide();
```

Register the `home_footer` slot in `config/admob.php` (see [Where ad units are configured](#where-ad-units-are-configured)). Or skip the manual calls entirely and use the [Blade component](#blade), which loads, shows, and tears the banner down for you.

The banner uses Google's **adaptive banner** sizing — the SDK picks the right height for the device. Width is full screen width. Banners are attached to the activity's root view (Android) or key window (iOS) as an overlay, so they don't shift your existing layout.

Test mode is automatic outside `production`. Real ad unit IDs are silently swapped for Google's reserved test IDs, so you can never accidentally show a real ad during development.

### Interstitial ads (available since v0.5.0-alpha — Android device-tested, iOS untested on hardware)

```php
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use BlessedZulu\NativePhpAdmob\Events\AdLoaded;
use BlessedZulu\NativePhpAdmob\Events\AdDismissed;
use Native\Mobile\Attributes\OnNative;

// Pre-load when the screen mounts:
public function mount(): void
{
    Admob::interstitial('between_calculations')->load();
}

// Show when the user finishes a meaningful action:
public function onCalculationFinished(): void
{
    if (Admob::interstitial('between_calculations')->isReady()) {
        Admob::interstitial('between_calculations')->show();
    }
}

// Re-load after dismissal so the next show is ready:
#[OnNative(AdDismissed::class)]
public function onDismissed(string $slot, string $format): void
{
    if ($format === 'interstitial') {
        Admob::interstitial($slot)->load();
    }
}
```

Interstitials are **one-shot**: each loaded ad survives until it is shown and dismissed, then the slot must be loaded again. The plugin clears the registry slot on `AdDismissed` and `AdFailedToShow` automatically.

Register the `between_calculations` slot in `config/admob.php` (see [Where ad units are configured](#where-ad-units-are-configured)).

Events dispatched for the interstitial lifecycle: `AdLoaded`, `AdFailedToLoad`, `AdShown`, `AdFailedToShow`, `AdImpression`, `AdClicked`, `AdDismissed`. Listen with `#[OnNative(EventClass::class)]` on any Livewire component.

### Rewarded ads (available since v0.6.0-alpha — Android device-tested, iOS untested on hardware)

```php
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use BlessedZulu\NativePhpAdmob\Events\AdDismissed;
use BlessedZulu\NativePhpAdmob\Events\UserEarnedReward;
use Native\Mobile\Attributes\OnNative;

// Pre-load when the screen mounts:
public function mount(): void
{
    Admob::rewarded('export_pdf')->load();
}

// Show in response to a user action ("Watch a video to unlock PDF export"):
public function onUnlockTapped(): void
{
    if (Admob::rewarded('export_pdf')->isReady()) {
        Admob::rewarded('export_pdf')->show();
    }
}

// Grant the reward when the user finishes watching:
#[OnNative(UserEarnedReward::class)]
public function onEarned(string $slot, string $format, string $type, int $amount): void
{
    if ($slot === 'export_pdf') {
        $this->unlockPdfExport();
    }
}

// Re-load after dismissal:
#[OnNative(AdDismissed::class)]
public function onDismissed(string $slot, string $format): void
{
    if ($format === 'rewarded') {
        Admob::rewarded($slot)->load();
    }
}
```

The `UserEarnedReward` event fires ONLY if the user watches to the rewardable threshold. Dismissing early fires `AdDismissed` without `UserEarnedReward`.

Register the `export_pdf` slot in `config/admob.php` (see [Where ad units are configured](#where-ad-units-are-configured)).

### Rewarded interstitial ads (available since v0.6.0-alpha)

Same API surface as rewarded, but the ad **auto-plays on entry** with a 5-second skip warning rather than an opt-in "Watch ad" tap. Useful between level transitions where you want to reward continuation without requiring an explicit tap.

```php
Admob::rewardedInterstitial('between_levels')->load();
// later…
if (Admob::rewardedInterstitial('between_levels')->isReady()) {
    Admob::rewardedInterstitial('between_levels')->show();
}
```

`UserEarnedReward` event payload includes `format: 'rewarded_interstitial'` so a single listener can branch.

### App Open ads (available since v0.7.0-alpha — Android device-tested, iOS untested on hardware)

App Open ads are the format Google designed for the brief moment between app foreground and your splash/home screen. The plugin's recommended path is **auto-show**: call `load()` once on app start; the native lifecycle observer presents the cached ad on every subsequent foreground (skipping the cold-start resume), and discards anything older than 4 hours.

```php
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use BlessedZulu\NativePhpAdmob\Events\AdDismissed;
use Native\Mobile\Attributes\OnNative;

// Once, on app boot or in a long-lived component:
public function mount(): void
{
    Admob::appOpen('warm_resume')->load();
}

// Re-load after dismissal so the next foreground has a fresh ad:
#[OnNative(AdDismissed::class)]
public function onDismissed(string $slot, string $format): void
{
    if ($format === 'app_open') {
        Admob::appOpen($slot)->load();
    }
}
```

**Locked behaviours:**
- **Skip first resume.** The very first `onResume` / `didBecomeActive` after launch is consumed silently so the splash owns cold start.
- **4-hour staleness.** Ads older than 4h are silently discarded on foreground. The plugin does NOT auto-load a replacement (consumer drives that via `#[OnNative(AdDismissed::class)]` or a periodic re-load).
- **One-shot per show.** Same as interstitial/rewarded: dismissal clears the registry slot; call `load()` again before the next show.

**Manual override** when the auto-show flow doesn't fit (e.g. you want to gate on a feature-flag or an in-app purchase state):

```php
Admob::appOpen('paywall_dismissed')->load();

// Later:
if (Admob::appOpen('paywall_dismissed')->isReady()) {
    Admob::appOpen('paywall_dismissed')->show();
}
```

Register the `warm_resume` slot in `config/admob.php` (see [Where ad units are configured](#where-ad-units-are-configured)).

### Other formats

> _All five formats now ship - Banner / Interstitial / Rewarded / Rewarded Interstitial / App Open._

```php
use BlessedZulu\NativePhpAdmob\Facades\Admob;

// One-time boot - idempotent. Initialises the SDK, requests UMP consent,
// shows the ATT prompt on iOS.
Admob::start();

// Banner
Admob::banner('calculator_bottom')->load()->show('bottom');
Admob::banner('calculator_bottom')->hide();

// Interstitial - pre-load, then show when ready
Admob::interstitial('between_calculations')->load();
if (Admob::interstitial('between_calculations')->isReady()) {
    Admob::interstitial('between_calculations')->show();
}

// Rewarded - dispatches UserEarnedReward event on success
Admob::rewarded('export_pdf')->load()->show();
```

### Listening for events (Livewire)

```php
use Native\Mobile\Attributes\OnNative;
use BlessedZulu\NativePhpAdmob\Events\UserEarnedReward;

class ExportPdf extends Component
{
    #[OnNative(UserEarnedReward::class)]
    public function onReward(string $slot, string $type, int $amount)
    {
        if ($slot === 'export_pdf') {
            $this->generatePdf();
        }
    }
}
```

### Blade

```blade
<x-admob::banner slot="home_footer" position="bottom" />
```

Drop this on any page that should show a banner. On render it loads and shows the banner for the slot; when you navigate away it tears the native overlay down for you. Because the banner is a screen-anchored native overlay (not a WebView element), teardown happens by listening for a DOM event and calling `Admob.HideBanner` through NativePHP's own JS bridge.

**No Livewire dependency.** The teardown events are configurable, defaulting to Livewire's SPA navigation:

```php
// config/admob.php
'banner' => [
    // Listens on BOTH window and document for each event, cleaned up on teardown.
    'hide_on_events' => ['livewire:navigating', 'inertia:before', 'pagehide'],
],
```

Auto-hide needs *some* navigation event from your host app: Livewire dispatches `livewire:navigating` on `window`, Inertia dispatches `inertia:*` on `document`, and `pagehide` covers full-page unloads. Override the list for a different router, or set `[]` to disable and call `Admob::banner($slot)->hide()` yourself. Notes: one native overlay per slot; sharing a slot across pages is safe; don't mount two different positions for the same slot at once (last wins). **Inertia/Vue/React apps should use the JS API + `<admob-banner>` Web Component below instead** - its connect/disconnect lifecycle drives show/hide with no event guessing.

## JavaScript API (Inertia / Vue / React / vanilla)

The plugin ships a JS module so you can drive ads from JavaScript without Livewire or Blade. Publish it into your app and import it:

```bash
php artisan vendor:publish --tag=admob-js   # -> resources/js/vendor/admob/admob.js (+ .d.ts)
```

```js
import { Admob, Events } from './vendor/admob/admob.js';
import { On } from '@nativephp/mobile'; // your NativePHP runtime import

On(Events.UserEarnedReward, ({ slot, amount }) => grant(slot, amount));

await Admob.interstitial('level_complete').load();
if (await Admob.interstitial('level_complete').isReady()) {
    await Admob.interstitial('level_complete').show();
}

// Consent / tracking
await Admob.ump.requestInfo();
if (!(await Admob.ump.canRequestAds())) await Admob.ump.showForm();
await Admob.att.request(); // iOS only
```

**Banner — `<admob-banner>` Web Component** (framework-agnostic mirror of `<x-admob::banner>`):

```html
<admob-banner slot="home_footer" position="bottom"></admob-banner>
```

Works in Vue (hyphenated tags resolve as custom elements; mark it via `app.config.compilerOptions.isCustomElement = t => t === 'admob-banner'`), React 19+ (native custom-element support), and vanilla. The element's own lifecycle is the teardown signal: connect → load + show, disconnect → hide - no navigation-event wiring. For manual control use `Admob.banner('home_footer').show('bottom')` / `.hide()` (e.g. in `onMounted` / `onBeforeUnmount`).

**How it works.** Every JS call POSTs to a thin same-origin endpoint (`/_admob/call`) that runs the PHP `Admob` facade, so slot resolution, the consent gate, frequency caps, and the `ADMOB_ENABLED` kill-switch all apply server-side - the JS layer duplicates none of it. Ad events still arrive in JS via the runtime's `On()`. The endpoint is CSRF-exempt and session-less, exactly like NativePHP's own `/_native/api/call` - it only exists inside the localhost native WebView. Toggle it off with `ADMOB_JS_API=false`; change its prefix with `config('admob.js_api_prefix')`.

> `npm`-packaged distribution is a planned follow-up; for now publish the file as above (or import it via a `#admob` alias you define).

## Events

| Event | Payload |
|-------|---------|
| `AdLoaded` | `slot`, `format` |
| `AdFailedToLoad` | `slot`, `format`, `errorCode`, `errorMessage` |
| `AdShown` | `slot`, `format` |
| `AdDismissed` | `slot`, `format` |
| `AdFailedToShow` | `slot`, `format`, `errorCode`, `errorMessage` |
| `AdImpression` | `slot`, `format` |
| `AdClicked` | `slot`, `format` |
| `UserEarnedReward` | `slot`, `format`, `type`, `amount` |
| `AdShowThrottled` | `slot`, `format`, `reason` |
| `ConsentFormShown` | - |
| `ConsentFormDismissed` | `status` |
| `ConsentChanged` | `status` |
| `TrackingAuthorizationGranted` | - |
| `TrackingAuthorizationDenied` | - |

## Permissions

Declared automatically via the plugin's manifest:

- Android: `android.permission.INTERNET`, `android.permission.ACCESS_NETWORK_STATE`, plus AdMob SDK runtime requirements
- iOS: `NSUserTrackingUsageDescription` (only when ATT is enabled) plus AdMob SDK runtime requirements

You do not need to add any of these to your own app's manifest.

## UMP and ATT Compliance

UMP (consent) and ATT (iOS tracking) are enabled by default. If your audience is entirely outside the EEA + UK and you only ever serve non-personalised ads, you can opt out:

```dotenv
ADMOB_UMP_ENABLED=false
ADMOB_ATT_ENABLED=false
```

### Testing the consent form

The UMP consent form only appears for users in the EEA + UK. To force it during development on a device anywhere, set a debug geography and register your device as a UMP test device:

```dotenv
ADMOB_UMP_DEBUG_GEOGRAPHY=EEA
ADMOB_TEST_DEVICES=<UMP-hashed-device-id>
```

The UMP-hashed device ID is printed to logcat / the Xcode console on the first `requestConsentInfo()` call on an unconfigured device (it is **not** the same value as the Mobile Ads test-device ID). Copy it from the log into `ADMOB_TEST_DEVICES` and relaunch. Set `ADMOB_UMP_DEBUG_GEOGRAPHY=DISABLED` (the default) for production.

You are responsible for following Google's [AdMob policies](https://support.google.com/admob/answer/6128543) and Apple's [App Tracking Transparency requirements](https://developer.apple.com/app-store/user-privacy-and-data-use/).

## Frequency caps

Throttle how often the full-screen formats (interstitial, rewarded, rewarded interstitial, app open) show, per format or per slot. Banners are exempt. Both constraints are opt-in - omit or set `0` to disable. Caps are persisted in the cache, so they survive app relaunches, and reset at local midnight. `test_mode` bypasses caps so you can spam-test.

```php
// config/admob.php
'frequency' => [
    'interstitial' => ['min_interval_seconds' => 60, 'max_per_day' => 10],
    'slots' => [
        'interstitial' => ['level_complete' => ['min_interval_seconds' => 30]], // per-slot overrides per-format
    ],
],
```

When a `show()` is suppressed, it no-ops and dispatches `AdShowThrottled` (`slot`, `format`, `reason` = `cooldown` | `daily_cap`) so you can react or log it.

## Debugging

Set `ADMOB_DEBUG=true` to trace every native bridge call (method, params, and response) at `debug` log level. When a bridge call fails (`success: false`), the plugin logs a warning rather than throwing - a failed ad never crashes your app.

## Testing

```bash
composer install
composer test       # Pest
composer lint       # Pint
```

Outside production, the plugin automatically swaps registered ad unit IDs for Google's reserved test IDs - you cannot accidentally show a real ad in `local` or `staging`.

For unit tests in your own app, swap the live bridge for a fake:

```php
use BlessedZulu\NativePhpAdmob\Facades\Admob;

Admob::fake();

// Then assert against recorded calls in your test.
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Good first issues are labelled in the [issue tracker](https://github.com/blessedzulu/nativephp-admob/issues).

## License

MIT. See [LICENSE](LICENSE).
