<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Commands;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;

/**
 * Workaround: NativePHP Mobile (v3.3.5) does not substitute ${ENV_VAR}
 * placeholders inside meta_data values when writing AndroidManifest.xml,
 * nor inside Info.plist string values on iOS. This hook runs at
 * post_compile and replaces our `${ADMOB_APP_ID}` literals with the
 * env-resolved value before Gradle's manifest merger runs.
 *
 * When upstream lands proper substitution, this command becomes a no-op
 * and can be removed in a future release.
 */
class SubstituteManifestPlaceholdersCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:admob:substitute-placeholders';

    protected $description = 'Substitute env-var placeholders for AdMob in the generated native manifests.';

    public function handle(): int
    {
        // The app ID is per-platform. Each build targets one platform, so read
        // that platform's key (ADMOB_APP_ID_ANDROID / ADMOB_APP_ID_IOS), falling
        // back to a universal ADMOB_APP_ID for single-platform apps.
        if ($this->isAndroid()) {
            $appId = env('ADMOB_APP_ID_ANDROID', env('ADMOB_APP_ID'));

            if (empty($appId)) {
                $this->warn('Admob: no Android app ID (ADMOB_APP_ID_ANDROID / ADMOB_APP_ID); skipping AndroidManifest substitution.');
            } else {
                $manifest = $this->buildPath().'/app/src/main/AndroidManifest.xml';
                $this->substituteInFile($manifest, '${ADMOB_APP_ID}', $appId, 'AndroidManifest.xml');
            }
        }

        if ($this->isIos()) {
            $appId = env('ADMOB_APP_ID_IOS', env('ADMOB_APP_ID'));

            if (empty($appId)) {
                $this->warn('Admob: no iOS app ID (ADMOB_APP_ID_IOS / ADMOB_APP_ID); skipping Info.plist substitution.');
            } else {
                foreach (glob($this->buildPath().'/*/Info.plist') ?: [] as $path) {
                    $this->substituteInFile($path, '${ADMOB_APP_ID}', $appId, basename($path));
                }
            }
        }

        return self::SUCCESS;
    }

    protected function substituteInFile(string $path, string $needle, string $value, string $label): void
    {
        if (! file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        if (! is_string($content) || ! str_contains($content, $needle)) {
            return;
        }

        $content = str_replace($needle, $value, $content);
        file_put_contents($path, $content);
        $this->info("Admob: substituted ADMOB_APP_ID in {$label}");
    }
}
