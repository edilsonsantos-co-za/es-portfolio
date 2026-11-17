import PageHeader from '../PageHeader';
import { Form, Spin, Input, Button, Typography, Descriptions, Alert } from 'antd';
import React, { useEffect, useState } from 'react';
import { InfoCircleOutlined } from '@ant-design/icons';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';

/**
 * Mail settings.
 *
 * The connection (mail platform host, port, user) belongs to the server: it is set in the server's
 * environment with the rest of the secrets, so it is shown read-only and the password is never sent to
 * the browser. What the owner chooses is the sender: the name and address people see. Those are saved
 * in the database and used for every email from the next one on.
 */
const Mail = () => {
    const [loading, setLoading] = useState(false);
    const [componentLoading, setComponentLoading] = useState(false);
    const [connection, setConnection] = useState({});
    const [form] = Form.useForm();

    useEffect(() => {
        loadMailSetting();
    }, [])

    const loadMailSetting = () => {
        setComponentLoading(true);
        HTTP.get(Routes.api.admin.settings)
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                const mail = response.data.payload.mailSettings || {};
                setConnection(mail);
                form.setFieldsValue({
                    MAIL_FROM_NAME: mail.MAIL_FROM_NAME,
                    MAIL_FROM_ADDRESS: mail.MAIL_FROM_ADDRESS,
                });
            })
        })
        .catch((error) => {
            Utils.handleException(error);
        }).finally(() => {
            setComponentLoading(false);
        });
    }

    const onFinish = (values) => {
        setLoading(true);
        HTTP.post(Routes.api.admin.mailSettings, {
            MAIL_FROM_NAME: values.MAIL_FROM_NAME,
            MAIL_FROM_ADDRESS: values.MAIL_FROM_ADDRESS,
        })
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                Utils.showNotification(response.data.message, 'success');
                form.setFieldsValue(response.data.payload);
            })
        })
        .catch((error) => {
            Utils.handleException(error);
        }).finally(() => {
            setLoading(false);
        });
    };

    const domain = connection.MAIL_FROM_DOMAIN;

    return (
        <React.Fragment>
            <PageHeader
                title="Mail Settings"
                subTitle={
                    <Typography.Text style={{ width: '100%', color: 'grey' }}>
                        Used for contact form replies, notifications and password recovery
                    </Typography.Text>
                }
            >
                <Spin spinning={componentLoading} delay={500} size="large">
                    <Form
                        layout="vertical"
                        name="mail-setting"
                        form={form}
                        onFinish={onFinish}
                    >
                        <Form.Item
                            name="MAIL_FROM_NAME"
                            label="Sender Name"
                            rules={[
                                { required: true, message: 'Sender name is required' },
                                { max: 80, message: 'At most 80 characters' },
                            ]}
                            tooltip={{ title: 'The name people see in their inbox', icon: <InfoCircleOutlined /> }}
                        >
                            <Input placeholder="ES Portfolio"/>
                        </Form.Item>
                        <Form.Item
                            name="MAIL_FROM_ADDRESS"
                            label="Sender Email"
                            rules={[
                                { required: true, message: 'Sender email is required' },
                                { type: 'email', message: 'Not a valid email address' },
                                ...(domain ? [{
                                    validator: (_, value) => !value || value.toLowerCase().endsWith('@' + domain)
                                        ? Promise.resolve()
                                        : Promise.reject(new Error(`Must end in @${domain}: the mail platform only sends for that domain`)),
                                }] : []),
                            ]}
                            tooltip={{ title: 'Replies are not read at this address', icon: <InfoCircleOutlined /> }}
                        >
                            <Input placeholder={domain ? `noreply@${domain}` : 'Sender Email'}/>
                        </Form.Item>
                        <Form.Item style={{ textAlign: 'right' }}>
                            <Button loading={loading} type="primary" htmlType="submit">
                                Save Sender
                            </Button>
                        </Form.Item>
                    </Form>

                    <Alert
                        type="info"
                        showIcon
                        style={{ marginBottom: 16 }}
                        message="Connection"
                        description="Set on the server with the other secrets, so it is read-only here."
                    />
                    <Descriptions bordered size="small" column={1}>
                        <Descriptions.Item label="Driver">{connection.MAIL_MAILER || '-'}</Descriptions.Item>
                        <Descriptions.Item label="Host">{connection.MAIL_HOST || '-'}</Descriptions.Item>
                        <Descriptions.Item label="Port">{connection.MAIL_PORT || '-'}</Descriptions.Item>
                        <Descriptions.Item label="Username">{connection.MAIL_USERNAME || '-'}</Descriptions.Item>
                    </Descriptions>
                </Spin>
            </PageHeader>
        </React.Fragment>
    );
};

export default Mail;
