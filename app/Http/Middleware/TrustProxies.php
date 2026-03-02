<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

/**
 * Extends Laravel's TrustProxies so REMOTE_ADDR is never passed as null to Symfony
 * (which causes IpUtils::checkIp4() to throw when using trustProxies(at: '*')).
 */
class TrustProxies extends \Illuminate\Http\Middleware\TrustProxies
{
    protected function setTrustedProxyIpAddressesToTheCallingIp(Request $request): void
    {
        $addr = $request->server->get('REMOTE_ADDR');
        if ($addr === null || $addr === '') {
            $addr = '127.0.0.1';
        }
        $request->setTrustedProxies([$addr], $this->getTrustedHeaderNames());
    }
}
