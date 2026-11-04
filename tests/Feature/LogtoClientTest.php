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

    public function testAuthorizationRequestsRoles(): void
    {
        $client = new LogtoClient('https://sso.example.test', null, 'portfolio', 'secret');
        parse_str(parse_url($client->authorizationRequest('https://portfolio.example.test/callback')['url'], PHP_URL_QUERY), $query);

        $this->assertContains('roles', explode(' ', $query['scope']));
    }

    public function testOnlyTheOwnerRoleCounts(): void
    {
        // Store customers share the Logto tenant: a verified email alone must never be enough
        $this->assertTrue(LogtoClient::isOwner(['roles' => ['es-owner']]));
        $this->assertTrue(LogtoClient::isOwner(['roles' => ['customer', 'es-owner']]));
        $this->assertFalse(LogtoClient::isOwner(['email' => 'someone@example.com', 'email_verified' => true]));
        $this->assertFalse(LogtoClient::isOwner(['roles' => []]));
        $this->assertFalse(LogtoClient::isOwner(['roles' => ['es-owner-ish', 'admin']]));
        $this->assertFalse(LogtoClient::isOwner(['roles' => 'es-owner-not-a-list']));
    }
}
