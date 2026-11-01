import React from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
import Utils from '../../../common/helpers/Utils';
import Routes from '../../../common/helpers/Routes';
import '../../../common/assets/css/atlas-auth.css';

/**
 * Atlas sign-in shell, shared in look with the blog: brand bar, one centred card, footer.
 */
const AuthLayout = ({ children, title = null, chip = 'Admin workspace', subtitle = null }) => {
    const { logo, siteName } = useSelector(state => state.globalState);
    const name = siteName || 'EdFolio';

    return (
        <div className="atlas-auth">
            <header className="atlas-auth-top">
                <a href={Routes.web.frontend.home} className="atlas-auth-brand">
                    {logo ? (
                        <span className="atlas-auth-logo has-img" aria-hidden="true"><img src={Utils.mediaUrl(logo)} alt=""/></span>
                    ) : (
                        <span className="atlas-auth-logo" aria-hidden="true">{name.charAt(0).toUpperCase()}</span>
                    )}
                    <span className="atlas-auth-brand-text">
                        <b>{name}</b>
                        <span className="mono">Portfolio admin</span>
                    </span>
                </a>
                <div className="atlas-auth-actions">
                    <a className="atlas-auth-link-btn" href={Routes.web.frontend.home}>← <span>Back to site</span></a>
                </div>
            </header>

            <main className="atlas-auth-main">
                <div className="atlas-auth-card">
                    {chip && <span className="atlas-auth-chip">{chip}</span>}
                    {title && <h1 className="atlas-auth-title">{title}</h1>}
                    {subtitle && <p className="atlas-auth-sub">{subtitle}</p>}
                    {children}
                </div>
            </main>

            <footer className="atlas-auth-foot mono">
                <span>© {new Date().getFullYear()} {name}</span>
            </footer>
        </div>
    );
}

AuthLayout.propTypes = {
    children: PropTypes.node,
    title: PropTypes.string,
    chip: PropTypes.string,
    subtitle: PropTypes.node,
}

export default AuthLayout;
