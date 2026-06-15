import { Avatar } from 'antd';
import React from 'react';
import styled from 'styled-components';
import Routes from '../../../common/helpers/Routes';
import AvatarDropdown from './AvatarDropdown';
import Icon from '@ant-design/icons';
import { CgMouse } from 'react-icons/cg';
import { useSelector } from 'react-redux';

const Wrapper = styled.div`
    display: flex;
    align-items: center;
    justify-content: flex-end;
    width: 100%;
    height: 100%;
    overflow: hidden;
`;

const ItemWrapper = styled.div`
align-items: center;
height: 100%;
padding: 0 10px;
cursor: pointer;
transition: all .3s;
justify-content: center;
-webkit-box-align: center;
display: inline-flex;
&:hover {
    background: rgba(0,0,0,.025);
}
`;

const NavContent = () => {
    const {demoMode} = useSelector(state => state.globalState);

    return (
        <React.Fragment>
            <Wrapper>
                {
                    demoMode && (
                        <ItemWrapper className="z-hover">
                        </ItemWrapper>
                    )
                }
                <ItemWrapper className="z-hover">
                    <a href={Routes.web.frontend.home} target="_blank" rel="noreferrer" title="Visit Front Panel">
                        <Avatar /* style={{ backgroundColor: 'var(--primary-hover-color)' }} */ icon={<Icon component={CgMouse} />} size="small"/>
                    </a>
                </ItemWrapper>
                <ItemWrapper className="z-hover">
                    <AvatarDropdown/>
                </ItemWrapper>
            </Wrapper>
        </React.Fragment>
    )
}

export default NavContent;