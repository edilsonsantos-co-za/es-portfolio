import { useSyncExternalStore } from 'react';

/**
 * Light/dark mode shared with the public sites: the admin shell loads /theme-mode.js in <head>,
 * which applies the saved choice before first paint and keeps it in a parent-domain cookie,
 * so the choice carries over between the portfolio, the blog, the store and their admins.
 */
const esTheme = window.esTheme;

export const toggleThemeMode = () => esTheme.toggle();

export const useThemeMode = () => useSyncExternalStore(esTheme.subscribe, esTheme.get);

/** Atlas palette, for components that take colours in JavaScript (Ant Design tokens, charts). */
export const ATLAS_PALETTE = {
    light: { bg: '#f3f5f9', card: '#ffffff', card2: '#f5f7fb', text: '#0f172a', muted: '#5b6478', line: '#dde3ee', accent: '#2563eb', accentSoft: '#e6eeff' },
    dark: { bg: '#090b10', card: '#121620', card2: '#181d29', text: '#e8edf6', muted: '#8f9ab0', line: '#232a38', accent: '#3b82f6', accentSoft: '#15213a' },
};
