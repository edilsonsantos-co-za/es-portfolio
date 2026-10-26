import React, { useEffect, useState } from 'react';
import AuthLayout from './AuthLayout';
import axios from 'axios';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { saveApiToken } from '../../redux/ActionCreators';

const sso = window.edfolioSso || { enabled: false };

const SSO_ERRORS = {
    not_authorised: 'That account is not an admin of this site.',
    cancelled: 'Sign-in was cancelled.',
    state: 'Sign-in session expired. Please try again.',
    failed: 'Single sign-on failed. Please try again.',
};

/**
 * With SSO configured, sign-in happens on the DataSparkLab SSO page: this route only
 * redirects there, exchanges the one-time code on the way back, or shows why it failed.
 * The password form is the fallback for installs without SSO.
 */
const Login = () => {
    const hash = new URLSearchParams(window.location.hash.slice(1));
    const [loading, setLoading] = useState(false);
    const [ssoError, setSsoError] = useState(() => hash.has('sso_error') ? (SSO_ERRORS[hash.get('sso_error')] || SSO_ERRORS.failed) : null);
    const [exchanging, setExchanging] = useState(() => hash.has('sso'));
    const [credentials, setCredentials] = useState({ email: '', password: '' });
    const dispatch = useDispatch();
    let navigate = useNavigate();
    let location = useLocation();
    const {siteName, apiToken, demoMode} = useSelector(state => state.globalState);

    const redirectToSso = sso.enabled && !apiToken && !ssoError && !exchanging;

    useEffect(() => {
        document.title = `Sign in - ${siteName}`;

        if (demoMode) {
            setCredentials({ email: 'admin@admin.com', password: '12345' });
        }

        if (hash.has('sso') || hash.has('sso_error')) {
            window.history.replaceState(null, '', window.location.pathname + window.location.search);
        }
        if (hash.has('sso')) {
            axios.post(Routes.api.admin.ssoExchange, { code: hash.get('sso') })
                .then(response => {
                    Utils.handleSuccessResponse(response, () => {
                        dispatch(saveApiToken({ apiToken: response.data.payload.token.access_token }));
                    });
                })
                .catch((error) => {
                    Utils.handleException(error);
                    setSsoError(SSO_ERRORS.failed);
                    setExchanging(false);
                });
        }
    }, [])

    useEffect(() => {
        if (redirectToSso) {
            window.location.replace(sso.loginUrl);
        }
    }, [redirectToSso]);

    useEffect(() => {
        if (apiToken) {
            let from = location.state?.from || { pathname: Routes.web.admin.dashboard };
            navigate(from, { replace: true });
        }
    }, [apiToken]);

    const onSubmit = (event) => {
        event.preventDefault();
        setLoading(true);

        axios.post(Routes.api.admin.login, credentials)
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                dispatch(saveApiToken({
                    apiToken: response.data.payload.token.access_token
                }));
            });
        })
        .catch((error) => {
            Utils.handleException(error);
        }).finally(() => {
            setLoading(false);
        });
    };

    const onChange = (event) => setCredentials({ ...credentials, [event.target.name]: event.target.value });

    if (redirectToSso || exchanging) {
        return (
            <AuthLayout chip="DataSparkLab SSO" title="Signing you in…" subtitle="Taking you to the DataSparkLab sign-in page."/>
        );
    }

    if (sso.enabled) {
        return (
            <AuthLayout chip="DataSparkLab SSO" title="We couldn't sign you in">
                {ssoError && <div className="atlas-auth-alert" role="alert">{ssoError}</div>}
                <p className="atlas-auth-sub">Sign in with the account linked to this site&apos;s admin, or ask an administrator for access.</p>
                <a className="atlas-auth-btn" href={sso.loginUrl}>Try again</a>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout title="Sign in" subtitle={`Manage the portfolio content and settings for ${siteName || 'this site'}.`}>
            <form className="atlas-auth-form" onSubmit={onSubmit}>
                <div className="atlas-auth-field">
                    <label htmlFor="login_email">Email</label>
                    <input id="login_email" className="atlas-auth-input" type="email" name="email" value={credentials.email}
                           onChange={onChange} placeholder="you@example.com" autoComplete="username" autoFocus required/>
                </div>
                <div className="atlas-auth-field">
                    <label htmlFor="login_password">Password</label>
                    <input id="login_password" className="atlas-auth-input" type="password" name="password" value={credentials.password}
                           onChange={onChange} placeholder="Enter your password" autoComplete="current-password" required/>
                </div>
                <div className="atlas-auth-row">
                    <span/>
                    <Link to={Routes.web.admin.forgetPassword}>Forgot password?</Link>
                </div>
                <button type="submit" className="atlas-auth-btn" disabled={loading}>{loading ? 'Signing in…' : 'Sign in'}</button>
            </form>
        </AuthLayout>
    )
}

export default Login;
