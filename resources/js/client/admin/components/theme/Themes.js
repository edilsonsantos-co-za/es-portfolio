import React, { useEffect, useState } from 'react';
import { Alert, Button, Card, Checkbox, Col, Empty, Popconfirm, Row, Space, Spin, Tag, Typography, Upload } from 'antd';
import { CloudDownloadOutlined, UploadOutlined } from '@ant-design/icons';
import PageWrapper from '../layout/PageWrapper';
import PageHeader from '../PageHeader';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';

const { Text, Paragraph } = Typography;

const shotStyle = { aspectRatio: '16 / 10', objectFit: 'cover', objectPosition: 'top', width: '100%' };

/**
 * Portfolio -> Themes: install ES Marketplace themes from the store or from a
 * downloaded package, switch the public theme, and remove themes.
 */
const Themes = () => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(null);
    const [activateUpload, setActivateUpload] = useState(true);

    const load = () => {
        setLoading(true);
        HTTP.get(Routes.api.admin.themes)
            .then(response => Utils.handleSuccessResponse(response, () => setData(response.data.payload)))
            .catch(error => Utils.handleException(error))
            .finally(() => setLoading(false));
    };

    useEffect(load, []);

    // Every action reloads the page data so the list, the active badge and the
    // store's Installed/Update state stay in step with the server.
    const run = (key, request) => {
        setBusy(key);
        request()
            .then(response => Utils.handleSuccessResponse(response, () => {
                Utils.showTinyNotification(response.data.message, 'success');
                load();
            }))
            .catch(error => Utils.handleException(error))
            .finally(() => setBusy(null));
    };

    const install = (id, activate) => run('install-' + id + (activate ? '-a' : ''), () => HTTP.post(Routes.api.admin.themes + '/' + id + '/install', { activate }));
    const activate = (id) => run('activate-' + id, () => HTTP.post(Routes.api.admin.themes + '/' + id + '/activate'));
    const remove = (id) => run('remove-' + id, () => HTTP.delete(Routes.api.admin.themes + '/' + id));

    const upload = ({ file, onSuccess, onError }) => {
        const formData = new FormData();
        formData.append('package', file);
        formData.append('activate', activateUpload ? '1' : '0');
        setBusy('upload');
        HTTP.post(Routes.api.admin.themes + '/upload', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
            .then(response => Utils.handleSuccessResponse(response, () => {
                Utils.showTinyNotification(response.data.message, 'success');
                onSuccess();
                load();
            }, () => onError(new Error(response.data.message))))
            .catch(error => {
                Utils.handleException(error);
                onError(error);
            })
            .finally(() => setBusy(null));
    };

    const installed = data ? data.installed : [];
    const active = data ? data.active : 'atlas';

    return (
        <PageWrapper>
            <PageHeader
                title="Themes"
                subTitle="Change how your portfolio looks without touching its content."
                extra={data ? [<a key="store" href={data.storeUrl} target="_blank" rel="noreferrer">Open the ES Marketplace ↗</a>] : []}
            />
            <Spin spinning={loading && !data}>
                <Card title="Installed" style={{ marginBottom: 16 }}>
                    <Row gutter={[16, 16]}>
                        {installed.map(theme => (
                            <Col key={theme.id} xs={24} sm={12} lg={8} xl={6}>
                                <Card
                                    size="small"
                                    cover={<img alt="" src={Utils.mediaUrl(theme.image)} style={shotStyle}/>}
                                    actions={[
                                        theme.id === active
                                            ? <Text key="active" type="success">Active</Text>
                                            : <Button key="activate" type="link" loading={busy === 'activate-' + theme.id} onClick={() => activate(theme.id)}>Activate</Button>,
                                        theme.builtin
                                            ? <Text key="builtin" type="secondary">Built in</Text>
                                            : (
                                                <Popconfirm
                                                    key="remove"
                                                    title={'Remove ' + theme.title + '?'}
                                                    description={theme.id === active ? 'The portfolio switches back to Atlas.' : null}
                                                    okText="Remove"
                                                    okButtonProps={{ danger: true }}
                                                    onConfirm={() => remove(theme.id)}
                                                >
                                                    <Button type="link" danger loading={busy === 'remove-' + theme.id}>Remove</Button>
                                                </Popconfirm>
                                            ),
                                    ]}
                                >
                                    <Card.Meta
                                        title={theme.title}
                                        description={
                                            <Space direction="vertical" size={2}>
                                                <Text type="secondary" style={{ fontSize: 12 }}>
                                                    {[theme.version ? 'v' + theme.version : null, theme.license, theme.author].filter(Boolean).join(' · ')}
                                                </Text>
                                                {theme.summary && <Text type="secondary">{theme.summary}</Text>}
                                                {!theme.builtin && !theme.signed && <Tag color="warning">Unsigned package</Tag>}
                                                {theme.repository && <a href={theme.repository} target="_blank" rel="noreferrer">Source ↗</a>}
                                            </Space>
                                        }
                                    />
                                </Card>
                            </Col>
                        ))}
                    </Row>
                </Card>

                <Card title="ES Marketplace" style={{ marginBottom: 16 }}>
                    {data && data.storeError && <Alert type="warning" showIcon message={data.storeError}/>}
                    {data && !data.storeError && data.store.length === 0 && <Empty description="The store has no portfolio themes to install right now."/>}
                    <Row gutter={[16, 16]}>
                        {(data ? data.store : []).map(theme => (
                            <Col key={theme.id} xs={24} sm={12} lg={8} xl={6}>
                                <Card
                                    size="small"
                                    cover={theme.preview ? <img alt="" src={data.storeUrl + '/' + theme.preview} style={shotStyle}/> : null}
                                    actions={theme.state === 'installed' ? [<Text key="installed" type="secondary">Installed</Text>] : [
                                        <Button
                                            key="install"
                                            type="link"
                                            icon={<CloudDownloadOutlined/>}
                                            loading={busy === 'install-' + theme.id + '-a'}
                                            onClick={() => install(theme.id, theme.state === 'new')}
                                        >
                                            {theme.state === 'update' ? 'Update' : 'Install & activate'}
                                        </Button>,
                                        ...(theme.state === 'new' ? [
                                            <Button key="install-only" type="link" loading={busy === 'install-' + theme.id} onClick={() => install(theme.id, false)}>Install only</Button>,
                                        ] : []),
                                    ]}
                                >
                                    <Card.Meta
                                        title={theme.name}
                                        description={
                                            <Space direction="vertical" size={2}>
                                                <Text type="secondary" style={{ fontSize: 12 }}>
                                                    {'v' + theme.version + ' · ' + theme.license + ' · ' + Math.max(1, Math.round(theme.size / 1024)) + ' KB'}
                                                </Text>
                                                <Text type="secondary">{theme.summary}</Text>
                                                {theme.repository && <a href={theme.repository} target="_blank" rel="noreferrer">Source ↗</a>}
                                            </Space>
                                        }
                                    />
                                </Card>
                            </Col>
                        ))}
                    </Row>
                </Card>

                <Card title="Upload a package">
                    <Paragraph type="secondary">
                        Downloaded a theme from the store? Upload the .zip here. Only packages signed by the ES Marketplace store install.
                    </Paragraph>
                    <Space wrap>
                        <Upload accept=".zip,application/zip" showUploadList={false} customRequest={upload} maxCount={1}>
                            <Button icon={<UploadOutlined/>} loading={busy === 'upload'}>Choose package</Button>
                        </Upload>
                        <Checkbox checked={activateUpload} onChange={e => setActivateUpload(e.target.checked)}>Activate after installing</Checkbox>
                    </Space>
                </Card>
            </Spin>
        </PageWrapper>
    );
};

export default Themes;
