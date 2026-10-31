import React, { useEffect } from 'react';
import AuthLayout from './AuthLayout';
import axios from 'axios';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';
import { useLocation, useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { saveApiToken } from '../../redux/ActionCreators';

const sso = window.edfolioSso || { enabled: false };

/**
 * Sign-in happens only on the SSO page. This route shows nothing: it sends the browser to the SSO,
 * or swaps the one-time code from the SSO callback for a token and goes straight to the dashboard.
 * Problems are handled on the SSO side (the server ends the SSO session and comes back here), so
 * there is no password form and no error screen of our own.
 */
const Login = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const location = useLocation();
    const { siteName, apiToken } = useSelector(state => state.globalState);

    useEffect(() => {
        document.title = `Sign in - ${siteName}`;
        if (!sso.enabled || apiToken || window.esSigningOut) {
            return;
        }

        const hash = new URLSearchParams(window.location.hash.slice(1));
        if (!hash.has('sso')) {
            window.location.replace(sso.loginUrl);
            return;
        }

        window.history.replaceState(null, '', window.location.pathname + window.location.search);
        axios.post(Routes.api.admin.ssoExchange, { code: hash.get('sso') })
            .then(response => {
                Utils.handleSuccessResponse(response, () => {
                    dispatch(saveApiToken({ apiToken: response.data.payload.token.access_token }));
                });
            })
            // An expired or reused code: start a fresh SSO sign-in
            .catch(() => window.location.replace(sso.loginUrl));
    }, []);

    useEffect(() => {
        if (apiToken) {
            navigate(location.state?.from || { pathname: Routes.web.admin.dashboard }, { replace: true });
        }
    }, [apiToken]);

    if (!sso.enabled) {
        return (
            <AuthLayout title="Sign-in isn't set up" subtitle="This site signs in through the SSO. Set LOGTO_ENDPOINT, LOGTO_APP_ID and LOGTO_APP_SECRET."/>
        );
    }

    return null;
}

export default Login;
