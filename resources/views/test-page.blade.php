@php
    // Clear a native EDGE bottom-nav if the host app renders one: it overlays the
    // bottom of the WebView, and NativePHP's safe-area inset covers ONLY the
    // home-indicator (~34pt on iOS), not the nav bar. Add the standard tab-bar
    // height per platform so the fixed event log isn't hidden behind it. 0 when
    // not mobile (no native nav to clear).
    $edgeNavInset = match (config('nativephp-internal.platform')) {
        'ios' => '49px',
        'android' => '72px',
        default => '0px',
    };
@endphp
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>AdMob Test</title>
    <style>
        :root {
            --bg: #09090b; --card: #18181b; --card-2: #27272a; --line: #27272a;
            --text: #fafafa; --muted: #a1a1aa; --accent: #10b981; --accent-ink: #03130d;
            --danger: #ef4444; --radius: 14px;
            --edge-nav: {{ $edgeNavInset }};
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            margin: 0; background: var(--bg); color: var(--text);
            font: 15px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, system-ui, sans-serif;
            padding: max(16px, env(safe-area-inset-top)) 16px calc(120px + var(--edge-nav) + env(safe-area-inset-bottom));
            -webkit-font-smoothing: antialiased;
        }
        h1 { font-size: 20px; margin: 4px 0 2px; letter-spacing: -0.02em; }
        .sub { color: var(--muted); font-size: 13px; margin: 0 0 18px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 14px; margin-bottom: 12px; }
        .card h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin: 0 0 10px; font-weight: 600; }
        .row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        input {
            flex: 1 1 120px; min-width: 0; background: var(--bg); border: 1px solid var(--line);
            color: var(--text); border-radius: 10px; padding: 9px 11px; font-size: 14px; font-family: inherit;
        }
        input:focus { outline: none; border-color: var(--accent); }
        button {
            appearance: none; border: 1px solid var(--line); background: var(--card-2); color: var(--text);
            border-radius: 10px; padding: 9px 13px; font-size: 14px; font-weight: 600; font-family: inherit; cursor: pointer;
        }
        button:active { transform: translateY(1px); }
        button.primary { background: var(--accent); color: var(--accent-ink); border-color: transparent; }
        button.ghost { background: transparent; }
        button:disabled { opacity: .4; cursor: not-allowed; }
        .pill { font-size: 12px; color: var(--muted); margin-left: auto; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .note { font-size: 12px; color: var(--muted); margin: 8px 0 0; }
        #log {
            position: fixed; left: 0; right: 0; bottom: calc(var(--edge-nav) + env(safe-area-inset-bottom)); height: 120px; overflow-y: auto;
            background: #000; border-top: 1px solid var(--line); padding: 8px 12px;
            font: 11px/1.5 ui-monospace, SFMono-Regular, Menlo, monospace; color: #d4d4d8;
        }
        #log .l { white-space: pre-wrap; word-break: break-word; }
        #log .ev { color: var(--accent); }
        #log .err { color: var(--danger); }
        .clearlog { position: fixed; right: 10px; bottom: calc(89px + var(--edge-nav) + env(safe-area-inset-bottom)); z-index: 2; padding: 5px 9px; font-size: 11px; line-height: 1; }
    </style>
</head>
<body>
    <h1>AdMob Test</h1>
    <p class="sub">
        Built-in test page (<code>nativephp-admob</code>). Drives every format through the JS API.
        @if ($testMode) Test mode is ON - any slot resolves to Google's demo ad units. @else <strong>Test mode is OFF</strong> - slots must be configured. @endif
    </p>

    <div class="card">
        <h2>Consent (UMP)</h2>
        <div class="row">
            <button onclick="ump('requestInfo')">Request info</button>
            <button onclick="ump('showForm')">Show form</button>
            <button onclick="umpStatus()">Status</button>
            <button class="ghost" onclick="ump('reset')">Reset</button>
            <span class="pill" id="ump-pill">consent: ?</span>
        </div>
    </div>

    <div class="card">
        <h2>Tracking (ATT - iOS only)</h2>
        <div class="row">
            <button onclick="att('request')">Request</button>
            <button onclick="attStatus()">Status</button>
            <span class="pill" id="att-pill">att: ?</span>
        </div>
    </div>

    <div class="card">
        <h2>Banner</h2>
        <div class="row">
            <input id="slot-banner" value="test_banner" autocapitalize="off" autocorrect="off" spellcheck="false">
            <input id="banner-offset" type="number" value="0" inputmode="numeric" style="flex:0 0 90px" title="offset dp">
        </div>
        <div class="row" style="margin-top:8px">
            <button class="primary" onclick="bannerShow()">Show</button>
            <button onclick="ad('banner','hide')">Hide</button>
            <button onclick="flip()">Flip <span id="pos">bottom</span></button>
        </div>
        <p class="note">Offset (dp) lifts the banner off the edge - use it to clear a bottom-nav.</p>
    </div>

    @foreach (['interstitial' => 'Interstitial', 'rewarded' => 'Rewarded', 'rewarded_interstitial' => 'Rewarded Interstitial', 'app_open' => 'App Open'] as $fmt => $label)
        <div class="card">
            <h2>{{ $label }}</h2>
            <div class="row">
                <input id="slot-{{ $fmt }}" value="test_{{ $fmt }}" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="grid" style="margin-top:8px">
                <button onclick="ad('{{ $fmt }}','load')">Load</button>
                <button onclick="ready('{{ $fmt }}')">Ready?</button>
                <button id="show-{{ $fmt }}" disabled style="grid-column:1/-1" onclick="ad('{{ $fmt }}','show')">Show (load first)</button>
            </div>
        </div>
    @endforeach

    <p class="note">Full-screen formats: Load first, then Show. App Open also auto-shows on foreground.</p>

    <button class="clearlog" onclick="document.getElementById('log').innerHTML=''">clear</button>
    <div id="log" aria-live="polite"></div>

    <script>
        const ENDPOINT = @json($endpoint);
        let position = 'bottom';

        const logEl = document.getElementById('log');
        function log(msg, cls) {
            const d = document.createElement('div');
            d.className = 'l' + (cls ? ' ' + cls : '');
            const t = new Date().toTimeString().slice(0, 8);
            d.textContent = '[' + t + '] ' + msg;
            logEl.prepend(d);
        }

        // Serialize onto a shared queue so bursts never race NativePHP's
        // URL-keyed POST-body capture (which would 422 some calls).
        function call(body) {
            const run = async () => {
                try {
                    const res = await fetch(ENDPOINT, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body),
                    });
                    const json = await res.json().catch(() => ({}));
                    const tag = body.kind + (body.format ? '/' + body.format : '') + ' ' + body.action;
                    log('→ ' + tag + '  ⇒  ' + JSON.stringify(json), res.ok ? null : 'err');
                    return json;
                } catch (e) {
                    log('→ ' + body.action + '  ⇒  network_error', 'err');
                    return {};
                }
            };
            const q = (window.__admobCallQueue || Promise.resolve()).then(run, run);
            window.__admobCallQueue = q.catch(() => {});
            return q;
        }

        function slotOf(format) {
            return (document.getElementById('slot-' + format)?.value || '').trim();
        }

        function ad(format, action) {
            return call({ kind: 'ad', format, slot: slotOf(format), action });
        }
        function bannerOffset() {
            return parseInt(document.getElementById('banner-offset')?.value, 10) || 0;
        }
        async function bannerShow() {
            const slot = slotOf('banner');
            await call({ kind: 'ad', format: 'banner', slot, action: 'load' });
            await call({ kind: 'ad', format: 'banner', slot, action: 'show', position, offset: bannerOffset() });
        }
        function flip() {
            position = position === 'bottom' ? 'top' : 'bottom';
            document.getElementById('pos').textContent = position;
            const slot = slotOf('banner');
            call({ kind: 'ad', format: 'banner', slot, action: 'hide' });
            call({ kind: 'ad', format: 'banner', slot, action: 'show', position, offset: bannerOffset() });
        }
        // Reflect a full-screen format's readiness on its Show button: green +
        // enabled when an ad is loaded, muted + disabled when it isn't.
        function setReady(format, isReady) {
            const btn = document.getElementById('show-' + format);
            if (!btn) return;
            btn.disabled = !isReady;
            btn.classList.toggle('primary', isReady);
            btn.textContent = isReady ? 'Show' : 'Show (load first)';
        }
        async function ready(format) {
            const r = await call({ kind: 'ad', format, slot: slotOf(format), action: 'isReady' });
            log('   ' + format + ' ready = ' + (r.ready === true));
            setReady(format, r.ready === true);
        }

        function ump(action) { return call({ kind: 'ump', action }); }
        async function umpStatus() {
            const r = await call({ kind: 'ump', action: 'status' });
            document.getElementById('ump-pill').textContent = 'consent: ' + (r.status || '?');
        }
        function att(action) { return call({ kind: 'att', action }); }
        async function attStatus() {
            const r = await call({ kind: 'att', action: 'status' });
            document.getElementById('att-pill').textContent = 'att: ' + (r.status || '?');
        }

        // Live event stream from native (AdLoaded, AdShown, ConsentChanged, ...).
        window.addEventListener('native-event', (e) => {
            const name = (e.detail?.event || '').split('\\').pop();
            const payload = e.detail?.payload ?? {};
            log('🔔 ' + name + '  ' + JSON.stringify(payload), 'ev');

            if (name === 'ConsentChanged' && payload.status) {
                document.getElementById('ump-pill').textContent = 'consent: ' + payload.status;
            }

            // Keep each full-screen Show button in sync with load state.
            const fmt = payload.format;
            if (fmt && fmt !== 'banner') {
                if (name === 'AdLoaded') setReady(fmt, true);
                if (name === 'AdDismissed' || name === 'AdFailedToShow' || name === 'AdFailedToLoad') setReady(fmt, false);
            }
        });

        log('ready - endpoint ' + ENDPOINT);
    </script>
</body>
</html>
