#!/usr/bin/env bash

ROOTPATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../../" && pwd )"

maxcounter=45

source "$ROOTPATH/conf/dist/conf.sh"

[[ -f "$ROOTPATH/conf/conf.sh" ]] && source "$ROOTPATH/conf/conf.sh"

counter=1
while ! mysql --protocol TCP -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "show databases;" > /dev/null 2>&1; do
    sleep 1
    counter=`expr $counter + 1`
    if [ $counter -gt $maxcounter ]; then
        >&2 echo "We have been waiting for MySQL too long already; failing."
        exit 1
    fi;
done