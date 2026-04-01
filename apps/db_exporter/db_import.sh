#!/usr/bin/env bash

CURPATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

source "$CURPATH/shared.sh"

for d in "$SQL_PATH/*/"" ; do
    echo "Importing $d"
    find "$SQL_PATH/$d/" -name '*.sql' | awk '{ print "source",$0 }' | mysql --batch -u "$MYSQL_USER" -p "$MYSQL_PASS" "$d" && echo "done"
done

