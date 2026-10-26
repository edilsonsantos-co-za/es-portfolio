import PageHeader from '../PageHeader';
import React, { useEffect, useState } from 'react';
import { Spin, List, Typography, Switch, Select } from 'antd';
import Utils from '../../../common/helpers/Utils';
import styled from 'styled-components';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';
import CoreConstants from '../../../common/helpers/CoreConstants';
import PropTypes from 'prop-types';
import ColorPickerPopup from '../ColorPickerPopup';
import { CloseOutlined, CheckOutlined } from '@ant-design/icons';
import SeoPopup from '../SeoPopup';
import { useSelector } from 'react-redux';

const { Item } = List;
const { Paragraph, Text } = Typography;

const StyledListItem = styled(Item)`
padding: 16px 0px !important;
`;

const Basic = (props) => {
    const [accentColor, setAccentColor] = useState(null);
    const [template, setTemplate] = useState('atlas');
    const [googleAnalyticsId, setGoogleAnalyticsId] = useState('');
    const [maintenanceMode, setMaintenanceMode] = useState(false);
    const [loading, setLoading] = useState(false);
    const [currentSettingToChange, setCurrentSettingToChange] = useState(null);
    const [colorPickerVisible, setColorPickerVisible] = useState(false);
    const [seoPopupVisible, setSeoPopupVisible] = useState(false);
    const [seo, setSeo] = useState(null);
    const { demoMode } = useSelector(state => state.globalState);

    useEffect(() => {
        props.mountedCallBack();
    }, [])

    useEffect(() => {
        if (props.config) {
            setAccentColor(props.config.accentColor);
            setTemplate(props.config.template || 'atlas');
            setGoogleAnalyticsId(props.config.googleAnalyticsId);
            setSeo(props.config.seo);
            setMaintenanceMode((parseInt(props.config.maintenanceMode) == 1) ? true : false);  
        }
    }, [props.config])

    const colorPickerSubmitCallback = (color) => {
        submitData(CoreConstants.portfolioConfig.ACCENT_COLOR, color);
    }

    const colorPickerOnChange = (colorObject) => {
        const color = colorObject.hex;
        setAccentColor(color);
    }

    const colorPickerCancelCallback = (color = null) => {
        if (color) {
            setAccentColor(color);
        }

        setColorPickerVisible(false);
    }

    const submitData = (name, value, callback = null) => {
        if (!loading) {
            setLoading(true);
        }

        setCurrentSettingToChange(name);

        HTTP.post(Routes.api.admin.portfolioConfigs, {
            setting_key: name,
            setting_value: value
        })
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                Utils.showNotification(response.data.message, 'success');
                if (callback) {
                    callback();
                }
            })
        })
        .catch(error => {
            Utils.handleException(error);
        })
        .finally(() => {
            setLoading(false);
        });
    }

    const changeGoogleAnalyticsId = (
        <React.Fragment>
            {
                googleAnalyticsId ? (
                    <Paragraph
                            editable={{
                                tooltip: 'click to edit google analytic id',
                                onChange: setGoogleAnalyticsId,
                            }}
                        >
                        {googleAnalyticsId}
                    </Paragraph>
                ) : (
                    <React.Fragment>
                        <Paragraph
                            editable={{
                                tooltip: 'click to edit google analytic id',
                                onChange: setGoogleAnalyticsId,
                            }}
                        >
                            <Text type='secondary'>Click here to set google analytic id</Text>
                        </Paragraph>
                    </React.Fragment>
                )
            }
        </React.Fragment>
    )

    const changeGoogleAnalyticsIdHandleSubmit = (e) => {
        e.preventDefault();

        submitData(CoreConstants.portfolioConfig.GOOGLE_ANALYTICS_ID, googleAnalyticsId);
    }

    const maintenanceModeOnChange = (checked) => {
        if (demoMode) {
            Utils.showNotification('This feature is disabled in demo', 'warning');
        } else {
            const callback = () => {
                setMaintenanceMode(checked);
            }

            submitData(CoreConstants.portfolioConfig.MAINTENANCE_MODE, checked, callback);
        }
    }

    const store = (
        <a href={Utils.storeUrl} target="_blank" rel="noreferrer">ES Marketplace store ↗</a>
    )

    const theme = Utils.templates.length > 1 ? (
        <Text type="secondary">Installed themes. Get more from the {store}</Text>
    ) : (
        <Text type="secondary">Atlas, the built-in theme. Get more from the {store}</Text>
    )

    const templateOnChange = (value) => {
        submitData(CoreConstants.portfolioConfig.TEMPLATE, value, () => setTemplate(value));
    }

    return (
        <React.Fragment>
            <PageHeader
                title="Basic Config"
            >
                <List
                    itemLayout="horizontal"
                    size="large"
                >
                    <Spin delay={500} size="small" spinning={loading && currentSettingToChange === CoreConstants.portfolioConfig.TEMPLATE}>
                        <StyledListItem actions={Utils.templates.length > 1 ? [
                            <Select
                                key="template-change"
                                value={template}
                                style={{ minWidth: 140 }}
                                onChange={templateOnChange}
                                options={Utils.templates.map(t => ({ value: t.id, label: t.title }))}
                            />
                        ] : []}>
                            <Item.Meta title={'Theme'} description={theme}/>
                        </StyledListItem>
                    </Spin>
                    <Spin delay={500} size="small" spinning={loading && currentSettingToChange === CoreConstants.portfolioConfig.ACCENT_COLOR}>
                        <StyledListItem actions={
                            [
                                <a 
                                    key="accent-color-change" 
                                    onClick={() => {
                                        setColorPickerVisible(true);
                                    }}
                                >
                                    Change
                                </a>,
                            ]
                        }>
                            <Item.Meta title={'Portfolio Accent Color'} description={'Change accent color of frontend.'} />
                        </StyledListItem>
                    </Spin>
                    <Spin delay={500} size="small" spinning={loading && currentSettingToChange === CoreConstants.portfolioConfig.GOOGLE_ANALYTICS_ID}>
                        <StyledListItem actions={[
                            <a 
                                key="google-analytics-change" 
                                onClick={changeGoogleAnalyticsIdHandleSubmit}
                            >
                                Change
                            </a>,
                        ]}>
                            <Item.Meta title={'Google Analytics ID'} description={changeGoogleAnalyticsId}/>
                        </StyledListItem>
                    </Spin>
                    <StyledListItem actions={
                        [
                            <a 
                                key="seo-change" 
                                onClick={() => {
                                    setSeoPopupVisible(true);
                                }}
                            >
                                Change
                            </a>,
                        ]
                    }>
                        <Item.Meta title={'SEO'} description={'Search engine optimization of frontend.'} />
                    </StyledListItem>
                    <Spin delay={500} size="small" spinning={loading && currentSettingToChange === CoreConstants.portfolioConfig.MAINTENANCE_MODE}>
                        <StyledListItem actions={[
                            <Switch
                                key="maintenance-mode-change" 
                                loading={loading && currentSettingToChange === CoreConstants.portfolioConfig.MAINTENANCE_MODE}
                                checkedChildren={<CheckOutlined />}
                                unCheckedChildren={<CloseOutlined />}
                                checked={maintenanceMode}
                                onChange={maintenanceModeOnChange}
                            />
                        ]}>
                            <Item.Meta title={'Maintenance Mode'} description={'Set maintenance mode of frontend.'}/>
                        </StyledListItem>
                    </Spin>
                </List>
            </PageHeader>
            {
                colorPickerVisible && (
                    <ColorPickerPopup
                        selectedColor={accentColor}
                        visible={colorPickerVisible}
                        handleCancel={colorPickerCancelCallback}
                        submitCallback={colorPickerSubmitCallback}
                        colorPickerOnChange={colorPickerOnChange}
                    />
                )
            }
            {
                seoPopupVisible && (
                    <SeoPopup
                        visible={seoPopupVisible}
                        data={seo}
                        handleCancel={
                            () => {
                                setSeoPopupVisible(false);
                            }
                        }
                        submitCallback={
                            (newValue) => {
                                setSeo(newValue);
                                setSeoPopupVisible(false);
                            }
                        }
                    />
                )
            }
        </React.Fragment>
    );
};

Basic.propTypes = {
    config: PropTypes.object,
    mountedCallBack: PropTypes.func.isRequired
}

export default Basic;