const mix = require('laravel-mix');
// require('laravel-mix-sri');

mix.webpackConfig({
    resolve: {
        alias: {
            //vue: 'vue/dist/vue.js'
        },
        fallback: {
            "vm": require.resolve("vm-browserify"),
            "crypto": require.resolve("crypto-browserify"),
            // "stream": false,
            "stream": require.resolve("stream-browserify"),
            "constants": require.resolve("constants-browserify"),
            'process/browser': require.resolve('process/browser')
        }
    },
    experiments: {
        syncWebAssembly: true,
        asyncWebAssembly: true,
    },
});

// mix.extend('i18n', new class {
//         webpackRules() {
//             return [
//                 {
//                     resourceQuery: /blockType=i18n/,
//                     type: 'javascript/auto',
//                     loader: '@kazupon/vue-i18n-loader',
//                 },
//             ];
//         }
//     }(),
// );

mix//.i18n()
    .js('resources/js/app.js', 'public/js').vue({version: 2})
    .sass('resources/sass/app.scss', 'public/css');
    // .generateIntegrityHash();

mix.copyDirectory('resources/assets', 'public/assets');

mix.copy('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'public/js');
mix.copy('node_modules/jquery/dist/jquery.min.js', 'public/js');
