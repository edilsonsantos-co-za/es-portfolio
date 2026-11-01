import React from 'react';
import AvatarDropdown from './AvatarDropdown';
import ThemeToggle from '../../../common/components/ThemeToggle';

/**
 * Top bar actions, same set as the blog admin: light/dark, account
 * (the Store is in the ES app launcher).
 */
const NavContent = () => (
    <div className="atlas-topbar-actions">
        <ThemeToggle className="atlas-icon-btn"/>
        <AvatarDropdown/>
    </div>
);

export default NavContent;
