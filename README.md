# NativePHP AdMob

Google AdMob plugin for [NativePHP Mobile](https://nativephp.com). Banner, interstitial, rewarded, rewarded interstitial, and app-open ads, with built-in UMP consent and iOS App Tracking Transparency.

> Status: pre-release scaffold. The PHP surface and config schema are in place; native iOS/Android implementations land across Phases 1-10.

## Features

- Five ad formats: banner, interstitial, rewarded, rewarded interstitial, app open
- Fluent, slot-based API: `Admob::interstitial('between_calculations')->load()->show()`
- Config-driven slot names - no raw `ca-app-pub-...` IDs in app code
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

The plugin's manifest takes care of writing this into the right places on each platform:

- **Android**: injected into `AndroidManifest.xml` as the `com.google.android.gms.ads.APPLICATION_ID` `<meta-data>` entry.
- **iOS**: injected into `Info.plist` as `GADApplicationIdentifier`.

You do not need to edit either of those files yourself.

### Slot IDs

```dotenv
# Per-slot ad unit IDs (one env var per slot you register)
ADMOB_BANNER_CALCULATOR_BOTTOM=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
ADMOB_INTERSTITIAL_BETWEEN_CALCULATIONS=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
ADMOB_REWARDED_EXPORT_PDF=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
```

### SKAdNetwork list (iOS)

The plugin ships a starter list of SKAdNetwork identifiers in its iOS Info.plist contribution. Google publishes the canonical list at [developers.google.com/admob/ios/privacy/strategies](https://developers.google.com/admob/ios/privacy/strategies) and updates it from time to time. Check that page before each App Store submission and add any new entries to your consumer app's Info.plist - your additions are merged with the plugin's defaults.

To register slots, publish the config and add named entries:

```bash
php artisan vendor:publish --tag=admob-config
```

```php
// config/admob.php
'slots' => [
    'banner' => [
        'calculator_bottom' => env('ADMOB_BANNER_CALCULATOR_BOTTOM'),
    ],
    // ...
],
```

## PHP Usage

### Banner ads (available since v0.4.0-alpha — Android device-tested, iOS untested on hardware)

```php
use BlessedZulu\NativePhpAdmob\Facades\Admob;

// In a Livewire/Volt component's mount() or wherever you want a banner:
Admob::banner('calculator_bottom')
    ->load()
    ->show('bottom');     // or ->show('top')

// Later, when navigating away or hiding:
Admob::banner('calculator_bottom')->hide();
```

Configure the slot in your `.env`:

```dotenv
ADMOB_ENABLED=true
ADMOB_APP_ID=ca-app-pub-XXXXXXXXXXXXXXXX~YYYYYYYYYY
ADMOB_BANNER_CALCULATOR_BOTTOM=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
```

Or, if you have many slots, publish the config and edit it inline:

```bash
php artisan vendor:publish --tag=admob-config
```

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

Configure the slot in your `.env`:

```dotenv
ADMOB_INTERSTITIAL_BETWEEN_CALCULATIONS=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
```

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

Configure the slot:

```dotenv
ADMOB_REWARDED_EXPORT_PDF=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
```

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

Configure the slot:

```dotenv
ADMOB_APP_OPEN_WARM_RESUME=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
```

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
<x-admob::banner slot="calculator_bottom" position="bottom" />
```

## JavaScript Usage

> _Coming in Phase 3 alongside the JS library stub._

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
| `UserEarnedReward` | `slot`, `type`, `amount` |
| `ConsentFormShown` | - |
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

You are responsible for following Google's [AdMob policies](https://support.google.com/admob/answer/6128543) and Apple's [App Tracking Transparency requirements](https://developer.apple.com/app-store/user-privacy-and-data-use/).

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
