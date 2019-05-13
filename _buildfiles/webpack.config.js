const path = require('path')
const webpack = require('webpack')
const glob = require('glob')
const Dotenv = require('dotenv-webpack')
const pathsFinder = require('./pathsFinder')
const LiveReloadPlugin = require('webpack-livereload-plugin')
const TerserPlugin = require('terser-webpack-plugin')
const WatchMissingNodeModulesPlugin = require('react-dev-utils/WatchMissingNodeModulesPlugin')
const { VueLoaderPlugin } = require('vue-loader')
const liveReloadOptions = {
    hostname: 'localhost',
    protocol: 'http'
}

module.exports = async env => {
    const devMode = env.NODE_ENV !== 'production'
    console.log('Build Type : ', env.NODE_ENV)
    let tsPaths = await pathsFinder.getTsPaths() // returns the paths of all the modules found in packageconfig.json
    let lessPaths = await pathsFinder.getLessPaths()

    return {
        entry: {
            agp: devMode // if dev mode compile all the modules
                ? glob.sync('../../core*/module_*/scripts/**/*.ts')
                : tsPaths // else only compile the needed modules for prod
        },
        output: {
            filename: './[name].min.js',
            path: path.resolve(__dirname, '../module_system/scripts/')
        },

        module: {
            rules: [
                {
                    test: /\.vue$/, // vue loader for the template files

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
                    test: /\.tsx?$/, // typescript loader for the .ts and .tsx files
                    use: [
                        { loader: 'babel-loader' },
                        {
                            loader: 'ts-loader',
                            options: {
                                appendTsSuffixTo: [/\.vue$/], // needed to import vue's template files in .ts files
                                configFile: path.resolve(
                                    // path to the tsconfig.json file
                                    __dirname,
                                    './tsconfig.json'
                                )
                            }
                        }
                    ],

                    exclude: /node_modules/
                },
                {
                    test: /\.woff($|\?)|\.woff2($|\?)|\.ttf($|\?)|\.eot($|\?)|\.svg($|\?)|\.(png|jpg|gif)$/, // loader for the fonts/images , makes import of this files possible
                    loader: 'url-loader'
                },
                {
                    test: /\.less$/,
                    use: [
                        {
                            loader: 'style-loader' // creates style nodes from JS strings
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
                    test: /\.css$/, // normal css loader
                    use: ['style-loader', 'css-loader']
                }
            ]
        },
        resolve: {
            modules: [path.resolve(__dirname, './node_modules')], // necessary to resolve npm packages
            extensions: ['.ts', '.js', '.vue', '.json', '.less'], // necessary to build files with these extensions
            alias: {
                vue$: 'vue/dist/vue.esm.js', // necessary to work rith vue.js properly
                'load-image': 'blueimp-load-image/js/load-image.js', // necessary to load jquery file upload properly
                'load-image-meta': 'blueimp-load-image/js/load-image-meta.js', // necessary to load jquery file upload properly
                'load-image-exif': 'blueimp-load-image/js/load-image-exif.js', // necessary to load jquery file upload properly
                'load-image-scale': 'blueimp-load-image/js/load-image-scale.js', // necessary to load jquery file upload properly
                'canvas-to-blob': 'blueimp-canvas-to-blob/js/canvas-to-blob.js', // necessary to load jquery file upload properly
                'jquery-ui/ui/widget':
                    'blueimp-file-upload/js/vendor/jquery.ui.widget.js', // necessary to load jquery file upload properly
                '@': path.resolve(__dirname, '../../'), // define root directory as @ : makes typecript imports easier / avoid long relative path input
                core: path.resolve(__dirname, '../'), // define core directory as core : makes typecript imports easier / avoid long relative path input
                core_agp: path.resolve(__dirname, '../../core_agp') // define core_agp directory as core_agp : makes typecript imports easier / avoid long relative path input
                // !important all the aliases definitions needs to be defined in the tsconfig.json as well
            }
        },
        plugins: devMode // if devMode use these plugins
            ? [
                new Dotenv({
                    // adds support for .env files
                    path: devMode
                        ? path.resolve(__dirname, '.env.dev')
                        : path.resolve(__dirname, '.env.prod')
                }),
                new webpack.ProvidePlugin({
                    // Automatically load modules instead of having to import or require them everywhere. needed for alot of jquery based modules
                    jQuery: 'jquery',
                    $: 'jquery',
                    jquery: 'jquery'
                }),
                new LiveReloadPlugin(liveReloadOptions), // adds page reload on save support
                new WatchMissingNodeModulesPlugin( // This Webpack plugin ensures npm install <library> forces a project rebuild.
                    path.resolve('node_modules')
                ),
                new VueLoaderPlugin() // adds support for the vue DevTools plugin (chrome)
            ] // else use these plugins
            : [
                new Dotenv({
                    // adds support for .env files
                    path: devMode
                        ? path.resolve(__dirname, '.env.dev')
                        : path.resolve(__dirname, '.env.prod')
                }),
                new webpack.ProvidePlugin({
                    // Automatically load modules instead of having to import or require them everywhere. needed for alot of jquery based modules
                    jQuery: 'jquery',
                    $: 'jquery',
                    jquery: 'jquery'
                }),
                new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/), // ignores not used languages for moment.js and fullcalendar.js for a smaller bundle size
                new VueLoaderPlugin() // adds support for the vue DevTools plugin (chrome)
            ],
        optimization: {
            minimize: !devMode, // minimize bundle size only in prod
            minimizer: !devMode
                ? [
                    new TerserPlugin({
                        // This plugin uses terser to minify the bundle
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
                chunks: 'all', // make a separate vendors.chunks bundle for all the npm modules (avoides duplicated dependecies + smaller bundle size)
                name: 'vendors.chunks'
            }
        }
    }
}
