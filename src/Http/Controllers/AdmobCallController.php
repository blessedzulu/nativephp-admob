<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Http\Controllers;

use BlessedZulu\NativePhpAdmob\Exceptions\UnknownSlotException;
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Single same-origin endpoint backing the resources/js/admob.js module and the
 * <admob-banner> Web Component. Every request runs the Admob facade, so slot
 * resolution, the consent gate, frequency caps, and the enabled kill-switch all
 * apply server-side - the JS layer is a thin client with zero duplicated logic.
 * Ad lifecycle events still reach JS via the native -> `native-event` path.
 */
class AdmobCallController
{
    /** snake-case format -> facade builder method. Fixed allowlist - never call a method from raw input. */
    private const AD_FORMATS = [
        'banner' => 'banner',
        'interstitial' => 'interstitial',
        'rewarded' => 'rewarded',
        'rewarded_interstitial' => 'rewardedInterstitial',
        'app_open' => 'appOpen',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        try {
            return match ((string) $request->input('kind')) {
                'ad' => $this->handleAd($request),
                'ump' => $this->handleUmp($request),
                'att' => $this->handleAtt($request),
                default => $this->error('unknown_kind'),
            };
        } catch (UnknownSlotException) {
            return $this->error('unknown_slot');
        }
    }

    private function handleAd(Request $request): JsonResponse
    {
        $format = (string) $request->input('format');
        $slot = (string) $request->input('slot');

        if (! isset(self::AD_FORMATS[$format]) || $slot === '') {
            return $this->error('invalid_ad_request');
        }

        $builder = Admob::{self::AD_FORMATS[$format]}($slot);

        return match ((string) $request->input('action')) {
            'load' => $this->ok(fn () => $builder->load()),
            'show' => $this->ok(fn () => $format === 'banner'
                ? $builder->show($this->position($request))
                : $builder->show()),
            'hide' => $format === 'banner'
                ? $this->ok(fn () => $builder->hide())
                : $this->error('hide_not_supported'),
            'isReady' => $format === 'banner'
                ? $this->error('is_ready_not_supported')
                : response()->json(['ready' => $builder->isReady()]),
            default => $this->error('invalid_action'),
        };
    }

    private function handleUmp(Request $request): JsonResponse
    {
        $ump = Admob::ump();

        return match ((string) $request->input('action')) {
            'requestInfo' => $this->ok(fn () => $ump->requestConsentInfo()),
            'showForm' => $this->ok(fn () => $ump->showFormIfRequired()),
            'canRequestAds' => response()->json(['can_request' => $ump->canRequestAds()]),
            'status' => response()->json(['status' => $ump->status()]),
            'reset' => $this->ok(fn () => $ump->reset()),
            default => $this->error('invalid_action'),
        };
    }

    private function handleAtt(Request $request): JsonResponse
    {
        $att = Admob::att();

        return match ((string) $request->input('action')) {
            'request' => $this->ok(fn () => $att->requestAuthorization()),
            'status' => response()->json(['status' => $att->status()]),
            default => $this->error('invalid_action'),
        };
    }

    private function ok(Closure $fn): JsonResponse
    {
        $fn();

        return response()->json(['ok' => true]);
    }

    private function error(string $code, int $status = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $code], $status);
    }

    private function position(Request $request): string
    {
        return $request->input('position') === 'top' ? 'top' : 'bottom';
    }
}
