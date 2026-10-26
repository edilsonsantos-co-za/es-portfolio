import React from 'react';
import { Dropdown, Avatar } from 'antd';
import { LogoutOutlined, UserOutlined, ProfileOutlined, UnlockOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import Routes from '../../../common/helpers/Routes';

const AvatarDropdown = () => {
    let navigate = useNavigate();

    const onMenuClick = ({ key }) => {
        if (key === 'logout') {
            navigate(Routes.web.admin.logout);
        } else if (key === 'profile') {
            navigate(Routes.web.admin.portfolioAbout);
        } else if (key === 'password') {
            navigate(Routes.web.admin.settings);
        }
    };

    const menuItems = {
        selectedKeys: [],
        onClick: onMenuClick,
        items: [
            { key: 'profile', icon: <ProfileOutlined />, label: 'Profile' },
            { key: 'password', icon: <UnlockOutlined />, label: 'Password' },
            { key: 'logout', icon: <LogoutOutlined />, label: 'Log out' },
        ],
    };

    return (
        <React.Fragment>
            <Dropdown menu={menuItems} trigger={['click']}>
                <button type="button" className="atlas-icon-btn" aria-label="Account menu" title="Account">
                    <Avatar icon={<UserOutlined />} size={26} alt="avatar" />
                </button>
            </Dropdown>
        </React.Fragment>
    );
}

export default AvatarDropdown;