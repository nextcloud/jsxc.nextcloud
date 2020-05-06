require('colors').setTheme({
  verbose: 'cyan',
  warn: 'yellow',
  error: 'red',
});

const fs = require("fs");
const path = require("path");
const libxml = require("libxmljs");
const https = require('https');
const execa = require('execa');
const GitRevisionPlugin = new (require('git-revision-webpack-plugin'))();
const package = require('../package.json');

const infoXmlPath = './appinfo/info.xml';
const isStableRelease = process.argv.indexOf('--stable') > 1;
const version = isStableRelease ? package.version : package.version.replace(/-.+$/, '') + '-git.' + GitRevisionPlugin.version();

createRelease().catch(err => {
  console.log(`✘ ${err.toString()}`.error);
});

async function createRelease() {
  console.log(`I'm now building JSXC for Nextcloud in version ${version}.`.verbose);

  await execa('yarn', ['checking']);
  console.log('✔ all code checks passed'.green);

  await execa('yarn', ['test']);
  console.log('✔ all tests passed'.green);

  await prepareInfoXml();

  await createBuild();
  console.log('✔ build created'.green);

  let filePath = await createArchive('ojsxc-' + version);
  console.log(`✔ wrote archive`.green);

  await createNextcloudSignature(filePath);
  console.log(`✔ created Nextcloud signature`.green);

  await createGPGSignature(filePath);
  console.log(`✔ created detached signature`.green);

  await createGPGArmorSignature(filePath);
  console.log(`✔ created detached signature`.green);
}

async function prepareInfoXml() {
  const infoFile = fs.readFileSync(infoXmlPath);
  const xmlDoc = libxml.parseXml(infoFile);

  updateVersion(xmlDoc, version);
  console.log('✔ version updated in info.xml'.green);

  await validateXml(xmlDoc);
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

async function createArchive(fileBaseName) {
  let fileName = `${fileBaseName}.tar.gz`;
  let filePath = path.normalize(__dirname + `/../archives/${fileName}`);
  let files = ['appinfo/', 'css/', 'img/', 'js/', 'lib/', 'templates/', 'LICENSE'];

  await execa('tar', ['-czhf', filePath, `--transform=s,^,ojsxc/,`, ...files]);

  return filePath;
}

function createNextcloudSignature(filePath) {
  const sigPath = `${filePath}.ncsig`;
  const keyFile = path.join(process.env.HOME, '.nextcloud/certificates/ojsxc.key');

  const signProcess = execa('openssl', ['dgst', '-sha512', '-sign', keyFile, filePath]);
  const base64Process = execa('openssl', ['base64']);

  signProcess.stdout.pipe(base64Process.stdin);
  base64Process.stdout.pipe(fs.createWriteStream(sigPath));

  return base64Process;
}

function createGPGSignature(filePath) {
  return execa('gpg', ['--yes', '--detach-sign', filePath]);
}

function createGPGArmorSignature(filePath) {
  return execa('gpg', ['--yes', '--detach-sign', '--armor', filePath]);
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


  let schemaString;
  try {
    schemaString = await wget(schemaLocation);
  } catch (err) {
    console.log('Could not download schema. Skip validation.'.warn);

    return;
  }
  let xsdDoc = libxml.parseXml(schemaString);

  if (xmlDoc.validate(xsdDoc)) {
    console.log('✔ document valid'.green);
  } else {
    console.log('✘ document INVALID'.error);

    xmlDoc.validationErrors.forEach((error, index) => {
      console.log(`#${index + 1}\t${error.toString().trim()}`.warn);
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
