import React, { useEffect } from 'react';
import { useDispatch } from 'react-redux';
import { Navigate } from 'react-router-dom';
import Routes from '../../../common/helpers/Routes';
import { removeApiToken } from '../../redux/ActionCreators';

const sso = window.edfolioSso || { enabled: false };

const Logout = () => {
    const dispatch = useDispatch();
    
    useEffect(() => {
        dispatch(removeApiToken());

        if (sso.enabled) {
            window.location.replace(sso.logoutUrl);
        }
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