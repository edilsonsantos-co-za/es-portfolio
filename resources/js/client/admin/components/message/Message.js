import React, { useEffect, useState } from 'react';
import { Drawer, Button, Spin, Divider, Row, Col, Input, Form, Typography } from 'antd';
import { SendOutlined } from '@ant-design/icons';
import HTTP from '../../../common/helpers/HTTP';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';
import styled from 'styled-components';
import PropTypes from 'prop-types';
import moment from 'moment';

const StyledDrawer = styled(Drawer)`
    .ant-drawer-content-wrapper {
        width: 520px !important;
        @media (max-width: 768px) {
            max-width: calc(100vw - 16px) !important;
        }
    }
`;

const StyledTitle = styled.p`
    display: block;
    margin-bottom: 16px;
    color: rgba(0, 0, 0, 0.85);
    font-size: 16px;
    line-height: 1.5715;
    margin-bottom: 16px;
`;

const Reply = styled.div`
    padding: 12px 14px;
    margin-bottom: 10px;
    border-radius: 10px;
    background: rgba(59, 130, 246, .08);
    white-space: pre-line;
`;

/**
 * Message details with the conversation and a reply box. Sending emails the reply to the sender
 * (Reply-To: the owner), records it under the message and marks the message replied.
 */
const Message = (props) => {
    const [visible, setVisible] = useState(false);
    const [data, setData] = useState(props.data);
    const [sending, setSending] = useState(false);
    const [form] = Form.useForm();

    useEffect(() => {
        if (!props.data) return;
        setData(props.data);
        // The list does not include replies; fetch the full message.
        HTTP.get(Routes.api.admin.messages + '/' + props.data.id)
        .then(response => {
            Utils.handleSuccessResponse(response, () => setData(response.data.payload));
        })
        .catch(error => Utils.handleException(error));
    }, [props.data && props.data.id])

    const sendReply = (values) => {
        setSending(true);
        HTTP.post(Routes.api.admin.messages + '/' + data.id + '/reply', { body: values.body })
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                Utils.showNotification(response.data.message, 'success');
                setData(response.data.payload);
                form.resetFields();
                if (props.submitCallback) props.submitCallback();
            });
        })
        .catch(error => Utils.handleException(error))
        .finally(() => setSending(false));
    }
    const [componentLoading, setComponentLoading] = useState((typeof props.componentLoading !== 'undefined') ? props.componentLoading : false);

    useEffect(() => {
        setTimeout(() => {
            setVisible(props.visible);
        }, 100);
    }, [props.visible])

    useEffect(() => {
        if (typeof props.componentLoading !== 'undefined') {
            setComponentLoading(props.componentLoading)
        }
    }, [props.componentLoading])

    const handleClose = () => {
        setVisible(false);
        setTimeout(() => {
            props.handleCancel();
        }, 400);
    };

    return (
        <StyledDrawer
            title={props.title}
            onClose={handleClose}
            open={visible}
            destroyOnClose={true}
            maskClosable={true}
            forceRender={true}
            footer={
                <div
                    style={{
                        textAlign: 'right',
                    }}
                >
                    <Button disabled={componentLoading} onClick={handleClose} style={{ marginRight: 8 }}>
                        Close
                    </Button>
                </div>
            }
        >
            <Spin spinning={componentLoading} size="large" delay={500}>
                <StyledTitle>Sender Name</StyledTitle>
                <Row>
                    <Col span={24}>
                        {data && data.name}
                    </Col>
                </Row>
                <Divider />
                <StyledTitle>Sender Email</StyledTitle>
                <Row>
                    <Col span={24}>
                        {data && data.email}
                    </Col>
                </Row>
                <Divider />
                <StyledTitle>Subject</StyledTitle>
                <Row>
                    <Col span={24}>
                        {data && data.subject}
                    </Col>
                </Row>
                <Divider/>
                <StyledTitle>Body</StyledTitle>
                <Row>
                    <Col span={24}>
                        {data && data.body}
                    </Col>
                </Row>
                <Divider/>
                <StyledTitle>Received</StyledTitle>
                <Row>
                    <Col span={24}>
                        {data && moment(data.created_at).fromNow()}
                    </Col>
                </Row>
                <Divider/>
                <StyledTitle>Replies</StyledTitle>
                {data && data.replies && data.replies.length > 0 ? data.replies.map(r => (
                    <Reply key={r.id}>
                        {r.body}
                        <Typography.Text type="secondary" style={{ display: 'block', marginTop: 6, fontSize: 12 }}>
                            Sent {moment(r.sent_at || r.created_at).fromNow()}
                        </Typography.Text>
                    </Reply>
                )) : (
                    <Typography.Text type="secondary">No reply sent from here yet.</Typography.Text>
                )}
                <Form form={form} layout="vertical" onFinish={sendReply} style={{ marginTop: 16 }}>
                    <Form.Item
                        name="body"
                        label={data ? `Reply to ${data.name}` : 'Reply'}
                        rules={[{ required: true, message: 'Write a reply first' }]}
                        extra="Emailed to the sender; their reply comes to your inbox."
                    >
                        <Input.TextArea rows={6} autoFocus={!!props.replyMode} maxLength={10000} />
                    </Form.Item>
                    <Button type="primary" htmlType="submit" loading={sending} icon={<SendOutlined />}>
                        Send reply
                    </Button>
                </Form>
            </Spin>
        </StyledDrawer>
    )
}

Message.propTypes = {
    handleCancel: PropTypes.func.isRequired,
    visible: PropTypes.bool.isRequired,
    data: PropTypes.object,
    componentLoading: PropTypes.bool,
    title: PropTypes.node,
    replyMode: PropTypes.bool,
    submitCallback: PropTypes.func,
}

export default Message;