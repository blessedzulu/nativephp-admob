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

Set the AdMob app ID and any slot IDs in `.env`:

```dotenv
ADMOB_ENABLED=true
ADMOB_APP_ID=ca-app-pub-XXXXXXXXXXXXXXXX~YYYYYYYYYY

# Per-slot ad unit IDs (one env var per slot you register)
ADMOB_BANNER_CALCULATOR_BOTTOM=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
ADMOB_INTERSTITIAL_BETWEEN_CALCULATIONS=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
ADMOB_REWARDED_EXPORT_PDF=ca-app-pub-XXXXXXXXXXXXXXXX/YYYYYYYYYY
```

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

> _Examples below are the target API. Filled in across Phases 1-6._

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
