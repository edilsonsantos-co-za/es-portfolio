<?php

namespace App\Services\Sso;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Minimal OpenID Connect client for Logto (authorization code flow with PKCE).
 *
 * The ID token is received directly from the token endpoint over the back channel,
 * so its claims are validated (issuer, audience, expiry, nonce) rather than its signature,
 * as permitted by OpenID Connect Core 3.1.3.7.
 */
class LogtoClient
{
    public function __construct(
        private readonly ?string $endpoint,
        private readonly ?string $internalEndpoint,
        private readonly ?string $appId,
        private readonly ?string $appSecret,
    ) {
    }

    public static function fromConfig(): self
    {
        $config = config('services.logto');

        return new self($config['endpoint'] ?? null, $config['internal_endpoint'] ?? null, $config['app_id'] ?? null, $config['app_secret'] ?? null);
    }

    public function enabled(): bool
    {
        return filled($this->endpoint) && filled($this->appId) && filled($this->appSecret);
    }

    /**
     * @return array{url: string, state: string, nonce: string, verifier: string}
     */
    public function authorizationRequest(string $redirectUri): array
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $url = $this->publicBase() . '/oidc/auth?' . http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'theme' => 'dark',
        ]);

        return compact('url', 'state', 'nonce', 'verifier');
    }

    /**
     * Exchange an authorization code and return the validated ID token claims.
     *
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code, string $redirectUri, string $verifier, string $nonce): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->appId, $this->appSecret)
            ->timeout(10)
            ->post($this->serverBase() . '/oidc/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'code_verifier' => $verifier,
            ]);

        if (!$response->successful() || !is_string($response->json('id_token'))) {
            throw new RuntimeException('Token exchange failed: ' . $response->status());
        }

        $claims = $this->decodeJwtPayload($response->json('id_token'));

        $issuers = array_filter([$this->publicBase() . '/oidc', $this->serverBase() . '/oidc']);
        $audience = (array) ($claims['aud'] ?? []);

        if (!in_array($claims['iss'] ?? null, $issuers, true)
            || !in_array($this->appId, $audience, true)
            || (int) ($claims['exp'] ?? 0) < time()
            || !hash_equals($nonce, (string) ($claims['nonce'] ?? ''))) {
            throw new RuntimeException('ID token claims are not valid for this client.');
        }

        return $claims;
    }

    public function logoutUrl(string $postLogoutRedirectUri): string
    {
        return $this->publicBase() . '/oidc/session/end?' . http_build_query([
            'client_id' => $this->appId,
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
        ]);
    }

    private function publicBase(): string
    {
        return rtrim((string) $this->endpoint, '/');
    }

    private function serverBase(): string
    {
        return rtrim((string) ($this->internalEndpoint ?: $this->endpoint), '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed ID token.');
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!is_array($payload)) {
            throw new RuntimeException('Malformed ID token payload.');
        }

        return $payload;
    }
}
