<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies;

    /**
     * The app only listens on private Docker networks behind the reverse proxy (no published ports),
     * so private ranges are trusted by default. Override with TRUSTED_PROXIES (comma list or "*").
     */
    public function __construct()
    {
        $configured = trim((string) env('TRUSTED_PROXIES', ''));

        if ($configured === '*') {
            $this->proxies = '*';
        } elseif ($configured !== '') {
            $this->proxies = array_map('trim', explode(',', $configured));
        } else {
            $this->proxies = ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '::1', 'fc00::/7'];
        }
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
