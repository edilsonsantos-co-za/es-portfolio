import { Alert, Button, Divider, Form, Input, Typography } from 'antd';
import React, { useEffect, useState } from 'react';
import AuthLayout from './AuthLayout';
import axios from 'axios';
import Routes from '../../../common/helpers/Routes';
import Utils from '../../../common/helpers/Utils';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { saveApiToken } from '../../redux/ActionCreators';
import {AiOutlineUser} from 'react-icons/ai';
import {BsShieldLock} from 'react-icons/bs';

const sso = window.edfolioSso || { enabled: false };

const SSO_ERRORS = {
    not_authorised: 'That account is not an admin of this site.',
    cancelled: 'Sign-in was cancelled.',
    state: 'Sign-in session expired. Please try again.',
    failed: 'Single sign-on failed. Please try again.',
};

const Login = () => {
    const [loading, setLoading] = useState(false);
    const [ssoError, setSsoError] = useState(null);
    const dispatch = useDispatch();
    let navigate = useNavigate();
    let location = useLocation();
    const [form] = Form.useForm();
    const {siteName, apiToken, demoMode} = useSelector(state => state.globalState);

    useEffect(() => {
        document.title = `Login - ${siteName}`;

        if (demoMode) {
            form.setFieldsValue({
                email: 'admin@admin.com', 
                password: 12345
            });
        }

        const hash = new URLSearchParams(window.location.hash.slice(1));
        if (hash.has('sso') || hash.has('sso_error')) {
            window.history.replaceState(null, '', window.location.pathname + window.location.search);
        }
        if (hash.has('sso_error')) {
            setSsoError(SSO_ERRORS[hash.get('sso_error')] || SSO_ERRORS.failed);
        }
        if (hash.has('sso')) {
            setLoading(true);
            axios.post(Routes.api.admin.ssoExchange, { code: hash.get('sso') })
                .then(response => {
                    Utils.handleSuccessResponse(response, () => {
                        dispatch(saveApiToken({ apiToken: response.data.payload.token.access_token }));
                    });
                })
                .catch((error) => {
                    Utils.handleException(error);
                })
                .finally(() => {
                    setLoading(false);
                });
        }
    }, [])

    useEffect(() => {
        if (apiToken) {
            let from = location.state?.from || { pathname: Routes.web.admin.dashboard };
            navigate(from, { replace: true });
        }
    }, [apiToken]);

    const onSubmit = (values) => {
        setLoading(true);
        
        axios.post(Routes.api.admin.login, {
            email: values.email,
            password: values.password,
        })
        .then(response => {
            Utils.handleSuccessResponse(response, () => {
                const apiToken = response.data.payload.token.access_token;

                dispatch(saveApiToken({
                    apiToken: apiToken
                }));
            });
        })
        .catch((error) => {
            Utils.handleException(error);
        }).finally(() => {
            setLoading(false);
        });
    };

    return (
        <React.Fragment>
            <AuthLayout title={'ADMIN LOGIN'}>
                {ssoError && <Alert type="error" showIcon message={ssoError} style={{ marginBottom: 16 }} />}
                {sso.enabled && (
                    <React.Fragment>
                        <Button type="primary" block size="large" href={sso.loginUrl} loading={loading}>
                            Continue with single sign-on
                        </Button>
                        <Divider plain>or use your password</Divider>
                    </React.Fragment>
                )}
                <Form
                    form={form}
                    name="login"
                    onFinish={onSubmit}
                >
                    <Form.Item
                        name="email"
                        messageVariables={{ label: 'Email' }}
                        rules={[
                            {
                                required: true,
                                message: 'Please enter your email'
                            },
                            {
                                type: 'email',
                                message: 'Invalid email address'
                            }
                        ]}
                    >
                        <Input prefix={<AiOutlineUser/>} placeholder="Email" />
                    </Form.Item>
                    <Form.Item
                        name="password"
                        messageVariables={{ label: 'Password' }}
                        rules={[
                            {
                                required: true,
                                message: 'Please enter your password'
                            }
                        ]}
                    >
                        <Input.Password
                            prefix={<BsShieldLock/>}
                            placeholder="Password"
                        />
                    </Form.Item>
                    <Form.Item>
                        <Button type="primary" htmlType="submit" block loading={loading}>
                            Log in
                        </Button>
                    </Form.Item>
                    <Divider style={{marginBottom: '5px'}}/>
                    <Form.Item className="text-center">
                        <Link to={Routes.web.admin.forgetPassword}>
                            <Typography.Text type="secondary">Forgot password?</Typography.Text>
                        </Link>
                    </Form.Item>
                </Form>
            </AuthLayout>
        </React.Fragment>
    )
}

export default Login;