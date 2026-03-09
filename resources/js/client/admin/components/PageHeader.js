import React from 'react';
import { Typography, Space } from 'antd';
import PropTypes from 'prop-types';

const styles = {
    container: {
        padding: '16px 0',
    },
    header: {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        marginBottom: 8,
    },
    titleRow: {
        display: 'flex',
        alignItems: 'center',
        gap: 12,
        flexWrap: 'wrap',
    },
};

const PageHeader = ({ title, subTitle, extra, children, style, className }) => (
    <div style={{ ...styles.container, ...style }} className={className}>
        <div style={styles.header}>
            <div style={styles.titleRow}>
                {title && (
                    <Typography.Title level={4} style={{ margin: 0 }}>
                        {title}
                    </Typography.Title>
                )}
                {subTitle && (
                    <Typography.Text type="secondary">{subTitle}</Typography.Text>
                )}
            </div>
            {extra && extra.length > 0 && (
                <Space>{extra}</Space>
            )}
        </div>
        {children}
    </div>
);

PageHeader.propTypes = {
    title: PropTypes.node,
    subTitle: PropTypes.node,
    extra: PropTypes.arrayOf(PropTypes.node),
    children: PropTypes.node,
    style: PropTypes.object,
    className: PropTypes.string,
};

export default PageHeader;
