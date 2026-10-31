<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\CoreConstants;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Sso\LogtoClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Admin sign-in through Logto. Only existing admins can sign in: the verified email
 * from Logto must match an admin record; accounts are never created here.
 *
 * The callback hands the admin SPA a one-time code (in the URL fragment) which the SPA
 * swaps for a JWT via exchange(), so the JWT itself never appears in a URL.
 */
class SsoController extends Controller
{
    private const HANDOFF_TTL_SECONDS = 60;

    public function login(Request $request): RedirectResponse
    {
        $client = LogtoClient::fromConfig();
        abort_unless($client->enabled(), 404);

        $auth = $client->authorizationRequest(route('sso.callback'));
        $request->session()->put('sso', [
            'state' => $auth['state'],
            'nonce' => $auth['nonce'],
            'verifier' => $auth['verifier'],
        ]);

        return redirect()->away($auth['url']);
    }

    /**
     * End the Logto session after the SPA has dropped its token, so signing out
     * does not sign straight back in, then return to the public site.
     */
    public function logout(): RedirectResponse
    {
        $client = LogtoClient::fromConfig();
        abort_unless($client->enabled(), 404);

        return redirect()->away($client->logoutUrl(url('/')));
    }

    public function callback(Request $request): RedirectResponse
    {
        $client = LogtoClient::fromConfig();
        abort_unless($client->enabled(), 404);

        $pending = $request->session()->pull('sso');

        if (!is_array($pending) || !hash_equals($pending['state'], (string) $request->query('state', ''))) {
            return $this->refuse($client, 'state mismatch or expired');
        }

        if ($request->filled('error') || !$request->filled('code')) {
            return $this->refuse($client, 'cancelled');
        }

        try {
            $claims = $client->exchangeCode((string) $request->query('code'), route('sso.callback'), $pending['verifier'], $pending['nonce']);
        } catch (Throwable $th) {
            return $this->refuse($client, 'token exchange failed: ' . $th->getMessage());
        }

        $email = Str::lower((string) ($claims['email'] ?? ''));
        $admin = ($email !== '' && ($claims['email_verified'] ?? false) === true)
            ? Admin::whereRaw('LOWER(email) = ?', [$email])->first()
            : null;

        if (!$admin) {
            return $this->refuse($client, 'no admin with that verified email');
        }

        $handoff = Str::random(48);
        Cache::put('sso-handoff:' . hash('sha256', $handoff), $admin->getKey(), self::HANDOFF_TTL_SECONDS);

        return redirect(url('/admin/login') . '#sso=' . $handoff);
    }

    /**
     * No error screen of our own: end the SSO session (so another account can be used) and come
     * back to /admin/login, which goes straight to the SSO sign-in page. The reason is only logged.
     */
    private function refuse(LogtoClient $client, string $reason): RedirectResponse
    {
        Log::notice('SSO sign-in refused: ' . $reason);

        return redirect()->away($client->logoutUrl(url('/admin/login')));
    }

    public function exchange(Request $request): JsonResponse
    {
        $code = (string) $request->input('code', '');
        $adminId = $code !== '' ? Cache::pull('sso-handoff:' . hash('sha256', $code)) : null;
        $admin = $adminId ? Admin::find($adminId) : null;

        if (!$admin) {
            return response()->json([
                'message' => 'Sign-in link expired. Please try again.',
                'payload' => null,
                'status' => CoreConstants::STATUS_CODE_UNAUTHORIZED,
            ], CoreConstants::STATUS_CODE_UNAUTHORIZED);
        }

        $guard = Auth::guard('admins');
        $token = $guard->login($admin);

        return response()->json([
            'message' => 'Successfully logged in',
            'payload' => [
                'admin' => $admin,
                'token' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => $guard->factory()->getTTL() * 60,
                ],
            ],
            'status' => CoreConstants::STATUS_CODE_SUCCESS,
        ]);
    }
}
