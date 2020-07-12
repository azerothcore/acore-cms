const dotenv = require('dotenv');
dotenv.config({ path: '.env.local' });
dotenv.config({ path: '.env' });
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const conf = require("./conf");

// workaround to the missing of a proper command to check it
function checkToBuilt() {
    try {
        execSync('docker-compose up --no-build --no-start', {
            stdio: 'pipe',
            cwd: conf.docker_base_dir,
        })
    } catch (e) {
        if (e.stderr.toString().includes('needs to be built')) return true;
    }

    return false;
}

function checkIsRunning(serviceName) {
    let ids, id;

    ids = execSync('docker ps -q --no-trunc').toString();

    id = execSync(`docker-compose ps -q ${serviceName}`).toString();

    return ids.indexOf(id) >= 0;
}

function createPackageHash() {
    // this file will be bound by docker-compose in root folder
    // allowing install-changed to work properly
    fs.writeFile('var/packagehash.txt', '', { flag: 'wx' }, function (/*err*/) {
        //if (err) throw err;
    });
}

function buildServicesCmd() {
    if (conf.docker_inject_sshkey) {
        const homedir = require('os').homedir();
        let idrsa = fs
            .readFileSync(path.resolve(`${homedir}/.ssh/id_rsa`))
            .toString();
        idrsa = idrsa.replace(/\r\n/g, '\n');
        idrsa = idrsa.replace(/\n/g, '\\n');
        let idrsapub = fs
            .readFileSync(path.resolve(`${homedir}/.ssh/id_rsa.pub`))
            .toString();
        idrsapub = idrsapub.replace(/\r\n/g, '\n');
        idrsapub = idrsapub.replace(/\n/g, '\\n');
        return `docker-compose build --force-rm --no-cache --build-arg idrsa="${idrsa}" --build-arg idrsapub="${idrsapub}"`;
    } else {
        return `docker-compose build --force-rm --no-cache`
    }
}

/**
 * @param {string} command - Command to execute: fg|bg|shell|remove|stop|build. Leave it blank to execute an arbitrary npm run command
 * @param {Array} scArgs - arguments for runCmd
 * @param {object} options - configuration object
 * @param {string} options.service - name of service where execute commands
 * @param {boolean} options.hasDb - if true, enable some db checks
 * @param {string} options.dbService - name of database service
 * @param {string} options.runCmd - prefix of command to run fg/bg parameters
 * @param {boolean} options.remove - run a docker-compose down command after command graceful exit
 * @param {boolean} options.stop - run a docker-compose stop after command graceful exit
 */
async function main(
    command,
    scArgs,
    {
        service = conf.docker_service_name,
        hasDb = conf.docker_has_db,
        dbService = conf.docker_db_service_name,
        runCmd = conf.docker_run_command,
        remove = false,
        stop = false,
    },
) {
    var scArgsStr = scArgs.join(' ');

    var upArgs = ' -f docker-compose.yml ',
        exitArgs = 'true';

    upArgs += conf.docker_extra_args;

    if (remove) {
        exitArgs = ' docker-compose down';
    } else if (stop) {
        exitArgs = ' docker-compose stop';
    }

    const dockerUp = `docker-compose ${upArgs} up -d --no-build --remove-orphans ${
        hasDb
            ? ` && docker-compose exec ${dbService} ${path.join(conf.docker_jsdc_dir, 'waitForMySQL.sh')}`
            : ''
        }`;
    var cmd = '';

    var toBuild = false;

    switch (command) {
        case 'fg':
            toBuild = checkToBuilt();
            conf.docker_packagehash_support && createPackageHash();
            if (scArgsStr.length > 0) {
                cmd = `${dockerUp} && docker-compose exec ${service} ${runCmd} ${scArgsStr} || ${exitArgs}`;
            } else {
                cmd = `docker-compose ${upArgs} up --no-build --remove-orphans || ${exitArgs}`;
            }

            break;
        case 'bg':
            toBuild = checkToBuilt();
            conf.docker_packagehash_support && createPackageHash();
            if (scArgsStr.length > 0) {
                cmd = `${dockerUp} && docker-compose exec -d ${service} ${runCmd} ${scArgsStr} || ${exitArgs}`;
            } else {
                cmd = `${dockerUp} || ${exitArgs}`;
            }
            break;
        case 'shell': {
            conf.docker_packagehash_support && createPackageHash();
            let isRunning = checkIsRunning(service);

            if (!isRunning) toBuild = checkToBuilt();

            cmd = `${
                !isRunning ? dockerUp + ' && ' : ''
                } docker-compose exec ${service} bash || ${exitArgs}`;
            break;
        }
        case 'remove':
            cmd = 'docker-compose down -v --remove-orphans --rmi local';
            fs.unlinkSync('var/packagehash.txt');
            break;
        case 'stop':
            cmd = 'docker-compose stop';
            break;
        case 'build': {
            cmd = buildServicesCmd();
            break;
        }
        default:
            cmd = 'npm run ' + command + ' ' + scArgsStr;
            break;
    }

    if (toBuild) {
        console.log('Building...');
        execSync(buildServicesCmd(), { stdio: 'inherit', cwd: conf.docker_base_dir });
    }

    console.log('Running: ' + cmd);

    execSync(cmd, { stdio: 'inherit', cwd: conf.docker_base_dir });
}

module.exports = main;
