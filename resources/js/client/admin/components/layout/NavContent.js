import React from 'react';
import AvatarDropdown from './AvatarDropdown';

/**
 * Top bar account actions, matching the blog admin
 * (the Store is in the ES app launcher).
 */
const NavContent = () => (
    <div className="atlas-topbar-actions">
        <AvatarDropdown/>
    </div>
);

export default NavContent;
