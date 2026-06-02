/**
 * Type declarations for the NativePHP AdMob JavaScript API.
 */

export type BannerPosition = 'bottom' | 'top';

export interface CallResult {
    ok: boolean;
    error?: string;
}

export interface BannerBuilder {
    load(): Promise<CallResult>;
    show(position?: BannerPosition): Promise<CallResult>;
    hide(): Promise<CallResult>;
}

export interface FullScreenBuilder {
    load(): Promise<CallResult>;
    show(): Promise<CallResult>;
    isReady(): Promise<boolean>;
}

export interface UmpApi {
    requestInfo(): Promise<CallResult>;
    showForm(): Promise<CallResult>;
    canRequestAds(): Promise<boolean>;
    /** 'required' | 'obtained' | 'not_required' | 'unknown' */
    status(): Promise<string>;
    reset(): Promise<CallResult>;
}

export interface AttApi {
    request(): Promise<CallResult>;
    /** 'authorized' | 'denied' | 'restricted' | 'notDetermined' | 'unsupported' */
    status(): Promise<string>;
}

export interface AdmobApi {
    banner(slot: string): BannerBuilder;
    interstitial(slot: string): FullScreenBuilder;
    rewarded(slot: string): FullScreenBuilder;
    rewardedInterstitial(slot: string): FullScreenBuilder;
    appOpen(slot: string): FullScreenBuilder;
    ump: UmpApi;
    att: AttApi;
}

export const Admob: AdmobApi;

/** Override the endpoint (defaults to '/_admob/call'). */
export function setEndpoint(url: string): void;

/** Register the <admob-banner> custom element (auto-called on import). */
export function registerAdmobBanner(): void;

export const Events: {
    readonly AdLoaded: string;
    readonly AdFailedToLoad: string;
    readonly AdShown: string;
    readonly AdDismissed: string;
    readonly AdFailedToShow: string;
    readonly AdImpression: string;
    readonly AdClicked: string;
    readonly UserEarnedReward: string;
    readonly AdShowThrottled: string;
    readonly ConsentChanged: string;
    readonly ConsentFormShown: string;
    readonly ConsentFormDismissed: string;
    readonly TrackingAuthorizationGranted: string;
    readonly TrackingAuthorizationDenied: string;
};

/** Event payload shapes delivered via the runtime's On(eventName, cb). */
export interface AdEventPayload {
    slot: string;
    format: string;
}
export interface AdFailurePayload extends AdEventPayload {
    errorCode: number;
    errorMessage: string;
}
export interface RewardPayload extends AdEventPayload {
    type: string;
    amount: number;
}
export interface ThrottledPayload extends AdEventPayload {
    reason: 'cooldown' | 'daily_cap';
}
export interface ConsentChangedPayload {
    status: 'required' | 'obtained' | 'not_required' | 'unknown';
}

export default Admob;
