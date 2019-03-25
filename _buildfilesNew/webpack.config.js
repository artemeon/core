const path = require('path')
const webpack = require('webpack')
const Dotenv = require('dotenv-webpack')
const glob = require('glob')
var LiveReloadPlugin = require('webpack-livereload-plugin')
const tsPaths = glob.sync('../../{core,core_agp}/module_*/scripts', {
  realpath: true
})

const liveReloadOptions = {
  hostname: 'localhost',
  protocol: 'http'
  // ignore: '*.less'
}
const devMode = process.env.NODE_ENV !== 'production'

module.exports = {
  entry: {
    agp: glob.sync('../../{core,core_agp}/module_*/scripts/*/*.ts')
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
            sass: 'vue-style-loader!css-loader!sass-loader?indentedSyntax'
          }
        }
      },
      {
        test: /\.tsx?$/,
        // include: tsPaths,
        loader: 'ts-loader',
        exclude: /node_modules/,
        options: {
          appendTsSuffixTo: [/\.vue$/]
        }
      },
      {
        test: /\.scss$/,
        use: ['style-loader', 'css-loader', 'sass-loader']
      },
      {
        test: /\.less$/,
        use: [
          // devMode ? 'style-loader' : MiniCssExtractPlugin.loader,
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
      'jquery-ui/ui/widget': 'blueimp-file-upload/js/vendor/jquery.ui.widget.js'
    }
  },
  plugins: [
    new Dotenv({
      path: path.resolve(__dirname, '.env.dev')
    }),
    new webpack.ProvidePlugin({
      jQuery: 'jquery',
      $: 'jquery',
      jquery: 'jquery'
    }),
    new LiveReloadPlugin(liveReloadOptions)
  ]
}
