const commander = require('commander');
const main = require('./main');
const conf = require('./conf')

function addDefaultOptions(command) {
    command
        .option('-s, --service <name>', 'Name of main service', conf.docker_service_name)
    return command;
}

/**
 *
 * @param {commander.Command} command - Commander instance
 * @param {object} [defaults] - Default values for options
 */
function addRunOptions(
    command,
    { stop = false, remove = false } = {},
) {
    command
        .option(
            '-t, --stop',
            'Stop docker container after exit, cannot be used with --rm',
            stop,
        )
        .option(
            '-r, --rm',
            'Remove docker container after exit, cannot be used with --stop',
            remove,
        )
        .option(
            '-c, --run-cmd <cmd>',
            'Command to use for direct run on docker instances',
            'npm run',
        );
}

commander
    .name('jsdocker-compose')
    .description('Command to manage docker instances');

const runFg = commander.command('run-foreground');

runFg
    .alias('run:fg')
    .description('Run docker services in foreground')
    .arguments('[cmdArgs...]')
    .action((cmdArgs, options) => {
        main('fg', cmdArgs, options);
        process.exit(0);
    });

addDefaultOptions(runFg);
addRunOptions(runFg);

const runBg = commander.command('run-background');

runBg
    .alias('run:bg')
    .description('Run docker services in background')
    .arguments('[cmdArgs...]')
    .action((cmdArgs, options) => {
        main('bg', cmdArgs, options);
        process.exit(0);
    });

addDefaultOptions(runBg);
addRunOptions(runBg);

const runShell = commander.command('run-shell');

runShell
    .alias('run:shell')
    .description('Run docker services with a shell')
    .action(options => {
        main('shell', [], options);
        process.exit(0);
    });

addDefaultOptions(runShell);
addRunOptions(runShell);

const stop = commander.command('stop');

stop.description('Stop docker services').action(options => {
    main('stop', [], options);
    process.exit(0);
});

addDefaultOptions(stop);

const build = commander.command('build');

build.description('Build docker image').action(options => {
    main('build', [], options);
    process.exit(0);
});

addDefaultOptions(build);

const remove = commander.command('remove');

remove
    .alias('rm')
    .description('Remove docker services')
    .action(options => {
        main('remove', [], options);
        process.exit(0);
    });

addDefaultOptions(remove);

commander.on('command:*', function () {
    console.error(
        'Invalid command: %s\nSee --help for a list of available commands.',
        commander.args.join(' '),
    );
    process.exit(1);
});

commander.parse(process.argv);

// normally the process exits before
commander.help();
