/**
 * Single sign-out across the ES apps. "Sign out" in any app's <es-apps> menu writes a fresh
 * `es-signout` cookie on the parent domain. The admin remembers the value it saw when it signed in
 * (localStorage, next to the token); a different value means you signed out in another ES app.
 * Kept in sync with public/assets/es-account.js (the portfolio pages).
 */
const SEEN_KEY = 'esSignoutSeen';

const marker = () => (document.cookie.match(/(?:^|; )es-signout=([^;]*)/) || [])[1] || '';

const SingleSignOut = {
    remember() {
        try { localStorage.setItem(SEEN_KEY, marker()); } catch { /* private mode */ }
    },

    signedOutElsewhere() {
        const now = marker();
        if (!now) return false;
        try { return localStorage.getItem(SEEN_KEY) !== now; } catch { return false; }
    },

    // Drop the admin token when another ES app signed out; true when there was one to drop
    // (no token: nothing to end, and the SSO sign-in callback must be left alone)
    enforce() {
        if (!SingleSignOut.signedOutElsewhere()) return false;
        let hadToken = false;
        try {
            hadToken = !!localStorage.getItem('apiToken');
            localStorage.removeItem('apiToken');
            localStorage.removeItem(SEEN_KEY);
        } catch { /* private mode */ }
        return hadToken;
    },

    // Signed out in another ES app: end the SSO session too, or the admin's automatic SSO sign-in
    // would sign straight back in. Without SSO, start over at the admin sign-in.
    endSession(fallback) {
        const sso = window.esSso || {};
        window.esSigningOut = true;
        window.location.replace(sso.enabled && sso.logoutUrl ? sso.logoutUrl : fallback);
    },
};

export default SingleSignOut;
