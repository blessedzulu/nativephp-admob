<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Support\FakeBridge;
use BlessedZulu\NativePhpAdmob\Support\LoggingBridge;
use Illuminate\Support\Facades\Log;

it('traces calls at debug level and delegates to the inner bridge', function () {
    Log::spy();
    $inner = (new FakeBridge)->stub('Admob.Ping', ['success' => true, 'data' => ['ok' => true], 'error' => null]);
    $bridge = new LoggingBridge($inner, app('log'));

    $result = $bridge->call('Admob.Ping', ['x' => 1]);

    expect($result['data']['ok'])->toBeTrue();
    $inner->assertCalled('Admob.Ping');
    Log::shouldHaveReceived('debug')->twice();
});
