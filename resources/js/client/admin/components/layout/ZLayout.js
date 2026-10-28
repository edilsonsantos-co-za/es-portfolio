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
  MenuOutlined,
} from '@ant-design/icons';
import { message } from 'antd';
import NavContent from './NavContent';
import { useIsMobile } from '../../../common/hooks/IsMobile';
import HTTP from '../../../common/helpers/HTTP';
import styled from 'styled-components';
import { ATLAS_PALETTE, useThemeMode } from '../../../common/helpers/ThemeMode';

const normalizeMenuLayout = (value) => (value === 'top' ? 'top' : 'side');

const LayoutShell = styled.div`
    height: 100vh;

    .ant-pro-sider-actions {
        display: none !important;
    }

    .ant-pro-sider {
        border-inline-end: 1px solid var(--line) !important;
    }

    .ant-pro-sider-logo {
        height: 64px;
        border-bottom: 1px solid var(--line);
    }

    .ant-pro-sider-logo img {
        width: 36px;
        height: 36px;
        border-radius: 10px;
    }

    .ant-pro-sider-logo h1 {
        font-weight: 800 !important;
        letter-spacing: -.02em;
        color: var(--text) !important;
    }

    .ant-menu-item, .ant-menu-submenu-title {
        border-radius: 12px !important;
        font-weight: 600;
    }

    /* Collapsed rail: antd fades out .ant-menu-title-content, but ProLayout renders the icon inside it,
       so keep that visible, hide only the label and the submenu arrow, and centre the icon. */
    .ant-pro-sider-collapsed .ant-menu-title-content {
        opacity: 1 !important;
        flex: 1;
        display: flex !important;
        justify-content: center;
        min-width: 0;
    }

    .ant-pro-sider-collapsed [class*="-item-text"],
    .ant-pro-sider-collapsed .ant-menu-submenu-arrow {
        display: none !important;
    }

    .ant-pro-sider-collapsed .ant-menu-item,
    .ant-pro-sider-collapsed .ant-menu-submenu-title {
        justify-content: center;
        padding-inline: 0 !important;
    }
`;


// Same behaviour as the blog admin: a hamburger in the top bar folds the sidebar to icons,
// and the choice is remembered per browser.
const SIDEBAR_KEY = 'edfolio-admin-sidebar-collapsed';

const readSidebarPreference = () => {
    try {
        const value = window.localStorage.getItem(SIDEBAR_KEY);
        return value === null ? null : value === '1';
    } catch {
        return null;
    }
};

const ZLayout = ({ children }) => {
    const isMobile = useIsMobile();
    const location = useLocation();
    const globalState = useSelector(state => state.globalState);
    const logo = globalState.logo;
    const siteName = globalState.siteName;
    const palette = ATLAS_PALETTE[useThemeMode()];
    const layout = normalizeMenuLayout(globalState.menuLayout);
    const [collapsed, setCollapsed] = useState(() => !isMobile && readSidebarPreference() === true);
    let navigate = useNavigate();

    useEffect(() => {
        // The "short menu" setting is the default until the sidebar is toggled in this browser.
        if (!isMobile && readSidebarPreference() === null) {
            setCollapsed(globalState.shortMenu);
        }
    }, [globalState.shortMenu])

    const toggleSidebar = () => {
        const next = !collapsed;
        setCollapsed(next);
        try {
            window.localStorage.setItem(SIDEBAR_KEY, next ? '1' : '0');
        } catch {
            // Private mode: keep the in-memory state only.
        }
    }

    const optimizeOnClick = () => {
        message.loading({
            content: 'Action in progress..',
            duration: 0,
            key: 'optimize'
        });

        HTTP.post(Routes.api.admin.optimize)
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
        navTheme: 'light',
        token: {
            bgLayout: palette.bg,
            colorTextAppListIcon: palette.muted,
            sider: {
                colorMenuBackground: palette.card,
                colorMenuItemDivider: palette.line,
                colorBgMenuItemHover: palette.card2,
                colorBgMenuItemSelected: palette.accentSoft,
                colorBgMenuItemCollapsedElevated: palette.card,
                colorTextMenu: palette.muted,
                colorTextMenuItemHover: palette.text,
                colorTextMenuActive: palette.text,
                colorTextMenuSelected: palette.accent,
                colorTextMenuSecondary: palette.muted,
                colorTextMenuTitle: palette.text,
                colorTextCollapsedButton: palette.muted,
                colorTextCollapsedButtonHover: palette.text,
            },
            header: {
                colorBgHeader: palette.card,
                colorHeaderTitle: palette.text,
                colorTextMenu: palette.muted,
                colorTextMenuSelected: palette.accent,
                colorBgMenuItemSelected: palette.accentSoft,
                colorTextRightActionsItem: palette.muted,
            },
            pageContainer: {
                colorBgPageContainer: palette.bg,
                paddingBlockPageContainerContent: 0,
                paddingInlinePageContainerContent: 0,
            },
        },
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
                            path: Routes.web.admin.portfolioThemes,
                            key: Routes.web.admin.portfolioThemes,
                            name: 'Themes',
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
                    collapsedButtonRender={false}
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
                    contentStyle={{ padding: 0 }}
                >
                    {shouldRenderInlineTopBar && (
                        <div className="atlas-topbar">
                            <span className="atlas-topbar-start">
                                <button
                                    type="button"
                                    className="atlas-icon-btn"
                                    onClick={toggleSidebar}
                                    aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'}
                                    aria-expanded={!collapsed}
                                    title={collapsed ? 'Expand navigation' : 'Collapse navigation'}
                                >
                                    <MenuOutlined />
                                </button>
                                <span className="atlas-kicker">Portfolio admin</span>
                            </span>
                            <NavContent/>
                        </div>
                    )}
                    <div className="atlas-content">
                        {children}
                    </div>
                </ProLayout>
            </LayoutShell>
        </React.Fragment>
    )
}

ZLayout.propTypes = {
    children: PropTypes.node,
}

export default React.memo(ZLayout);
