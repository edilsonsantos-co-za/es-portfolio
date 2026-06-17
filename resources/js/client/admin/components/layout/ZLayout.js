import React, { useEffect, useState } from 'react';
import ProLayout from '@ant-design/pro-layout';
import { useLocation, useNavigate } from 'react-router-dom';
import { useSelector } from 'react-redux';
import Routes from '../../../common/helpers/Routes';
import PropTypes from 'prop-types';
import Utils from '../../../common/helpers/Utils';
import {
  HomeOutlined,
  ExperimentOutlined,
  ControlOutlined,
  TeamOutlined,
  MailOutlined,
  ThunderboltOutlined,
} from '@ant-design/icons';
import { message } from 'antd';
import NavContent from './NavContent';
import { useIsMobile } from '../../../common/hooks/IsMobile';
import axios from 'axios';
import styled from 'styled-components';

const normalizeMenuLayout = (value) => (value === 'top' ? 'top' : 'side');
const normalizeNavTheme = (value) => (value === 'dark' || value === 'realDark' ? 'realDark' : 'light');

const LayoutShell = styled.div`
    height: 100vh;

    .ant-pro-sider-actions {
        display: none !important;
    }

    .ant-pro-sider-collapsed .ant-menu-item-icon,
    .ant-pro-sider-collapsed .ant-menu-submenu-title .ant-menu-item-icon {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .ant-pro-sider-collapsed .ant-menu,
    .ant-pro-sider-collapsed .ant-menu-item,
    .ant-pro-sider-collapsed .ant-menu-submenu,
    .ant-pro-sider-collapsed .ant-menu-submenu-title {
        display: flex !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .ant-pro-sider-collapsed .ant-menu {
        flex-direction: column;
    }

    .ant-pro-sider-collapsed .ant-menu-item,
    .ant-pro-sider-collapsed .ant-menu-submenu-title {
        justify-content: center;
        padding-inline: 0 !important;
    }
`;

const AdminTopBar = styled.div`
    display: flex;
    align-items: center;
    justify-content: flex-end;
    min-height: 56px;
    margin: -24px -24px 24px;
    padding: 0 16px;
    background: #fff;
    box-shadow: 0 1px 4px rgba(0, 21, 41, 0.08);
`;

const ZLayout = ({ children }) => {
    const isMobile = useIsMobile();
    const location = useLocation();
    const globalState = useSelector(state => state.globalState);
    const logo = globalState.logo;
    const siteName = globalState.siteName;
    const menuColor = normalizeNavTheme(globalState.menuColor);
    const navColor = globalState.navColor;
    const layout = normalizeMenuLayout(globalState.menuLayout);
    const [collapsed, setCollapsed] = useState(false);
    let navigate = useNavigate();

    useEffect(() => {
        !isMobile && setCollapsed(globalState.shortMenu);
    }, [globalState.shortMenu])

    const optimizeOnClick = () => {
        message.loading({
            content: 'Action in progress..',
            duration: 0,
            key: 'optimize'
        });

        axios.get(Routes.web.frontend.optimize, {
            headers: { 
                Accept: 'application/json'
            }
        })
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                message.success({ 
                    content: response.data.message, 
                    key: 'optimize'
                });
            })
        })
        .catch((error) => {
            Utils.handleException(error);
        });
    }

    const defaultProps = {
        title: siteName,
        navTheme: menuColor,
        headerTheme: navColor,
        layout: !isMobile ? layout : 'mix',
        fixedHeader: true,
        collapsed: collapsed,
        onCollapse: (_collapsed) => {
            setCollapsed(_collapsed)
        },
        logo: Utils.mediaUrl(logo),
        menu: {
            hideMenuWhenCollapsed: false,
        },
        route: {
            routes: [
                {
                    path: Routes.web.admin.dashboard,
                    key: Routes.web.admin.dashboard,
                    name: 'Dashboard',
                    icon: <HomeOutlined />,
                },
                {
                    path: 'portfolio',
                    name: 'Portfolio',
                    icon: <ExperimentOutlined />,
                    routes: [
                        {
                            path: Routes.web.admin.portfolioConfig,
                            key: Routes.web.admin.portfolioConfig,
                            name: 'Config',
                        },
                        {
                            path: Routes.web.admin.portfolioAbout,
                            key: Routes.web.admin.portfolioAbout,
                            name: 'About',
                        },
                        {
                            path: Routes.web.admin.portfolioSkills,
                            key: Routes.web.admin.portfolioSkills,
                            name: 'Skill',
                        },
                        {
                            path: Routes.web.admin.portfolioEducation,
                            key: Routes.web.admin.portfolioEducation,
                            name: 'Education',
                        },
                        {
                            path: Routes.web.admin.portfolioExperiences,
                            key: Routes.web.admin.portfolioExperiences,
                            name: 'Experience',
                        },
                        {
                            path: Routes.web.admin.portfolioProjects,
                            key: Routes.web.admin.portfolioProjects,
                            name: 'Project',
                        },
                        {
                            path: Routes.web.admin.portfolioServices,
                            key: Routes.web.admin.portfolioServices,
                            name: 'Service',
                        },
                    ],
                },
                {
                    path: Routes.web.admin.visitors,
                    key: Routes.web.admin.visitors,
                    name: 'Visitor',
                    icon: <TeamOutlined/>,
                },
                {
                    path: Routes.web.admin.messages,
                    key: Routes.web.admin.messages,
                    name: 'Message',
                    icon: <MailOutlined/>,
                },
                {
                    path: Routes.web.frontend.optimize,
                    key: Routes.web.frontend.optimize,
                    name: 'Optimize',
                    onclickHandle: optimizeOnClick,
                    icon: <ThunderboltOutlined/>,
                },
                {
                    path: Routes.web.admin.settings,
                    key: Routes.web.admin.settings,
                    name: 'Settings',
                    icon: <ControlOutlined />,
                },
            ]
        },
    };

    const navigateToPath = (path) => {
        navigate(path);
    }
    
    const shouldRenderInlineTopBar = layout === 'side' && !isMobile;
    
    return (
        <React.Fragment>
            <LayoutShell>
                <ProLayout
                    {...defaultProps}
                    location={location}
                    fixSiderbar
                    onMenuHeaderClick={() => navigateToPath(Routes.web.admin.dashboard)}
                    menuItemRender={(item, dom) => (
                        <a
                            onClick={(e) => {
                                e.preventDefault();

                                if (typeof item.onclickHandle !== 'undefined') {
                                    item.onclickHandle();
                                } else {
                                    navigateToPath(item.path);
                                }
                            }}
                            href={item.path}
                        >
                            {dom}
                        </a>
                    )}
                    rightContentRender={!shouldRenderInlineTopBar ? () => <NavContent/> : false}
                    breadcrumbRender={() => ('')}
                    contentStyle={{ padding: '24px' }}
                >
                    {shouldRenderInlineTopBar && (
                        <AdminTopBar>
                            <NavContent/>
                        </AdminTopBar>
                    )}
                    {children}
                    {/* <BackTop /> */}
                </ProLayout>
            </LayoutShell>
        </React.Fragment>
    )
}

ZLayout.propTypes = {
    children: PropTypes.node,
}

export default React.memo(ZLayout);
