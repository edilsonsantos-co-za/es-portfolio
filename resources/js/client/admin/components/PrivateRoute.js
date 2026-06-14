import React from 'react';
import { useSelector } from "react-redux";
import { Navigate, Outlet, useLocation } from "react-router-dom";
import Routes from "../../common/helpers/Routes";

const PrivateRoute = () => {
    const apiToken = useSelector(state => state.globalState.apiToken);
    const location = useLocation();

    if (typeof apiToken !== 'undefined' && apiToken !== null) {
        return <Outlet />;
    }

    return <Navigate to={Routes.web.admin.login} state={{ from: location }} replace />;
}

export default PrivateRoute;