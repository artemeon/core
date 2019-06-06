// This webpack config is identically equal to the normal webpack.config.js .It compiles a bundle and lets you analyse it with graphical diagrams
//! important : npm run analyse | to use this script
const path = require('path')
const webpack = require('webpack')
const glob = require('glob')
const Dotenv = require('dotenv-webpack')
const LiveReloadPlugin = require('webpack-livereload-plugin')
const TerserPlugin = require('terser-webpack-plugin')
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer')
    .BundleAnalyzerPlugin
const WatchMissingNodeModulesPlugin = require('react-dev-utils/WatchMissingNodeModulesPlugin')
const { VueLoaderPlugin } = require('vue-loader')
const liveReloadOptions = {
    hostname: 'localhost',
    protocol: 'http'
}

module.exports = async env => {
    const devMode = env.NODE_ENV !== 'production'
    console.log('Build Type : Analyse Bundle')

    return {
        entry: {
            agp: glob.sync(
                '../../{core,core_agp,core_customer}/module_*/scripts/**/*.ts'
            )
        },
        output: {
            filename: './[name].min.js',
            path: path.resolve(__dirname, '../module_system/scripts/')
        },

        module: {
            rules: [
                {
                    test: /\.vue$/,

                    loader: 'vue-loader',
                    options: {
                        loaders: {
                            scss: 'vue-style-loader!css-loader!sass-loader',
                            sass:
                                'vue-style-loader!css-loader!sass-loader?indentedSyntax'
                        }
                    }
                },
                {
                    test: /\.tsx?$/,
                    use: [
                        { loader: 'babel-loader' },
                        {
                            loader: 'ts-loader',
                            options: {
                                appendTsSuffixTo: [/\.vue$/],
                                configFile: path.resolve(
                                    __dirname,
                                    './tsconfig.json'
                                )
                            }
                        }
                    ],

                    exclude: /node_modules/
                },
                {
                    test: /\.woff($|\?)|\.woff2($|\?)|\.ttf($|\?)|\.eot($|\?)|\.svg($|\?)|\.png($|\?)/,
                    loader: 'url-loader'
                },
                {
                    test: /\.less$/,
                    use: [
                        {
                            loader: 'style-loader'
                        },
                        {
                            loader: 'css-loader' // translates CSS into CommonJS
                        },
                        {
                            loader: 'less-loader' // compiles Less to CSS
                        }
                    ]
                },
                {
                    test: /\.css$/,
                    use: ['style-loader', 'css-loader']
                }
            ]
        },
        resolve: {
            modules: [path.resolve(__dirname, './node_modules')],
            extensions: ['.ts', '.js', '.vue', '.json'],
            alias: {
                vue$: 'vue/dist/vue.esm.js',
                'load-image': 'blueimp-load-image/js/load-image.js',
                'load-image-meta': 'blueimp-load-image/js/load-image-meta.js',
                'load-image-exif': 'blueimp-load-image/js/load-image-exif.js',
                'load-image-scale': 'blueimp-load-image/js/load-image-scale.js',
                'canvas-to-blob': 'blueimp-canvas-to-blob/js/canvas-to-blob.js',
                'jquery-ui/ui/widget':
                    'blueimp-file-upload/js/vendor/jquery.ui.widget.js',
                '@': path.resolve(__dirname, '../../'),
                core: path.resolve(__dirname, '../'),
                core_agp: path.resolve(__dirname, '../../core_agp'),
                core_customer: path.resolve(__dirname, '../../core_customer')
            }
        },
        plugins: devMode
            ? [
                new Dotenv({
                    path: devMode
                        ? path.resolve(__dirname, '.env.dev')
                        : path.resolve(__dirname, '.env.prod')
                }),
                new webpack.ProvidePlugin({
                    jQuery: 'jquery',
                    $: 'jquery',
                    jquery: 'jquery'
                }),
                new LiveReloadPlugin(liveReloadOptions),
                new WatchMissingNodeModulesPlugin(
                    path.resolve('node_modules')
                ),
                new VueLoaderPlugin()
            ]
            : [
                new Dotenv({
                    path: devMode
                        ? path.resolve(__dirname, '.env.dev')
                        : path.resolve(__dirname, '.env.prod')
                }),
                new webpack.ProvidePlugin({
                    jQuery: 'jquery',
                    $: 'jquery',
                    jquery: 'jquery'
                }),
                new BundleAnalyzerPlugin(),
                new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/),
                new VueLoaderPlugin()
            ],
        optimization: {
            minimize: !devMode,
            minimizer: !devMode
                ? [
                    new TerserPlugin({
                        terserOptions: {
                            parse: {
                                ecma: 8
                            },
                            compress: {
                                ecma: 5,
                                warnings: false,
                                comparisons: false,
                                inline: 2
                            },
                            mangle: {
                                safari10: true
                            },
                            output: {
                                ecma: 5,
                                comments: false,
                                ascii_only: true
                            }
                        },
                        parallel: true,
                        cache: true
                    })
                ]
                : [],
            splitChunks: {
                chunks: 'all',
                name: 'vendors.chunks'
            }
        }
    }
}
