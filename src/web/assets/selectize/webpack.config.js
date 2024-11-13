/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {},
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'selectize.js': [
            require.resolve('@selectize/selectize/dist/js/selectize.js'),
            require.resolve('selectize-plugin-a11y/selectize-plugin-a11y.js'),
          ],
        },
      }),
      new CopyWebpackPlugin({
        patterns: [
          {
            from: require.resolve(
              '@selectize/selectize/dist/css/selectize.css'
            ),
            to: './css/selectize.css',
          },
        ],
      }),
    ],
  },
});
