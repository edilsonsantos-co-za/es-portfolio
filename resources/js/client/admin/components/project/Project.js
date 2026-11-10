import React, { useEffect, useState } from 'react';
import { Drawer, Button, Spin, Input, Form, Select, Modal, Upload } from 'antd';
import styled from 'styled-components';
import PropTypes from 'prop-types';
import HTTP from '../../../common/helpers/HTTP';
import Utils from '../../../common/helpers/Utils';
import Routes from '../../../common/helpers/Routes';
import FileUploaderFormInput from '../uploader/FileUploaderFormInput';
import { PlusOutlined } from '@ant-design/icons';

const { Option } = Select;

const StyledDrawer = styled(Drawer)`
    .ant-drawer-content-wrapper {
        width: 520px !important;
        @media (max-width: 768px) {
            max-width: calc(100vw - 16px) !important;
        }
    }
`;

const Project = (props) => {
    const [visible, setVisible] = useState(false);
    const [form] = Form.useForm();
    const [loading, setLoading] = useState((typeof props.loading !== 'undefined') ? props.loading : false);
    const [componentLoading, setComponentLoading] = useState((typeof props.componentLoading !== 'undefined') ? props.componentLoading : false);
    const [previewVisible, setPreviewVisible] = useState(false);
    const [imageFileList, setImageFileList] = useState([]);
    const [previewImage, setPreviewImage] = useState('');

    useEffect(() => {
        if (props.itemToEdit) {
            var newImageArray = [];
            JSON.parse(props.itemToEdit.images).forEach((element, index) => {
                newImageArray.push({
                    uid: index,
                    name: 'image.png',
                    status: 'done',
                    url: Utils.mediaUrl(element),
                    existingPath: element,
                })
            });
            setImageFileList(newImageArray);

            form.setFieldsValue({
                images: newImageArray,
            });
        } else {
            form.setFieldsValue({
                images: '',
            });
        }

        form.setFieldsValue({
            id: props.itemToEdit ? props.itemToEdit.id : '', 
            title: props.itemToEdit ? props.itemToEdit.title : '', 
            thumbnail: props.itemToEdit ? props.itemToEdit.thumbnail : '',
            details: props.itemToEdit ? props.itemToEdit.details : '',
            link: props.itemToEdit ? props.itemToEdit.link : '',
            video_url: props.itemToEdit ? (props.itemToEdit.video_url || '') : '',
            links: props.itemToEdit && props.itemToEdit.links ? JSON.parse(props.itemToEdit.links).map(l => `${l.label} | ${l.url}`).join('\n') : '',
            categories: props.itemToEdit ? JSON.parse(props.itemToEdit.categories) : []
        });
    }, [props.itemToEdit])

    useEffect(() => {
        form.setFieldsValue({
            images: imageFileList.length ? imageFileList : ''
        });
    }, [imageFileList])

    useEffect(() => {
        setTimeout(() => {
            setVisible(props.visible);
        }, 100);
    }, [props.visible])

    useEffect(() => {
        if (typeof props.loading !== 'undefined') {
            setLoading(props.loading)
        }
    }, [props.loading])

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

    const handleOk = () => {
        form
        .validateFields()
        .then(async (values) => {
            
            //save form
            setLoading(true);

            const formData = new FormData();
            values.id && formData.append('_method', 'put');

            values.id && formData.append('id', values.id);
            formData.append('title', values.title);
            values.categories.forEach(category => {
                formData.append('categories[]', category);
            });
            if (typeof values.thumbnail === 'string') {
                formData.append('existing_thumbnail', values.thumbnail);
            } else {
                formData.append('thumbnail', values.thumbnail);
            }
            
            for (const file of values.images) {
                if (file.originFileObj) {
                    formData.append('images[]', file.originFileObj);
                } else if (file.existingPath) {
                    formData.append('existing_images[]', file.existingPath);
                }
            }
            
            values.details && formData.append('details', values.details);

            values.link && formData.append('link', values.link);
            formData.append('video_url', values.video_url || '');
            formData.append('links', values.links || '');

            HTTP.post(Routes.api.admin.projects+(values.id ? `/${values.id}` : '' ), formData)
            .then(response => {
                Utils.handleSuccessResponse(response, () => {
                    form.resetFields();
                    Utils.showNotification(response.data.message, 'success');
                    props.submitCallback();
                })
            })
            .catch((error) => {
                Utils.handleException(error);
            }).finally(() => {
                setLoading(false);
            });
        })
        .catch((info) => {
            console.log('Validate Failed:', info);
        });
    }

    const thumbnailUploadOnChangeCallback = (files) => {
        form.setFieldsValue({
            thumbnail: files.length ? files[0] : ''
        });
    }

    const imageListHandleChange = (info) => {
        setImageFileList(info.fileList.filter(file => validateImage(file)))
    };

    const uploadButton = (
      <div>
        <PlusOutlined />
        <div style={{ marginTop: 8 }}>Upload</div>
      </div>
    );

    const validateImage = (file) => {
        if (!file.url && !file.preview) {
            const validType = file.type === 'image/jpeg' || file.type === 'image/jpg' || file.type === 'image/png';
            if (!validType) {
                Utils.showTinyNotification('You can only upload image file!', 'error');
            }
            const validSize = file.size / 1024 / 1024 < 5;
            if (!validSize) {
                Utils.showTinyNotification('Image must smaller than 5MB!', 'error');
            }
            return validType && validSize;
        }
        return true;
    }

    const handlePreview = async file => {
        if (!file.url && !file.preview) {
            file.preview = await getBase64(file.originFileObj);
        }

        setPreviewImage(file.url || file.preview);
        setPreviewVisible(true);
    };

    const getBase64 = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    }

    return (
        <StyledDrawer
            title={props.title}
            onClose={handleClose}
            open={visible}
            destroyOnClose={true}
            maskClosable={false}
            forceRender={true}
            footer={
                <div
                    style={{
                        textAlign: 'right',
                    }}
                >
                    <Button disabled={componentLoading} onClick={handleClose} style={{ marginRight: 8 }}>
                        Cancel
                    </Button>
                    <Button disabled={componentLoading} onClick={handleOk} type="primary" loading={loading}>
                        Save
                    </Button>
                </div>
            }
        >
            <Spin spinning={componentLoading} size="large" delay={500}>
                <Form
                    preserve={false}
                    form={form}
                    layout="vertical"
                    name="project"
                >
                    <Form.Item name="id" hidden>
                        <Input/>
                    </Form.Item>
                    <Form.Item
                        name="title"
                        label="Title"
                        rules={[
                            {
                                required: true,
                            },
                        ]}
                    >
                        <Input placeholder="Enter Title"/>
                    </Form.Item>
                    <Form.Item
                        name="categories"
                        label="Category"
                        rules={[
                            {
                                required: true,
                                message: 'Please select project category'
                            },
                        ]}
                    >
                        <Select
                            mode="multiple"
                            allowClear
                            placeholder="Select Existing or Create New"
                        >
                            {props.categories.map((category, index) => (
                                <Option key={index} value={category}>{category}</Option>
                            ))}
                        </Select>
                    </Form.Item>
                    <Form.Item
                        name="thumbnail"
                        label="Thumbnail"
                        rules={[
                            {
                                required: true,
                                message: 'Please upload thumbnail',
                            },
                        ]}
                    >
                        <FileUploaderFormInput
                            onChangeCallback={thumbnailUploadOnChangeCallback}
                            acceptedFileTypes={"image/*"}
                            previewFile={props.itemToEdit ? Utils.mediaUrl(props.itemToEdit.thumbnail) : null}
                        />
                    </Form.Item>
                    <Form.Item
                        name="images"
                        label="Images"
                        rules={[
                            {
                                required: true,
                                message: 'Please upload images of the project',
                            },
                        ]}
                    >
                        <Upload
                            accept="image/png,image/jpeg,image/jpg"
                            listType="picture-card"
                            fileList={imageFileList}
                            beforeUpload={
                                () => {
                                    return false;
                                }
                            }
                            onPreview={handlePreview}
                            onChange={imageListHandleChange}
                        >
                            {imageFileList.length >= 8 ? null : uploadButton}
                        </Upload>
                    </Form.Item>
                    <Form.Item
                        name="link"
                        label="Link"
                        rules={[
                            {
                                type: 'url',
                                message: 'Please enter a valid link'
                            },
                        ]}
                    >
                        <Input placeholder="Enter Link"/>
                    </Form.Item>
                    <Form.Item
                        name="video_url"
                        label="Video"
                        extra="A YouTube link, shown above the screenshots."
                        rules={[{ type: 'url', message: 'Please enter a valid link' }]}
                    >
                        <Input placeholder="https://www.youtube.com/watch?v=..."/>
                    </Form.Item>
                    <Form.Item
                        name="links"
                        label="More links"
                        extra='One per line: "Label | https://..." (e.g. Source code | https://github.com/...)'
                    >
                        <Input.TextArea rows={3} placeholder={'Source code | https://github.com/...\nCase study | https://...'}/>
                    </Form.Item>
                    <Form.Item 
                        name="details" 
                        label="Details"
                    >
                        <Input.TextArea rows={4} placeholder="Enter Details"/>
                    </Form.Item>
                </Form>
                <Modal
                    open={previewVisible}
                    title={'Preview'}
                    footer={null}
                    centered
                    onCancel={() => setPreviewVisible(false)}
                >
                    <img alt="example" style={{ width: '100%' }} src={previewImage} />
                </Modal>
            </Spin>
        </StyledDrawer>
    )
}

Project.propTypes = {
    handleCancel: PropTypes.func.isRequired,
    submitCallback: PropTypes.func.isRequired,
    visible: PropTypes.bool.isRequired,
    itemToEdit: PropTypes.object,
    loading: PropTypes.bool,
    componentLoading: PropTypes.bool,
    title: PropTypes.node,
    categories: PropTypes.array.isRequired,
}

export default Project;
