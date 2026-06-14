const { fixupPluginRules } = require('@eslint/compat');
const js = require('@eslint/js');
const globals = require('globals');
const react = require('eslint-plugin-react');

module.exports = [
    {
        ignores: [
            'node_modules/**',
            'public/build/**',
        ],
    },
    js.configs.recommended,
    {
        files: ['resources/js/**/*.{js,jsx}'],
        languageOptions: {
            ecmaVersion: 2021,
            sourceType: 'module',
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
            },
            globals: {
                ...globals.browser,
            },
        },
        plugins: {
            react: fixupPluginRules(react),
        },
        rules: {
            ...react.configs.recommended.rules,
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
];
