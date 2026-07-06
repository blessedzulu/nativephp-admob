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
                $this->error('Admob: set ADMOB_APP_ID_ANDROID (or a universal ADMOB_APP_ID) in your .env - the Android AdMob app ID is required to build.');

                return self::FAILURE;
            }

            $manifest = $this->buildPath().'/app/src/main/AndroidManifest.xml';
            $this->substituteInFile($manifest, '${ADMOB_APP_ID}', $appId, 'AndroidManifest.xml');
        }

        if ($this->isIos()) {
            $appId = env('ADMOB_APP_ID_IOS', env('ADMOB_APP_ID'));

            if (empty($appId)) {
                $this->error('Admob: set ADMOB_APP_ID_IOS (or a universal ADMOB_APP_ID) in your .env - the iOS AdMob app ID is required to build.');

                return self::FAILURE;
            }

            // NativePHP uses a separate Info.plist per target: the DEVICE target's
            // lives one directory deep (e.g. NativePHP/Info.plist) while the
            // SIMULATOR target's sits at the build root as
            // "<App>-simulator-Info.plist". Cover both globs - if the simulator
            // plist is missed, its ${ADMOB_APP_ID} is never replaced, Xcode
            // expands the unknown ${...} to an empty string, and the GMA SDK
            // aborts on launch (GADApplicationVerifyPublisherInitializedCorrectly).
            $plists = array_values(array_unique(array_merge(
                glob($this->buildPath().'/*/Info.plist') ?: [],
                glob($this->buildPath().'/*Info.plist') ?: [],
            )));

            foreach ($plists as $path) {
                $this->substituteInFile($path, '${ADMOB_APP_ID}', $appId, basename($path));
                $this->injectSKAdNetworkItems($path, basename($path));
            }
        }

        return self::SUCCESS;
    }

    /**
     * Inject the SKAdNetworkItems array (array-of-dicts) into the iOS Info.plist.
     *
     * This is done here, at post_compile, rather than via the plugin's
     * declarative `info_plist` block because NativePHP's IOSPluginCompiler only
     * serialises flat arrays of <string> values - it TypeErrors on an array of
     * <dict> entries (which SKAdNetworkItems requires per Apple's spec). Emitting
     * the XML directly against the final Info.plist sidesteps that limitation.
     *
     * Idempotent: skips if the key is already present.
     */
    protected function injectSKAdNetworkItems(string $path, string $label): void
    {
        if (! file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        if (! is_string($content) || str_contains($content, '<key>SKAdNetworkItems</key>')) {
            return;
        }

        $idsFile = dirname(__DIR__, 2).'/resources/skadnetwork-ids.json';
        if (! file_exists($idsFile)) {
            return;
        }

        $ids = json_decode((string) file_get_contents($idsFile), true);
        if (! is_array($ids) || $ids === []) {
            return;
        }

        $dicts = '';
        foreach ($ids as $id) {
            if (! is_string($id) || $id === '') {
                continue;
            }
            $safe = htmlspecialchars($id, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $dicts .= "\n\t\t<dict>\n\t\t\t<key>SKAdNetworkIdentifier</key>\n\t\t\t<string>{$safe}</string>\n\t\t</dict>";
        }

        $entry = "\n\t<key>SKAdNetworkItems</key>\n\t<array>{$dicts}\n\t</array>";

        $new = preg_replace(
            '/(\s*<\/dict>\s*<\/plist>)/s',
            $entry.'$1',
            $content,
            1
        );

        if (! is_string($new) || $new === $content) {
            $this->warn("Admob: could not inject SKAdNetworkItems into {$label} (no </dict></plist> anchor).");

            return;
        }

        file_put_contents($path, $new);
        $this->info('Admob: injected '.count($ids)." SKAdNetworkItems into {$label}");
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
