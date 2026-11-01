import React from 'react';
import Routes from '../../../common/helpers/Routes';
import AvatarDropdown from './AvatarDropdown';
import ThemeToggle from '../../../common/components/ThemeToggle';

/**
 * Top bar actions, same set as the blog admin: view site, light/dark, account
 * (the Store is in the ES app launcher).
 */
const NavContent = () => (
    <div className="atlas-topbar-actions">
        <a className="atlas-icon-btn" href={Routes.web.frontend.home} target="_blank" rel="noreferrer" title="View site" aria-label="View site">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
        </a>
        <ThemeToggle className="atlas-icon-btn"/>
        <AvatarDropdown/>
    </div>
);

export default NavContent;
