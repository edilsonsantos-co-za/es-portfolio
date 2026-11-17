import React, { useEffect, useState } from 'react';
import { Button, Card, Form, Image, Input, List, Modal, Popconfirm, Space, Tag, Typography, Upload } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined, UploadOutlined } from '@ant-design/icons';
import moment from 'moment';
import PropTypes from 'prop-types';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';

/**
 * Courses completed (with a certificate image) and learning plans, as shown on the public Education page.
 * A plan has no date and no certificate, so it can never read as a qualification already earned.
 */
const LABELS = {
    certificate: { title: 'Courses completed', one: 'course', hint: 'Certificates of course completion, oldest first on the site.' },
    plan: { title: 'Where I want to grow next', one: 'plan', hint: 'Goals, not qualifications earned. No dates are shown.' },
};

const ItemForm = ({ kind, item, open, onClose, onSaved }) => {
    const [form] = Form.useForm();
    const [file, setFile] = useState(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) return;
        setFile(null);
        form.setFieldsValue({
            title: item ? item.title : '',
            issuer: item ? item.issuer : '',
            summary: item ? item.summary : '',
            verification_url: item ? item.verification_url : '',
            completed_on: item && item.completed_on ? String(item.completed_on).slice(0, 10) : '',
        });
    }, [open, item]);

    const submit = (values) => {
        const data = new FormData();
        data.append('kind', kind);
        ['title', 'issuer', 'summary'].forEach(k => data.append(k, values[k] || ''));
        if (kind === 'certificate') {
            data.append('completed_on', values.completed_on || '');
            if (values.verification_url) data.append('verification_url', values.verification_url);
            if (file) data.append('image', file);
        }
        setSaving(true);
        HTTP.post(Routes.api.admin.learning + (item ? '/' + item.id : ''), data)
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                Utils.showNotification(response.data.message, 'success');
                onSaved();
            });
        })
        .catch(error => Utils.handleException(error))
        .finally(() => setSaving(false));
    };

    return (
        <Modal
            open={open}
            title={(item ? 'Edit ' : 'Add ') + LABELS[kind].one}
            onCancel={onClose}
            okText="Save"
            confirmLoading={saving}
            onOk={() => form.submit()}
            destroyOnClose
        >
            <Form form={form} layout="vertical" onFinish={submit}>
                <Form.Item name="title" label="Title" rules={[{ required: true, message: 'Title is required' }]}>
                    <Input maxLength={191} />
                </Form.Item>
                <Form.Item name="issuer" label={kind === 'certificate' ? 'Issuer' : 'Body or institution'} rules={[{ required: true, message: 'Required' }]}>
                    <Input maxLength={191} />
                </Form.Item>
                {kind === 'certificate' && (
                    <Form.Item name="completed_on" label="Completed on" rules={[{ required: true, message: 'Completion date is required' }]}>
                        <Input type="date" max={moment().format('YYYY-MM-DD')} />
                    </Form.Item>
                )}
                <Form.Item name="summary" label="Summary">
                    <Input.TextArea rows={3} maxLength={1000} />
                </Form.Item>
                {kind === 'certificate' && (
                    <>
                        <Form.Item name="verification_url" label="Verification link (optional)" rules={[{ type: 'url', message: 'Must be a full https:// link' }]}>
                            <Input placeholder="https://" />
                        </Form.Item>
                        <Form.Item
                            label="Certificate image"
                            required={!item}
                            extra="JPG, PNG or WebP, up to 5 MB. Export a PDF certificate as an image first."
                        >
                            {item && item.image_url && !file && (
                                <Image src={item.image_url} width={160} style={{ borderRadius: 8, marginBottom: 8 }} />
                            )}
                            <Upload
                                accept="image/jpeg,image/png,image/webp"
                                maxCount={1}
                                beforeUpload={(f) => { setFile(f); return false; }}
                                onRemove={() => setFile(null)}
                                fileList={file ? [file] : []}
                            >
                                <Button icon={<UploadOutlined />}>{item ? 'Replace image' : 'Choose image'}</Button>
                            </Upload>
                        </Form.Item>
                    </>
                )}
            </Form>
        </Modal>
    );
};

ItemForm.propTypes = {
    kind: PropTypes.string.isRequired,
    item: PropTypes.object,
    open: PropTypes.bool.isRequired,
    onClose: PropTypes.func.isRequired,
    onSaved: PropTypes.func.isRequired,
};

const LearningItems = () => {
    const [data, setData] = useState({ certificates: [], plans: [] });
    const [loading, setLoading] = useState(false);
    const [editing, setEditing] = useState(null); // { kind, item }

    const load = () => {
        setLoading(true);
        HTTP.get(Routes.api.admin.learning)
        .then(response => Utils.handleSuccessResponse(response, () => setData(response.data.payload)))
        .catch(error => Utils.handleException(error))
        .finally(() => setLoading(false));
    };

    useEffect(load, []);

    const remove = (item) => {
        HTTP.delete(Routes.api.admin.learning + '/' + item.id)
        .then(response => Utils.handleSuccessResponse(response, () => {
            Utils.showTinyNotification('Removed', 'success');
            load();
        }))
        .catch(error => Utils.handleException(error));
    };

    const section = (kind, items) => (
        <Card
            key={kind}
            title={LABELS[kind].title}
            extra={<Button icon={<PlusOutlined />} onClick={() => setEditing({ kind, item: null })}>Add {LABELS[kind].one}</Button>}
            style={{ marginTop: 16 }}
        >
            <Typography.Text type="secondary">{LABELS[kind].hint}</Typography.Text>
            <List
                loading={loading}
                dataSource={items}
                locale={{ emptyText: 'Nothing here yet' }}
                renderItem={item => (
                    <List.Item
                        actions={[
                            <a key="edit" onClick={() => setEditing({ kind, item })}><EditOutlined /> Edit</a>,
                            <Popconfirm key="delete" title={`Remove "${item.title}" from the site?`} okText="Remove" onConfirm={() => remove(item)}>
                                <a><DeleteOutlined /> Remove</a>
                            </Popconfirm>,
                        ]}
                    >
                        <List.Item.Meta
                            avatar={item.image_url ? <Image src={item.image_url} width={72} style={{ borderRadius: 6 }} /> : null}
                            title={item.title}
                            description={
                                <Space size={8} wrap>
                                    <span>{item.issuer}</span>
                                    {item.completed_on ? <Tag>{moment(item.completed_on).format('D MMM YYYY')}</Tag> : <Tag color="blue">Planned</Tag>}
                                    {item.verification_url && <Tag color="green">Verifiable</Tag>}
                                </Space>
                            }
                        />
                    </List.Item>
                )}
            />
        </Card>
    );

    return (
        <>
            {section('certificate', data.certificates)}
            {section('plan', data.plans)}
            <ItemForm
                kind={editing ? editing.kind : 'certificate'}
                item={editing ? editing.item : null}
                open={!!editing}
                onClose={() => setEditing(null)}
                onSaved={() => { setEditing(null); load(); }}
            />
        </>
    );
};

export default LearningItems;
