import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { App as AntdApp, ConfigProvider, theme as antdTheme } from 'antd';
import enUSIntl from 'antd/locale/en_US';import { BrowserRouter, Outlet, Route, Routes as RouterRoutes } from 'react-router-dom';

// Suppress known antd v5 / rc-resize-observer findDOMNode warning (upstream library issue)
const _consoleError = console.error;
console.error = (...args) => {
    if (typeof args[0] === 'string' && args[0].includes('findDOMNode')) return;
    _consoleError(...args);
};
import Routes from '../../common/helpers/Routes';
import SuspenseErrorBoundary from '../../common/components/SuspenseErrorBoundary';
import {ErrorBoundaryFallbackUI} from '../../common/components/ErrorBoundaryFallbackUI';
import '../../common/assets/css/app.css';
import '../../common/assets/css/atlas-auth.css';
import '../../common/assets/css/atlas-admin.css';
import { ATLAS_PALETTE } from '../../common/helpers/AtlasPalette';
import ReactRoutes from '../../common/helpers/ReactRoutes';
import { Provider, useDispatch } from 'react-redux';
import { initializeGlobalState } from '../redux/ActionCreators';
import ConfigureStore from '../redux/ConfigureStore';
import PrivateRoute from '../components/PrivateRoute';
import loadable from '@loadable/component';
import ZLayout from '../components/layout/ZLayout';
import LazyLoadingFallbackUi from '../../common/components/lazyLoadingFallbackUi/LazyLoadingFallbackUi';
import { setupInterceptors } from '../../common/helpers/HTTP';
import Utils from '../../common/helpers/Utils';
import SingleSignOut from '../../common/helpers/SingleSignOut';
// Coming back to an open admin tab after signing out in another ES app: sign out here too
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && localStorage.getItem('apiToken') && SingleSignOut.enforce()) {
        SingleSignOut.endSession(Routes.web.admin.admin);
    }
});
const NotFound = loadable(() => import('../components/Notfound'));

const store = ConfigureStore();

/**
 * Registers context-aware antd notification/message instances into Utils
 * so they respect the active theme/ConfigProvider context.
 */
const AntdAppBridge = () => {
    const { notification: notif, message: msg } = AntdApp.useApp();
    React.useEffect(() => {
        Utils.setAntdInstances(notif, msg);
    }, [notif, msg]);
    return null;
};

/**
 * Remove an element by showing fade out effect
 * 
 * @param Element el 
 * @param int speed in millisecond
 */
const fadeoutAndRemoveElement = (el, speed) => {
    var seconds = speed/1000;
    el.style.transition = "opacity "+seconds+"s ease";

    el.style.opacity = 0;
    setTimeout(function() {
        if (el.parentNode) {
            el.parentNode.removeChild(el);
        }
    }, speed);
}

/**
 * public routes
 */
const publicRoutes = () => {
    return ReactRoutes.admin.filter(route => route.private === false).map((route, index) => {
        const RouteComponent = route.component;

        return (
            <Route
                key={index}
                path={route.path}
                element={<RouteComponent fallback={<LazyLoadingFallbackUi/>}/>}
            />
        );
    });
}

/**
 * private routes
 */
const privateRoutes = () => {
    return ReactRoutes.admin.filter(route => route.private === true).map((route, index) => {
        const RouteComponent = route.component;

        return (
            <Route
                key={index}
                path={route.path}
                element={<RouteComponent fallback={<LazyLoadingFallbackUi spinner={true}/>}/>}
            />
        );
    });
}

/**
 * Root component
 */
const App = () => {
    const dispatch = useDispatch();

    /**
     * Below settings are coming from 
     * resources/views/admin/app.blade.php
     * line 17. Ideally to get the settings, a request should be made to api.
     * 
     * But to keep it simple, we are passing the values in a constant which is
     * already declared in that mentioned file.
     */
    // eslint-disable-next-line no-undef
    let mySettings = settings;

    // Signed out in another ES app: drop the token and end the SSO session too
    // (not on the sign-out page itself, which already ends the SSO session)
    if (window.location.pathname !== Routes.web.admin.logout && SingleSignOut.enforce()) {
        SingleSignOut.endSession(Routes.web.admin.login);
    }
    const apiToken = (localStorage.getItem("apiToken") !== 'undefined' && localStorage.getItem("apiToken") !== null) ? localStorage.getItem("apiToken") : null;
    
    dispatch(initializeGlobalState({
        apiToken: apiToken,
        accentColor: mySettings.accentColor,
        shortMenu: mySettings.shortMenu,
        menuLayout: mySettings.menuLayout,
        siteName: mySettings.siteName,
        logo: mySettings.logo,
        favicon: mySettings.favicon,
        avatar: mySettings.avatar,
        demoMode: mySettings.demoMode,
        cover: mySettings.cover,
    }));

    useEffect(()=> {
        //remove preloader
        let preloader = document.getElementById("szn-preloader");
        
        if (preloader) {
             fadeoutAndRemoveElement(preloader, 1000);
        }

        Utils.changeAccentColor(mySettings.accentColor);

        setupInterceptors(store);
    }, []);

    const palette = ATLAS_PALETTE;
    const atlasTheme = {
        algorithm: antdTheme.darkAlgorithm,
        token: {
            colorPrimary: palette.accent,
            colorLink: palette.accent,
            colorBgLayout: palette.bg,
            colorBgContainer: palette.card,
            colorBgElevated: palette.card,
            colorBorder: palette.line,
            colorBorderSecondary: palette.line,
            colorText: palette.text,
            colorTextSecondary: palette.muted,
            fontFamily: "'Manrope', system-ui, -apple-system, 'Segoe UI', sans-serif",
            fontFamilyCode: "'JetBrains Mono', ui-monospace, monospace",
            borderRadius: 12,
            borderRadiusLG: 24,
        },
    };

	return (
		<React.Fragment>
            <SuspenseErrorBoundary fallback={<ErrorBoundaryFallbackUI/>}>
                <ConfigProvider locale={enUSIntl} theme={atlasTheme}>
                    <AntdApp>
                        <AntdAppBridge />
                        <BrowserRouter>
                        <RouterRoutes>
                            
                            {/* public routes */}
                            {publicRoutes()}

                            {/* private routes */}
                            <Route element={<PrivateRoute />}>
                                <Route element={<ZLayout><Outlet /></ZLayout>}>
                                    {privateRoutes()}
                                    <Route path="*" element={<NotFound/>} />
                                </Route>
                            </Route>

                            {/* 404 route */}
                            <Route path={Routes.web.admin.notFound} element={<NotFound/>} />
                            <Route path="*" element={<NotFound/>} />
                            
                        </RouterRoutes>
                        </BrowserRouter>
                    </AntdApp>
                </ConfigProvider>
            </SuspenseErrorBoundary>
        </React.Fragment>
	);
}

if (document.getElementById('react-root')) {
    const root = createRoot(document.getElementById('react-root'));
    root.render(
        <Provider store={store}>
            <App/>
        </Provider>
    );
}