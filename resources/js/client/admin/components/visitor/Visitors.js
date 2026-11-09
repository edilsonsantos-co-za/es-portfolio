import React, { useEffect, useMemo, useState } from 'react';
import { Button, Space, Modal, Row, Col, Card, DatePicker, Statistic, Empty, Tabs, theme } from 'antd';
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
    MobileOutlined,
    ExclamationCircleOutlined,
    ReloadOutlined,
} from '@ant-design/icons';
import styled from 'styled-components';
import { Pie, Column, Bar } from '@ant-design/plots';
import VisitorIps from './VisitorIps';

const { confirm } = Modal;

// Donut slices beyond this many are folded into "Other" so labels stay readable.
const MAX_SLICES = 5;
const TOP_LOCATIONS = 10;

const topWithOther = (items, limit = MAX_SLICES) => {
    const sorted = [...items].sort((a, b) => b.value - a.value);

    if (sorted.length <= limit) {
        return sorted;
    }

    const other = sorted.slice(limit).reduce((sum, item) => sum + item.value, 0);

    return [...sorted.slice(0, limit), { name: 'Other', value: other }];
};

const donutConfig = (data, colors, ink) => ({
    data,
    theme: chartTheme(ink),
    angleField: 'value',
    colorField: 'name',
    height: 240,
    innerRadius: 0.64,
    scale: { color: { range: colors } },
    legend: { color: { position: 'right', rowPadding: 6, itemMarker: 'circle', itemLabelFill: ink.text } },
    label: false,
    tooltip: { title: 'name', items: [{ field: 'value', name: 'Visits' }] },
    annotations: [{
        type: 'text',
        style: {
            text: String(data.reduce((sum, item) => sum + item.value, 0)),
            x: '50%',
            y: '50%',
            textAlign: 'center',
            fontSize: 22,
            fontWeight: 700,
            fill: ink.text,
        },
    }],
});

// The admin is dark (antd darkAlgorithm); the charts' default light theme draws black text on it.
// No `legendCategory` here: a theme override replaces that component's defaults (itemSpacing, …) and the
// legend layout then crashes ("reading '0'"), blanking every chart with a legend. Legend colours are set per chart.
const chartTheme = (ink) => ({
    type: 'classicDark',
    view: { viewFill: 'transparent' },
    axis: { labelFill: ink.muted, titleFill: ink.muted, gridStroke: ink.line, lineStroke: ink.line, tickStroke: ink.line },
    label: { fill: ink.text },
});

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

const Kpi = styled.div`
display: flex;
flex-direction: column;
gap: 4px;
.ant-statistic-title { margin-bottom: 0; }
small { color: var(--kpi-muted); font-size: 12px; }
`;

const Visitors = () => {
    const { token } = theme.useToken();
    const ink = { text: token.colorText, muted: token.colorTextSecondary, line: token.colorBorderSecondary };
    const palette = [token.colorPrimary, '#22c55e', '#f59e0b', '#a855f7', '#ef4444', '#94a3b8'];
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
    const [dailyData, setDailyData] = useState([]);
    const [refreshKey, setRefreshKey] = useState(0);

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
        setDailyData([]);
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
                setDailyData(Array.isArray(result.daily) ? result.daily.flatMap(day => [
                    { date: moment(day.date).format('D MMM'), type: 'New', value: toSafeInteger(day.new) },
                    { date: moment(day.date).format('D MMM'), type: 'Returning', value: toSafeInteger(day.returning) },
                ]) : []);
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
                            setRefreshKey(key => key + 1);

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

    const returningRate = visitorsData.total ? Math.round((visitorsData.old / visitorsData.total) * 100) : 0;
    const deviceTotal = deviceData.reduce((sum, item) => sum + item.value, 0);
    const mobileShare = deviceTotal ? Math.round(((deviceData.find(d => d.name === 'Mobile') || { value: 0 }).value / deviceTotal) * 100) : 0;
    const hasDaily = dailyData.some(point => point.value > 0);
    const topLocations = useMemo(
        () => [...locationData].sort((a, b) => b.value - a.value).slice(0, TOP_LOCATIONS),
        [locationData]
    );

    const card = (title, body, extra = null) => (
        <Card
            style={{ cursor: 'default', height: '100%' }}
            title={title}
            extra={extra}
            loading={loading}
            variant="borderless"
            className="z-shadow"
        >
            {body}
        </Card>
    );

    const empty = <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="No visits in this range" />;

    const kpis = [
        { title: 'Total visits', value: visitorsData.total, icon: <TeamOutlined />, note: 'All tracked page views' },
        { title: 'New visitors', value: visitorsData.new, icon: <UserAddOutlined />, note: 'First visit from this browser' },
        { title: 'Returning', value: visitorsData.old, icon: <UserSwitchOutlined />, note: `${returningRate}% of visits` },
        { title: 'Mobile share', value: mobileShare, suffix: '%', icon: <MobileOutlined />, note: `${deviceTotal} visits with a known device` },
    ];

    return (
        <React.Fragment>
            <Card variant="borderless" className="z-shadow" style={{ marginBottom: 24 }}>
                <Row gutter={[16, 16]} align="middle">
                    <StyledCol md={14} sm={24} xs={24} align={'left'}>
                        <DatePicker.RangePicker
                            presets={[
                                { label: 'Today', value: [moment(), moment()] },
                                { label: 'Yesterday', value: [moment().subtract(1, 'day'), moment().subtract(1, 'day')] },
                                { label: 'Last 7 days', value: [moment().subtract(6, 'day'), moment()] },
                                { label: 'Last 30 days', value: [moment().subtract(29, 'day'), moment()] },
                                { label: 'This month', value: [moment().startOf('month'), moment().endOf('month')] },
                                { label: 'Last month', value: [moment().subtract(1, 'months').startOf('month'), moment().subtract(1, 'months').endOf('month')] },
                                { label: 'Last 90 days', value: [moment().subtract(89, 'day'), moment()] },
                            ]}
                            onChange={datePickerOnChange}
                        />
                        <span style={{ marginLeft: 12, color: token.colorTextSecondary }}>
                            {date.startDate ? 'Custom range' : 'All time · chart shows the last 30 days'}
                        </span>
                    </StyledCol>
                    <StyledCol md={10} sm={24} xs={24} align={'right'}>
                        <Space>
                            <Button icon={<ReloadOutlined />} onClick={() => { loadData(); setRefreshKey(key => key + 1); }} disabled={loading}>Refresh</Button>
                            <Button danger onClick={showResetConfirm} disabled={loading}>Reset stats</Button>
                        </Space>
                    </StyledCol>
                </Row>
            </Card>

            <Tabs
                defaultActiveKey="overview"
                items={[
                    {
                        key: 'overview',
                        label: 'Overview',
                        children: (
                            <React.Fragment>
                        <Row gutter={[24, 24]} style={{ marginBottom: 24 }}>
                            {kpis.map(kpi => (
                                <Col key={kpi.title} xl={6} md={12} xs={24}>
                                    <Card loading={loading} variant="borderless" className="z-shadow" style={{ height: '100%' }}>
                                        <Kpi style={{ '--kpi-muted': token.colorTextSecondary }}>
                                            <Statistic title={kpi.title} value={kpi.value} suffix={kpi.suffix} prefix={kpi.icon} />
                                            <small>{kpi.note}</small>
                                        </Kpi>
                                    </Card>
                                </Col>
                            ))}
                        </Row>

                        <Row gutter={[24, 24]} style={{ marginBottom: 24 }}>
                            <Col xs={24}>
                                {card('Visits over time', hasDaily ? (
                                    <Column
                                        data={dailyData}
                                        xField="date"
                                        yField="value"
                                        colorField="type"
                                        stack
                                        theme={chartTheme(ink)}
                                        height={300}
                                        scale={{ color: { range: [token.colorPrimary, '#22c55e'] } }}
                                        axis={{ x: { labelAutoHide: true, labelAutoRotate: false }, y: { title: false, gridLineDash: [4, 4] } }}
                                        legend={{ color: { position: 'top', layout: { justifyContent: 'flex-end' }, itemLabelFill: ink.text } }}
                                        style={{ radiusTopLeft: 4, radiusTopRight: 4, maxWidth: 28 }}
                                        interaction={{ tooltip: { shared: true } }}
                                    />
                                ) : empty)}
                            </Col>
                        </Row>

                        <Row gutter={[24, 24]} style={{ marginBottom: 24 }}>
                            <Col xl={12} xs={24}>
                                {card(`Top locations`, topLocations.length ? (
                                    <Bar
                                        data={topLocations}
                                        xField="name"
                                        yField="value"
                                        height={Math.max(220, topLocations.length * 34)}
                                        theme={chartTheme(ink)}
                                        style={{ fill: token.colorPrimary, radiusTopRight: 4, radiusBottomRight: 4, maxWidth: 22 }}
                                        axis={{ x: { title: false, labelAutoRotate: false, labelAutoHide: false, labelTransform: 'rotate(0)', labelFill: ink.text }, y: { title: false, gridLineDash: [4, 4] } }}
                                        label={{ text: 'value', position: 'right', dx: 6, fill: ink.text }}
                                        tooltip={{ title: 'name', items: [{ field: 'value', name: 'Visits' }] }}
                                    />
                                ) : empty, locationData.length > TOP_LOCATIONS ? `${locationData.length} countries` : null)}
                            </Col>
                            <Col xl={12} xs={24}>
                                {card('Device', deviceData.length ? <Pie {...donutConfig(deviceData, palette, ink)} /> : empty)}
                            </Col>
                            <Col xl={12} xs={24}>
                                {card('Browser', browserData.length ? <Pie {...donutConfig(topWithOther(browserData), palette, ink)} /> : empty)}
                            </Col>
                            <Col xl={12} xs={24}>
                                {card('Platform', platformData.length ? <Pie {...donutConfig(topWithOther(platformData), palette, ink)} /> : empty)}
                            </Col>
                        </Row>
                            </React.Fragment>
                        ),
                    },
                    {
                        key: 'ips',
                        label: 'IP addresses',
                        children: (
                            <Card variant="borderless" className="z-shadow">
                                <VisitorIps startDate={date.startDate} endDate={date.endDate} refreshKey={refreshKey} />
                            </Card>
                        ),
                    },
                ]}
            />
        </React.Fragment>
    )
}

export default Visitors;
