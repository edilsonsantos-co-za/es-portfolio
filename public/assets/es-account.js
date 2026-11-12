/*!
 * Portfolio pages: show the signed-in admin in <es-apps> (instead of "Sign in"), like the blog does.
 * The admin signs in with a token kept in this origin's localStorage, so ask the admin API who that is;
 * an expired token is refreshed once, anything else leaves the "Sign in" button as it is.
 */
(() => {
    // A tab left open (or restored from the back/forward cache) after signing out in another ES app:
    // reload when it comes back into view, so the menu shows "Sign in" instead of a stale signed-in state
    const signedOutSince = () => {
        const now = (document.cookie.match(/(?:^|; )es-signout=([^;]*)/) || [])[1] || '';
        let seenNow = null;
        try { seenNow = localStorage.getItem('esSignoutSeen'); } catch { return false; }
        return !!now && !!seenNow && seenNow !== now;
    };
    const recheck = () => {
        if (document.visibilityState !== 'visible') return;
        if (document.querySelector('es-apps[current="portfolio"][signed-in]') && signedOutSince()) window.location.reload();
    };
    document.addEventListener('visibilitychange', recheck);
    window.addEventListener('pageshow', (e) => { if (e.persisted) recheck(); });

    let token = null;
    try { token = localStorage.getItem('apiToken'); } catch { return; }
    if (!token || token === 'undefined' || token === 'null') return;

    // Signed out in another ES app (the shared `es-signout` marker changed since this admin signed in):
    // drop the token and keep "Sign in". Same rule as resources/js/client/common/helpers/SingleSignOut.js
    const marker = (document.cookie.match(/(?:^|; )es-signout=([^;]*)/) || [])[1] || '';
    let seen = null;
    try { seen = localStorage.getItem('esSignoutSeen'); } catch { /* private mode */ }
    if (marker && seen !== marker) {
        try { localStorage.removeItem('apiToken'); localStorage.removeItem('esSignoutSeen'); } catch { /* private mode */ }
        return;
    }

    const api = (path, method = 'GET') => fetch(`/api/v1/admin/${path}`, {
        method,
        headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        credentials: 'same-origin',
    });

    const me = async () => {
        let res = await api('me');
        if (res.status === 401) {
            const refreshed = await api('refresh-token', 'POST');
            const fresh = refreshed.ok ? (await refreshed.json())?.payload?.access_token : null;
            if (!fresh) return null;
            token = fresh;
            try { localStorage.setItem('apiToken', fresh); } catch { /* private mode */ }
            res = await api('me');
        }
        return res.ok ? (await res.json())?.payload || null : null;
    };

    me().then((user) => {
        if (!user) return;
        document.querySelectorAll('es-apps[current="portfolio"]').forEach((el) => {
            el.removeAttribute('sign-in-url');
            el.setAttribute('user-name', user.name || '');
            el.setAttribute('user-email', user.email || '');
            el.setAttribute('avatar', user.avatar || '');
            el.setAttribute('manage-url', '/admin/portfolio/about');
            el.setAttribute('sign-out-url', '/admin/logout');
            el.setAttribute('signed-in', '');
            // Portfolio admins need the es-owner role to sign in (SsoController), so they see the private apps
            el.setAttribute('owner', '');
        });
    }).catch(() => { /* offline or API down: keep "Sign in" */ });
})();
