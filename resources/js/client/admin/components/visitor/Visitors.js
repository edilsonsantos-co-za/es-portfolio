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

const pieConfig = {
    appendPadding: 10,
    angleField: 'value',
    colorField: 'name',
    radius: 1,
    height: 200,
    innerRadius: 0.6,
    label: {
        position: 'inside',
        autoRotate: false,
        text: (d) => `${parseFloat(d.percent * 100).toFixed(0)}%`,
        style: {
            textAlign: 'center',
            fontSize: 14,
            fill: '#fff',
        },
    },
    interactions: [{ type: 'element-selected' }, { type: 'element-active' }],
    statistic: {
        title: false,
        content: {
            style: {
                whiteSpace: 'pre-wrap',
                overflow: 'hidden',
                textOverflow: 'ellipsis',
            },
            formatter: function formatter() {
                return '';
            },
        },
    },
};

const wordCloudConfig = {
    wordField: 'name',
    weightField: 'value',
    colorField: 'name',
    wordStyle: {
        fontFamily: 'Verdana',
        fontSize: [8, 32],
        rotation: 0,
    },
    random: function random() {
        return 0.5;
    },
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

    const [visitorsData, setVisitorsData] = useState([{
        total: 0,
        new: 0,
        old: 0,
    }]);

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
                const result = response.data.payload;

                if (result) {
                    //visitors
                    setVisitorsData({
                        total: parseInt(result.visitors.total),
                        new: parseInt(result.visitors.new),
                        old: parseInt(result.visitors.old)
                    });

                    //location
                    let locationArray = [];
                    result.location.forEach(element => {
                        locationArray.push({
                            name: element.location,
                            value: parseInt(element.total)
                        })
                    });
                    setLocationData(locationArray);

                    //device
                    let deviceArray = [];

                    if (parseInt(result.device.desktop)) {
                        deviceArray.push({
                            name: "Desktop",
                            value: parseInt(result.device.desktop)
                        })
                    }
                    
                    if (parseInt(result.device.mobile)) {
                        deviceArray.push({
                            name: "Mobile",
                            value: parseInt(result.device.mobile)
                        })
                    }

                    setDeviceData(deviceArray);

                    //browser
                    let browserArray = [];
                    result.browser.forEach(element => {
                        browserArray.push({
                            name: element.browser,
                            value: parseInt(element.total)
                        })
                    });
                    setBrowserData(browserArray);

                    //platform
                    let platformArray = [];
                    result.platform.forEach(element => {
                        platformArray.push({
                            name: element.platform,
                            value: parseInt(element.total)
                        })
                    });
                    setPlatformData(platformArray);
                }
            });
        })
        .catch(error => {
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
                            setVisitorsData({
                                total: 0,
                                new: 0,
                                old: 0
                            });

                            //location
                            setLocationData([]);

                            //device
                            setDeviceData([]);

                            //browser
                            setBrowserData([]);

                            //platform
                            setPlatformData([]);

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