import React, { useEffect, useState } from 'react';
import { Table, Input, Tag, Tooltip, Typography, theme } from 'antd';
import { DesktopOutlined, MobileOutlined } from '@ant-design/icons';
import moment from 'moment';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';

const { Text } = Typography;

const place = (row) => {
    const parts = [row.city, row.region, row.country].filter(Boolean);
    const unique = parts.filter((part, index) => parts.indexOf(part) === index);

    return unique.length && row.country !== 'Unknown' ? unique.join(', ') : null;
};

// One row per IP address, showing where and with what the latest visit came in.
const VisitorIps = ({ startDate, endDate, refreshKey }) => {
    const { token } = theme.useToken();
    const [loading, setLoading] = useState(false);
    const [rows, setRows] = useState([]);
    const [keyword, setKeyword] = useState('');
    const [pagination, setPagination] = useState({ current: 1, pageSize: 20, total: 0 });

    const load = (page = pagination.current, pageSize = pagination.pageSize) => {
        setLoading(true);

        HTTP.get(Routes.api.admin.visitorsIps, { params: { page, pageSize, keyword, startDate, endDate } })
            .then(response => {
                Utils.handleSuccessResponse(response, () => {
                    const payload = response.data.payload || {};
                    setRows(Array.isArray(payload.data) ? payload.data : []);
                    setPagination({ current: payload.current_page || 1, pageSize: payload.per_page || pageSize, total: payload.total || 0 });
                }, () => setRows([]));
            })
            .catch(error => {
                setRows([]);
                Utils.handleException(error);
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        load(1);
    }, [startDate, endDate, refreshKey, keyword]);

    const columns = [
        {
            title: 'IP address',
            dataIndex: 'ip',
            render: ip => <Text copyable={{ text: ip }} style={{ fontFamily: 'monospace' }}>{ip || '—'}</Text>,
        },
        {
            title: 'Location',
            key: 'location',
            render: (_, row) => place(row) || <Text type="secondary">Unknown</Text>,
        },
        {
            title: 'Browser',
            key: 'browser',
            responsive: ['md'],
            render: (_, row) => (
                <span style={{ whiteSpace: 'nowrap' }}>
                    {row.is_desktop === null ? null : row.is_desktop
                        ? <DesktopOutlined style={{ marginRight: 6 }} />
                        : <MobileOutlined style={{ marginRight: 6 }} />}
                    {[row.browser, row.platform].filter(Boolean).join(' · ') || '—'}
                </span>
            ),
        },
        {
            title: 'User agent',
            dataIndex: 'user_agent',
            responsive: ['lg'],
            ellipsis: { showTitle: false },
            render: ua => ua
                ? <Tooltip title={ua} placement="topLeft"><Text type="secondary" style={{ fontSize: 12 }}>{ua}</Text></Tooltip>
                : <Text type="secondary">Not recorded</Text>,
        },
        {
            title: 'Visits',
            dataIndex: 'visits',
            align: 'right',
            width: 80,
            render: visits => <Tag color={visits > 1 ? token.colorPrimary : undefined} style={{ marginInlineEnd: 0 }}>{visits}</Tag>,
        },
        {
            title: 'Last seen',
            dataIndex: 'last_seen',
            width: 150,
            render: (value, row) => (
                <Tooltip title={`First seen ${moment(row.first_seen).format('D MMM YYYY, HH:mm')}`}>
                    <span style={{ whiteSpace: 'nowrap' }}>{moment(value).format('D MMM YYYY, HH:mm')}</span>
                </Tooltip>
            ),
        },
    ];

    return (
        <React.Fragment>
            <Input.Search
                allowClear
                placeholder="Search IP, location, browser or user agent"
                onSearch={value => setKeyword(value.trim())}
                style={{ maxWidth: 420, marginBottom: 16 }}
            />
            <Table
                rowKey="ip"
                size="middle"
                loading={loading}
                columns={columns}
                dataSource={rows}
                scroll={{ x: 'max-content' }}
                pagination={{ ...pagination, showSizeChanger: true, pageSizeOptions: [20, 50, 100], showTotal: total => `${total} IP addresses` }}
                onChange={pager => load(pager.current, pager.pageSize)}
                expandable={{
                    expandedRowRender: row => (
                        <div style={{ fontSize: 12 }}>
                            <div><Text type="secondary">User agent: </Text><Text copyable={!!row.user_agent}>{row.user_agent || 'Not recorded (visit predates user-agent logging)'}</Text></div>
                            <div><Text type="secondary">First seen: </Text>{moment(row.first_seen).format('D MMM YYYY, HH:mm')}</div>
                        </div>
                    ),
                }}
            />
        </React.Fragment>
    );
};

export default VisitorIps;
