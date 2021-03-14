const mix = require('laravel-mix');

mix.webpackConfig({
    resolve: {
        alias: {
            //vue: 'vue/dist/vue.js'
        },
        fallback: {
            "crypto": require.resolve("crypto-browserify"),
            "stream": false
        }
    },
    experiments: {
        syncWebAssembly: true,
        asyncWebAssembly: true,
    },
});

mix.js('resources/js/app.js', 'public/js').vue({version: 2})
    .sass('resources/sass/app.scss', 'public/css');

mix.copyDirectory('resources/assets', 'public/assets');
