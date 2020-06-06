#!/usr/bin/env bash

set -e

ROOT_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/"

git submodule foreach git pull origin master

source "$ROOT_PATH/apps/git-utils/subrepo.sh"

echo "> Pulling and update all subrepos"

subrepoUpdate https://github.com/azerothcoore/git-utils  master apps/git-utils

subrepoUpdate https://github.com/azerothcore/acore-php-framework master vendor/acore-php/acore-php