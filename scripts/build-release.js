require('colors').setTheme({
  verbose: 'cyan',
  warn: 'yellow',
  error: 'red',
});

const fs = require("fs");
const path = require("path");
const libxml = require("libxmljs");
const https = require('https');
const GitRevisionPlugin = new(require('git-revision-webpack-plugin'))();
const package = require('../package.json');

const infoXmlPath = './appinfo/info.xml';
const isStableRelease = process.argv.indexOf('--stable') > 1;
const version = isStableRelease ? package.version : package.version.replace(/-.+$/, '') + '-git.' + GitRevisionPlugin.version();

run();

async function run() {
  await prepareInfoXml();
  await createRelease();
}

async function prepareInfoXml() {
  const infoFile = fs.readFileSync(infoXmlPath);
  const xmlDoc = libxml.parseXml(infoFile);

  updateVersion(xmlDoc, version);
  await validateXml(xmlDoc);
}

async function createRelease() {
  console.log(`I'm now building JSXC for Nextcloud in version ${version}.`.verbose);

  await createBuild();
  let filePath = await createArchive(version);
  await createSignature(filePath);
}

function createBuild() {
  const webpackArgs = {
    mode: 'production',
  };
  const webpackConfig = require('../webpack.config.js')(undefined, webpackArgs);
  const compiler = require('webpack')(webpackConfig);

  return new Promise(resolve => {
    compiler.run((err, stats) => {
      if (err) {
        console.error(err);
        return;
      }

      console.log(stats.toString('minimal'));

      resolve();
    });
  });
}

function createArchive(fileBaseName) {
  let fileName = `${fileBaseName}.tar.gz`;
  let filePath = path.normalize(__dirname + `/../archives/${fileName}`);
  let output = fs.createWriteStream(filePath);
  let archive = require('archiver')('tar', {
     gzip: true,
  });

  archive.on('warning', function(err) {
     if (err.code === 'ENOENT') {
        console.warn('Archive warning: '.warn, err);
     } else {
        throw err;
     }
  });

  archive.on('error', function(err) {
     throw err;
  });

  archive.pipe(output);

  archive.directory('dist/', 'ojsxc');

  return new Promise(resolve => {
     output.on('close', function() {
        console.log(`Wrote ${archive.pointer()} bytes to ${fileName}`.verbose);

        resolve(filePath);
     });

     archive.finalize();
  });
}

function createSignature(filePath) {
  const {
     exec
  } = require('child_process');

  return new Promise((resolve, reject) => {
    const sigPath = `${filePath}.sig`;
     exec(`openssl dgst -sha512 -sign ~/.nextcloud/certificates/ojsxc.key ${filePath} | openssl base64 > ${sigPath}`, (error, stdout, stderr) => {
        if (error) {
           throw error;
        }

        if (stdout) {
           console.log(`stdout: ${stdout}`);
        }

        if (stderr) {
           console.log(`stderr: ${stderr}`);
        }

        console.log(`Created signature: ${path.basename(sigPath)}`.verbose);

        resolve();
     });
  });
}

function updateVersion(xmlDoc, version) {
  let versionChild = xmlDoc.get('//version');
  let currentVersion = versionChild.text();

  if (version !== currentVersion) {
    console.log(`Update version in info.xml to ${version}.`.verbose);

    versionChild.text(version);

    fs.writeFileSync(infoXmlPath, xmlDoc.toString());
  }
}

async function validateXml(xmlDoc) {
  const schemaLocation = xmlDoc.root().attr('noNamespaceSchemaLocation').value();

  if (!schemaLocation) {
    throw "Found no schema location";
  }

  let schemaString = await wget(schemaLocation);
  let xsdDoc = libxml.parseXml(schemaString);

  if (xmlDoc.validate(xsdDoc)) {
    console.log('✔ Document valid'.green);
  } else {
    console.log('✘ Document INVALID'.error);

    xmlDoc.validationErrors.forEach((error, index) => {
      console.log(`#${index+1}\t${error.toString().trim()}`.warn);
      console.log(`\tLine ${error.line}:${error.column} (level ${error.level})`.verbose);
    });

    throw 'Abort';
  }
}

function wget(url) {
  return new Promise((resolve, reject) => {
    https.get(url, (resp) => {
      let data = '';

      resp.on('data', (chunk) => {
        data += chunk;
      });

      resp.on('end', () => {
        resolve(data);
      });

    }).on("error", (err) => {
      reject(err);
    });
  })
}
