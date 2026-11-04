import React, { useEffect } from 'react';
import { useDispatch } from 'react-redux';
import { Navigate } from 'react-router-dom';
import Routes from '../../../common/helpers/Routes';
import { removeApiToken } from '../../redux/ActionCreators';

const sso = window.esSso || { enabled: false };

const Logout = () => {
    const dispatch = useDispatch();

    useEffect(() => {
        if (sso.enabled) {
            // Dropping the token re-renders the admin, which routes to /admin/login; that page must not
            // start a new SSO sign-in (it would replace this redirect and sign straight back in).
            window.esSigningOut = true;
            window.location.replace(sso.logoutUrl);
        }
        dispatch(removeApiToken());
    }, [])

    if (sso.enabled) {
        return null;
    }

    return (
        <Navigate
            to={Routes.web.admin.login}
            replace
        />
    );
}

export default Logout;
