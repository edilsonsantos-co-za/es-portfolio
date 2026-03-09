import { Card } from "antd"
import React from "react";
import PropTypes from 'prop-types';

const PageWrapper = ({children, className = '', loading = false, noPadding = false}) => {
    return (
        <React.Fragment>
            <Card 
                variant="borderless"
                styles={{ body: {padding: noPadding ? 0 : undefined} }} 
                hoverable 
                className={`${className} z-shadow`} 
                style={{cursor: 'default'}} 
                loading={loading}
            >
                {children}
            </Card>
        </React.Fragment>
    )
}

PageWrapper.propTypes = {
    children: PropTypes.node,
    className: PropTypes.string,
    loading: PropTypes.bool,
    noPadding: PropTypes.bool,
}

export default PageWrapper;