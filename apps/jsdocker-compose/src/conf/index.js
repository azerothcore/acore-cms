const fs = require("fs");
const path = require("path");
var conf = require("./dist/conf");

const confPath = path.join(__dirname, "conf.js");
if (fs.existsSync()) {
    conf = require(confPath);
}

module.exports = conf;