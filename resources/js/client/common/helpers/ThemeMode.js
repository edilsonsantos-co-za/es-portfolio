import { useSyncExternalStore } from 'react';

/**
 * Light/dark mode shared with the public Atlas theme: same localStorage key and the
 * same data-theme attribute on <html>, so the choice carries over between the site and the admin.
 */
const STORAGE_KEY = 'atlas-theme';
const listeners = new Set();
const media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

const readSaved = () => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        return saved === 'dark' || saved === 'light' ? saved : null;
    } catch {
        return null;
    }
};

const saved = readSaved();
if (saved) {
    document.documentElement.setAttribute('data-theme', saved);
}

const getMode = () => {
    const forced = document.documentElement.getAttribute('data-theme');
    if (forced === 'dark' || forced === 'light') {
        return forced;
    }
    return media && media.matches ? 'dark' : 'light';
};

const subscribe = (listener) => {
    listeners.add(listener);
    media && media.addEventListener('change', listener);
    return () => {
        listeners.delete(listener);
        media && media.removeEventListener('change', listener);
    };
};

export const toggleThemeMode = () => {
    const next = getMode() === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem(STORAGE_KEY, next); } catch { /* private mode */ }
    listeners.forEach(listener => listener());
};

export const useThemeMode = () => useSyncExternalStore(subscribe, getMode);

/** Atlas palette, for components that take colours in JavaScript (Ant Design tokens, charts). */
export const ATLAS_PALETTE = {
    light: { bg: '#f3f5f9', card: '#ffffff', card2: '#f5f7fb', text: '#0f172a', muted: '#5b6478', line: '#dde3ee', accent: '#2563eb', accentSoft: '#e6eeff' },
    dark: { bg: '#090b10', card: '#121620', card2: '#181d29', text: '#e8edf6', muted: '#8f9ab0', line: '#232a38', accent: '#3b82f6', accentSoft: '#15213a' },
};
