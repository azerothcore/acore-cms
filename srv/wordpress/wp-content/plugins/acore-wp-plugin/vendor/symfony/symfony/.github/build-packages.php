<?php

if (3 > $_SERVER['argc']) {
    echo "Usage: branch version dir1 dir2 ... dirN\n";
    exit(1);
}
chdir(dirname(__DIR__));

$json = ltrim(file_get_contents('composer.json'));
if ($json !== $package = preg_replace('/\n    "repositories": \[\n.*?\n    \],/s', '', $json)) {
    file_put_contents('composer.json', $package);
}

$dirs = $_SERVER['argv'];
array_shift($dirs);
$mergeBase = trim(shell_exec(sprintf('git merge-base "%s" HEAD', array_shift($dirs))));
$version = array_shift($dirs);

$packages = array();
$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
$preferredInstall = json_decode(file_get_contents(__DIR__.'/composer-config.json'), true)['config']['preferred-install'];

foreach ($dirs as $k => $dir) {
    if (!system("git diff --name-only $mergeBase -- $dir", $exitStatus)) {
        if ($exitStatus) {
            exit($exitStatus);
        }
        unset($dirs[$k]);
        continue;
    }
    echo "$dir\n";

    $json = ltrim(file_get_contents($dir.'/composer.json'));
    if (null === $package = json_decode($json)) {
        passthru("composer validate $dir/composer.json");
        exit(1);
    }

    $package->repositories = array(array(
        'type' => 'composer',
        'url' => 'file://'.str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__)).'/',
    ));
    if (false === strpos($json, "\n    \"repositories\": [\n")) {
        $json = rtrim(json_encode(array('repositories' => $package->repositories), $flags), "\n}").','.substr($json, 1);
        file_put_contents($dir.'/composer.json', $json);
    }

    if (isset($preferredInstall[$package->name]) && 'source' === $preferredInstall[$package->name]) {
        passthru("cd $dir && tar -cf package.tar --exclude='package.tar' *");
    } else {
        passthru("cd $dir && git init && git add . && git commit -q -m - && git archive -o package.tar HEAD && rm .git/ -Rf");
    }

    $package->version = (isset($package->extra->{'branch-version'}) ? $package->extra->{'branch-version'} : $version).'.x-dev';
    $package->dist['type'] = 'tar';
    $package->dist['url'] = 'file://'.str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__))."/$dir/package.tar";

    $packages[$package->name][$package->version] = $package;

    $versions = @file_get_contents('https://repo.packagist.org/p/'.$package->name.'.json') ?: sprintf('{"packages":{"%s":{"%s":%s}}}', $package->name, $package->version, file_get_contents($dir.'/composer.json'));
    $versions = json_decode($versions)->packages->{$package->name};

    foreach ($versions as $v => $package) {
        $packages[$package->name] += array($v => $package);
    }
}

file_put_contents('packages.json', json_encode(compact('packages'), $flags));

if ($dirs) {
    $json = ltrim(file_get_contents('composer.json'));
    if (null === $package = json_decode($json)) {
        passthru("composer validate $dir/composer.json");
        exit(1);
    }

    $package->repositories = array(array(
        'type' => 'composer',
        'url' => 'file://'.str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__)).'/',
    ));
    $json = rtrim(json_encode(array('repositories' => $package->repositories), $flags), "\n}").','.substr($json, 1);
    file_put_contents('composer.json', $json);
}
