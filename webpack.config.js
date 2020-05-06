/* jshint node:true */
const path = require("path");
const webpack = require("webpack");
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const DESTINATION_DIR = '.';

const fileLoader = {
   loader: 'file-loader',
   options: {
      name: '[path][name]-[sha1:hash:hex:8].[ext]',
      outputPath: 'assets/'
   }
};

const config = {
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
   performance: {
      maxEntrypointSize: 1024 * 1000 * 1000 * 3,
      maxAssetSize: 1024 * 1000 * 1000 * 3,
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
      new webpack.LoaderOptionsPlugin({
         options: {
            handlebarsLoader: {}
         }
      }),
      // new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, new RegExp(MOMENTJS_LOCALES.join('|'))),
      // new BundleAnalyzerPlugin(),
   ]
};

module.exports = (env, argv) => {

   if (typeof argv.mode === 'string') {
      config.mode = argv.mode;
   }

   return config;
};
