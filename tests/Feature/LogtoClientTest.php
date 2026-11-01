<?php

namespace Tests\Feature;

use App\Services\Sso\LogtoClient;
use Tests\TestCase;

class LogtoClientTest extends TestCase
{
    public function testAuthorizationAlwaysRequestsDarkTheme(): void
    {
        $cookies = $_COOKIE;
        $client = new LogtoClient('https://sso.example.test', null, 'portfolio', 'secret');

        try {
            foreach ([[], ['es-theme' => 'light']] as $preferences) {
                $_COOKIE = $preferences;
                $request = $client->authorizationRequest('https://portfolio.example.test/callback');
                parse_str(parse_url($request['url'], PHP_URL_QUERY), $query);

                $this->assertSame('dark', $query['theme']);
                $this->assertSame($request['state'], $query['state']);
                $this->assertSame('S256', $query['code_challenge_method']);
            }
        } finally {
            $_COOKIE = $cookies;
        }
    }
}
