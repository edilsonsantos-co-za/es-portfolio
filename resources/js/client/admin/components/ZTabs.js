import { Menu } from 'antd';
import React, { useEffect, useState } from 'react';
import styled from 'styled-components';
import PropTypes from 'prop-types';

const Wrapper = styled.div`
display: flex;
width: 100%;
height: 100%;
padding-top: 16px;
padding-bottom: 16px;
overflow: auto;
background-color: @menu-bg;
.leftMenu {
    width: 224px;
    border-right: @border-width-base @border-style-base @border-color-split;
}
.right {
    flex: 1;
    padding: 0 24px;
    overflow: auto;
}
@media (max-width: 768px) {
    flex-direction: column;
    .leftMenu {
        width: 100%;
        border: none;
    }
    .right {
        padding: 0 16px;
    }
}
`;

const ZTabs = ({tabs, selectedTab = null}) => {
    const [selectKey, setSelectKey] = useState(selectedTab ? selectedTab : tabs[0].key);

    useEffect(() => {
        if (selectedTab) {
            setSelectKey(selectedTab);
        }
    }, [selectedTab])

    const getMenuItems = () => {
        return tabs.map((tab) => ({ key: tab.key, label: tab.title }));
    }

    const renderContent = () => {
        let selectedTab = tabs.filter((tab) => {
            return tab.key === selectKey
        });

        if (selectedTab.length) {
            selectedTab = selectedTab[0];

            return selectedTab.content;
        }
    }

    return (
        <React.Fragment>
            <Wrapper>
                <div className={'leftMenu'}>
                    <Menu mode={'inline'} selectedKeys={[selectKey]} onClick={({ key }) => setSelectKey(key)} items={getMenuItems()} />
                </div>
                <div className={'right'}>
                    {renderContent()}
                </div>
            </Wrapper>
        </React.Fragment>
    )
}

ZTabs.propTypes = {
    tabs: PropTypes.arrayOf(PropTypes.shape({ key: PropTypes.string, title: PropTypes.node, content: PropTypes.node })).isRequired,
    selectedTab: PropTypes.string
}

export default ZTabs;