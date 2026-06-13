const path = require('path');

module.exports = async ({ mode }) => {
    const { transformWithEsbuild } = await import('vite');
    const { default: laravel } = await import('laravel-vite-plugin');
    const { default: react } = await import('@vitejs/plugin-react');
    const jsAsJsx = () => ({
        name: 'load-js-files-as-jsx',
        enforce: 'pre',
        async transform(code, id) {
            if (!/resources\/js\/.*\.js$/.test(id)) {
                return null;
            }

            return transformWithEsbuild(code, id, {
                loader: 'jsx',
            });
        },
    });

    return {
        plugins: [
            jsAsJsx(),
            laravel({
                input: [
                    'resources/js/client/admin/roots/app.js',
                    'resources/js/client/frontend/roots/projects.js',
                    'resources/js/client/frontend/roots/error.js',
                ],
                refresh: true,
            }),
            react(),
        ],
        resolve: {
            alias: {
                'styled-components': path.resolve(__dirname, 'node_modules/styled-components'),
            },
        },
        define: {
            'process.env.NODE_ENV': JSON.stringify(mode === 'production' ? 'production' : 'development'),
        },
        css: {
            preprocessorOptions: {
                less: {
                    javascriptEnabled: true,
                },
            },
        },
        optimizeDeps: {
            esbuildOptions: {
                loader: {
                    '.js': 'jsx',
                },
            },
        },
    };
};
