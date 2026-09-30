import React, { useEffect, useState } from 'react';
import { Button, Space, Modal, Row, Col, Card, DatePicker, Statistic, Empty } from 'antd';
import moment from 'moment';
import { useIsMounted } from '../../../common/hooks/IsMounted';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';
import { useSelector } from 'react-redux';
import { 
    TeamOutlined, 
    UserAddOutlined, 
    UserSwitchOutlined, 
    ExclamationCircleOutlined
} from '@ant-design/icons';
import styled from 'styled-components';
import { Pie, WordCloud } from '@ant-design/plots';

const { confirm } = Modal;

// Configs target @ant-design/plots v2 (G2 v5); v1 options such as
// `statistic`, `wordField` and `d.percent` are silently ignored there.
const formatPercent = (datum, index, data) => {
    const total = Array.isArray(data)
        ? data.reduce((sum, item) => sum + (Number(item && item.value) || 0), 0)
        : 0;
    const value = Number(datum && datum.value) || 0;

    return total > 0 ? `${Math.round((value / total) * 100)}%` : '0%';
};

const pieConfig = {
    angleField: 'value',
    colorField: 'name',
    height: 200,
    autoFit: true,
    radius: 1,
    innerRadius: 0.6,
    label: {
        position: 'inside',
        text: formatPercent,
        style: {
            fontSize: 12,
            fontWeight: 'bold',
            fill: '#fff',
        },
    },
    legend: {
        color: {
            title: false,
            position: 'right',
            rowPadding: 5,
        },
    },
    tooltip: {
        title: 'name',
        items: [{ field: 'value', name: 'Visitors' }],
    },
};

const wordCloudConfig = {
    textField: 'name',
    colorField: 'name',
    autoFit: true,
    layout: {
        spiral: 'rectangular',
        fontSize: [14, 48],
        rotate: 0,
        padding: 2,
        random: () => 0.5,
    },
    legend: false,
    tooltip: {
        title: 'name',
        items: [{ field: 'value', name: 'Visitors' }],
    },
};

const emptyVisitorsPayload = {
    visitors: {
        total: 0,
        new: 0,
        old: 0,
    },
    location: [],
    device: {
        desktop: 0,
        mobile: 0,
    },
    browser: [],
    platform: [],
};

const toSafeInteger = (value) => {
    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed) ? parsed : 0;
};

const normalizeBreakdownData = (items = [], nameKey) => {
    if (!Array.isArray(items)) {
        return [];
    }

    return items.reduce((data, item = {}) => {
        const value = toSafeInteger(item.total);

        if (value <= 0) {
            return data;
        }

        data.push({
            name: item[nameKey] || 'Unknown',
            value,
        });

        return data;
    }, []);
};

const StyledCol = styled(Col).withConfig({
    shouldForwardProp: (prop) => !['mobilePaddingTop'].includes(prop),
})`
text-align: ${props => props.align ? props.align : 'left'};
@media (max-width: 768px) {
    text-align: center !important;
    ${props => props.mobilePaddingTop && `padding-top: ${props.mobilePaddingTop};`}
}
`;

const Visitors = () => {
    const isMounted = useIsMounted();
    const { demoMode } = useSelector(state => state.globalState);
    const [loading, setLoading] = useState(false);

    const [date, setDate] = useState({
        startDate: null,
        endDate: null,
    });

    const [visitorsData, setVisitorsData] = useState({
        total: 0,
        new: 0,
        old: 0,
    });

    const [locationData, setLocationData] = useState([]);
    const [deviceData, setDeviceData] = useState([]);
    const [browserData, setBrowserData] = useState([]);
    const [platformData, setPlatformData] = useState([]);

    useEffect(() => {
        loadData();
    }, []);

    useEffect(() => {
        if (isMounted) {
            loadData();
        }
    }, [date]);

    const resetStatsState = () => {
        setVisitorsData(emptyVisitorsPayload.visitors);
        setLocationData([]);
        setDeviceData([]);
        setBrowserData([]);
        setPlatformData([]);
    };

    const loadData = (_loading = true) => {
        setLoading(_loading);

        HTTP.get(Routes.api.admin.visitorsStats, {
            params: {
                startDate: date.startDate,
                endDate: date.endDate,
            }
        })
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                const result = response.data.payload || emptyVisitorsPayload;
                const nextVisitors = result.visitors || emptyVisitorsPayload.visitors;
                const nextDevice = result.device || emptyVisitorsPayload.device;
                const nextDeviceData = [];
                const desktopVisitors = toSafeInteger(nextDevice.desktop);
                const mobileVisitors = toSafeInteger(nextDevice.mobile);

                setVisitorsData({
                    total: toSafeInteger(nextVisitors.total),
                    new: toSafeInteger(nextVisitors.new),
                    old: toSafeInteger(nextVisitors.old),
                });

                if (desktopVisitors > 0) {
                    nextDeviceData.push({
                        name: 'Desktop',
                        value: desktopVisitors,
                    });
                }
                
                if (mobileVisitors > 0) {
                    nextDeviceData.push({
                        name: 'Mobile',
                        value: mobileVisitors,
                    });
                }

                setLocationData(normalizeBreakdownData(result.location, 'location'));
                setDeviceData(nextDeviceData);
                setBrowserData(normalizeBreakdownData(result.browser, 'browser'));
                setPlatformData(normalizeBreakdownData(result.platform, 'platform'));
            }, resetStatsState);
        })
        .catch(error => {
            resetStatsState();
            Utils.handleException(error);
        }).finally(() => {
            setLoading(false);
        });
    }

    const datePickerOnChange = (dates) => {
        let utcStartDate = null;
        let utcEndDate = null;

        if (dates) {
            let withoutUtcStartDate = dates[0];
            let withoutUtcEndDate = dates[1];

            utcStartDate =  moment.utc(withoutUtcStartDate.startOf('day')).format('YYYY-MM-DD HH:mm:ss');
            utcEndDate = moment.utc(withoutUtcEndDate.endOf('day')).format('YYYY-MM-DD HH:mm:ss');
        }

        setDate({
            startDate: utcStartDate,
            endDate: utcEndDate,
        });
    }

    const showResetConfirm = () => {
        if (demoMode) {
            Utils.showNotification('This feature is disabled in demo', 'warning');
        } else {
            confirm({
                confirmLoading: loading,
                title: 'Are you sure?',
                content: 'By pressing OK, all stats related to visitors will be removed. Please proceed with cautions.',
                icon: <ExclamationCircleOutlined />,
                mask: true,
                onOk() {
                    setLoading(true);

                    HTTP.delete(Routes.api.admin.visitorsStats)
                    .then(response => {
                        Utils.handleSuccessResponse(response, () => {
                            //visitors
                            resetStatsState();

                            Utils.showNotification(response.data.message, 'success', false);
                        });
                    })
                    .catch((error) => {
                        Utils.handleException(error);
                    }).finally(() => {
                        setLoading(false);
                    });
                },
            });
        }
    }

    return (
        <React.Fragment>
            <Row gutter={24}>
                <Col
                    xl={24}
                    lg={24}
                    md={24}
                    sm={24}
                    xs={24}
                    style={{
                        marginBottom: 24,
                    }}
                >
                    <Card 
                        variant="borderless"
                        hoverable
                        style={{cursor: 'default'}}
                        className="z-shadow"
                    >
                        <Row>
                            <StyledCol md={12} sm={12} xs={24} align={'left'}>
                                <DatePicker.RangePicker
                                    variant="borderless"
                                    presets={[
                                        { label: "Today", value: [moment(), moment()] },
                                        { label: "Yesterday", value: [moment().subtract(1, 'day'), moment().subtract(1, 'day')] },
                                        { label: "This Week", value: [moment().startOf('week'), moment().endOf('week')] },
                                        { label: "Last 7 Days", value: [moment().subtract(7, 'day'), moment()] },
                                        { label: "This Month", value: [moment().startOf('month'), moment().endOf('month')] },
                                        { label: "Last Month", value: [moment().subtract(1,'months').startOf('month'), moment().subtract(1,'months').endOf('month')] },
                                        { label: "Last 30 Days", value: [moment().subtract(30, 'day'), moment()] },
                                    ]}
                                    onChange={datePickerOnChange}
                                />
                            </StyledCol>
                            <StyledCol md={12} sm={12} xs={24} align={'right'} mobilePaddingTop={'1rem'}>
                                <Space>
                                    <Button type="primary" danger onClick={showResetConfirm} disabled={loading}>Reset All Stats</Button>
                                    <Button type="primary" onClick={loadData} disabled={loading}>Refresh</Button>
                                </Space>
                            </StyledCol>
                        </Row>
                    </Card>
                </Col>
                <Col
                    xl={12}
                    lg={12}
                    md={24}
                    sm={24}
                    xs={24}
                    style={{
                        marginBottom: 24,
                    }}
                >
                    <Row>
                        <Col
                            xl={24}
                            lg={24}
                            md={24}
                            sm={24}
                            xs={24}
                            style={{
                                marginBottom: 24,
                            }}
                        >
                            <Card
                                style={{cursor: 'default'}}
                                title={"Count"}
                                loading={loading}
                                variant="borderless"
                                hoverable
                                className="z-shadow"
                            >
                                <Row>
                                    <Col md={8} sm={12} xs={24}>
                                        <Statistic
                                            className="text-center"
                                            title={'Total Visitors'}
                                            value={visitorsData.total}
                                            prefix={<TeamOutlined />}
                                        />
                                    </Col>
                                    <Col md={8} sm={12} xs={24}>
                                        <Statistic
                                            className="text-center"
                                            title={'New Visitors'}
                                            value={visitorsData.new}
                                            prefix={<UserAddOutlined />}
                                        />
                                    </Col>
                                    <Col md={8} sm={12} xs={24}>
                                        <Statistic
                                            className="text-center"
                                            title={'Returning Visitors'}
                                            value={visitorsData.old}
                                            prefix={<UserSwitchOutlined />}
                                        />
                                    </Col>
                                </Row>
                            </Card>
                        </Col>
                        <Col
                            xl={24}
                            lg={24}
                            md={24}
                            sm={24}
                            xs={24}
                            style={{
                                marginBottom: 24,
                            }}
                        >
                            <Card
                                style={{cursor: 'default'}}
                                title={"Platform"}
                                loading={loading}
                                variant="borderless"
                                hoverable
                                className="z-shadow"
                            >
                                {
                                    platformData.length !== 0 ? (
                                        <Pie 
                                            {...pieConfig} 
                                            data={platformData} 
                                        />
                                    ) : (
                                        <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
                                    )
                                }
                            </Card>
                        </Col>
                        <Col
                            xl={24}
                            lg={24}
                            md={24}
                            sm={24}
                            xs={24}
                            style={{
                                marginBottom: 24,
                            }}
                        >
                            <Card
                                style={{cursor: 'default'}}
                                title={"Browser"}
                                loading={loading}
                                variant="borderless"
                                hoverable
                                className="z-shadow"
                            >
                                {
                                    browserData.length !== 0 ? (
                                        <Pie 
                                            {...pieConfig} 
                                            data={browserData} 
                                        />
                                    ) : (
                                        <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
                                    )
                                }
                            </Card>
                        </Col>
                    </Row>
                </Col>
                <Col
                    xl={12}
                    lg={12}
                    md={24}
                    sm={24}
                    xs={24}
                    style={{
                        marginBottom: 24,
                    }}
                >
                    <Row>
                        <Col
                            xl={24}
                            lg={24}
                            md={24}
                            sm={24}
                            xs={24}
                            style={{
                                marginBottom: 24,
                            }}
                        >
                            <Card
                                style={{cursor: 'default'}}
                                title={"Location"}
                                loading={loading}
                                variant="borderless"
                                hoverable
                                className="z-shadow"
                            >
                                {
                                    locationData.length !== 0 ? (
                                        <WordCloud {...wordCloudConfig} data={locationData} height={391}/>
                                    ) : (
                                        <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
                                    )
                                }
                            </Card>
                        </Col>
                        <Col
                            xl={24}
                            lg={24}
                            md={24}
                            sm={24}
                            xs={24}
                            style={{
                                marginBottom: 24,
                            }}
                        >
                            <Card
                                style={{cursor: 'default'}}
                                title={"Device"}
                                loading={loading}
                                variant="borderless"
                                hoverable
                                className="z-shadow"
                            >
                                {
                                    deviceData.length !== 0 ? (
                                        <Pie 
                                            {...pieConfig} 
                                            data={deviceData} 
                                        />
                                    ) : (
                                        <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} />
                                    )
                                }
                            </Card>
                        </Col>
                    </Row>
                </Col>
            </Row>
        </React.Fragment>
    )
}

export default Visitors;
