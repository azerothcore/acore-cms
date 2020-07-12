const path = require('path');

module.exports = {
  entry: './src/cli.js',
  target: 'node',
  mode: 'production',
  output: {
    filename: 'main.js',
    path: path.resolve(__dirname, 'dist'),
  },
  node: {
    global: false,
    __filename: false,
    __dirname: false,
  }
};