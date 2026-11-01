import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';

/**
 * The ES app launcher and account menu (<es-apps>, shared by every ES app; public/assets/es-apps.js).
 * The signed-in admin (name, email, Gravatar) comes from the API, since the admin page itself is
 * rendered before the token-based sign-in is known.
 */
const AvatarDropdown = () => {
    const [me, setMe] = useState(null);
    const apiToken = useSelector(state => state.globalState.apiToken);

    // Ask again whenever the sign-in token changes (it can arrive after the layout first renders)
    useEffect(() => {
        if (!apiToken) {
            return;
        }
        HTTP.get(Routes.api.admin.me)
            .then(response => setMe(response.data.payload || null))
            .catch(() => setMe(null));
    }, [apiToken]);

    return (
        <es-apps
            current="portfolio"
            signed-in="true"
            user-name={me?.name || ''}
            user-email={me?.email || ''}
            avatar={me?.avatar || ''}
            manage-url={Routes.web.admin.portfolioAbout}
            sign-out-url={Routes.web.admin.logout}
        />
    );
}

export default AvatarDropdown;
