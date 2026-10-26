import React from 'react';
import PropTypes from 'prop-types';
import { toggleThemeMode } from '../helpers/ThemeMode';

/**
 * Sun/moon button matching the public Atlas theme: moon in light mode, sun in dark mode.
 */
const ThemeToggle = ({ className = 'atlas-auth-icon-btn' }) => (
    <button className={className} type="button" onClick={toggleThemeMode} aria-label="Toggle dark mode" title="Toggle dark mode">
        <svg className="i-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M20.5 14.3A8.5 8.5 0 1 1 9.7 3.5a6.8 6.8 0 0 0 10.8 10.8z"/></svg>
        <svg className="i-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    </button>
);

ThemeToggle.propTypes = {
    className: PropTypes.string,
};

export default ThemeToggle;
