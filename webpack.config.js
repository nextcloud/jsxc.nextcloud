/* jshint node:true */
const path = require("path");
const webpack = require("webpack");
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const CleanWebpackPlugin = require('clean-webpack-plugin');

const DESTINATION_DIR = 'dist/';

const fileLoader = {
   loader: 'file-loader',
   options: {
      name: '[path][name]-[sha1:hash:hex:8].[ext]',
      outputPath: 'assets/'
   }
};

module.exports = {
   entry: ['./scss/index.scss', './ts/index.ts'],
   output: {
      filename: 'js/bundle.js',
      path: path.resolve(__dirname, DESTINATION_DIR),
      publicPath: DESTINATION_DIR,
   },
   optimization: {
      splitChunks: {
         minSize: 10,
         cacheGroups: {
            styles: {
               name: 'styles',
               test: /\.css$/,
               chunks: 'all',
               enforce: true
            }
         }
      },
   },
   node: {
      fs: 'empty'
   },
   module: {
      rules: [{
            test: /\.ts$/,
            loader: 'ts-loader',
            exclude: /node_modules/,
         },
         {
            test: /\.hbs$/,
            loader: 'handlebars-loader',
            exclude: /node_modules/,
            options: {
               helperDirs: [
                  path.resolve(__dirname, 'template', 'helpers')
               ],
               partialDirs: [
                  path.resolve(__dirname, 'template', 'partials')
               ]
            }
         },
         {
            test: /\.css$/,
            use: [
               MiniCssExtractPlugin.loader,
               'css-loader?importLoaders=1',
            ],
         },
         {
            test: /\.(sass|scss)$/,
            use: [
               MiniCssExtractPlugin.loader, {
                  loader: 'css-loader',
                  options: {
                     url: false
                  }
               },
               'sass-loader'
            ]
         },
         {
            test: /.*\.(svg|png|jpg|gif|mp3|wav)$/,
            use: [fileLoader]
         },
         {
            test: /.*\.(js)$/,
            resourceQuery: /path/,
            use: [fileLoader]
         }
      ]
   },
   resolve: {
      extensions: [".ts", ".js", ".hbs"],
      alias: {}
   },
   externals: {
      'jquery': 'jQuery',
   },
   plugins: [
      new MiniCssExtractPlugin({
         filename: 'css/bundle.css',
      }),
      new CleanWebpackPlugin([DESTINATION_DIR]),
      new CopyWebpackPlugin([{
         from: 'node_modules/jsxc/dist/',
         to: 'js/jsxc/'
      }, {
         from: 'node_modules/libsignal-protocol/dist/',
         to: 'js/libsignal/'
      }, {
         from: 'appinfo/',
         to: 'appinfo/'
      }, {
            from: 'img/',
            to: 'img/'
      }, {
            from: 'templates/',
            to: 'templates/'
      }, {
            from: 'lib/',
            to: 'lib/'
      }, {
            from: 'settings/',
            to: 'settings/'
      }, 'LICENSE']),
      new webpack.LoaderOptionsPlugin({
         options: {
            handlebarsLoader: {}
         }
      }),
      // new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, new RegExp(MOMENTJS_LOCALES.join('|'))),
      // new BundleAnalyzerPlugin(),
   ]
};
