import { Result } from 'antd';
import React from 'react';
import { createRoot } from 'react-dom/client';
import styled from 'styled-components';

const Wrapper = styled.section`
    margin-top: 30px;
`;

const code = document.querySelector('[data-code]') ? document.querySelector('[data-code]').dataset.code : 500;
const message = document.querySelector('[data-message]') ? document.querySelector('[data-message]').dataset.message : 'Something went wrong';

function App() {
    return (
        <React.Fragment>
           <Wrapper>
                <Result
                    status={code}
                    title={code}
                    subTitle={message}
                />
            </Wrapper>
        </React.Fragment>
    );
}

if (document.getElementById('react-root')) {
    const root = createRoot(document.getElementById('react-root'));
    root.render(
        <React.StrictMode>
            <App />
        </React.StrictMode>
    ); 
}