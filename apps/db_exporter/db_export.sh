#!/usr/bin/env bash

CURPATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "===== STARTING PROCESS ====="

source "$CURPATH/shared.sh"

CONF="MYSQL_USER=$MYSQL_USER;\
MYSQL_PASS=$MYSQL_PASS;\
CLEANFOLDER=1; \
CHMODE=0; \
TEXTDUMPS=0; \
PARSEDUMP=1; \
FULL=0; \
DUMPOPTS='--no-tablespaces --skip-comments --skip-set-charset --routines --extended-insert --order-by-primary --single-transaction --quick';"


function export() {
    echo "Working on: "$1
    database=$1

    base_path="$SQL_PATH/$database"
    
    base_conf="TPATH="$base_path";\
               $CONF"

    bash "$ROOTPATH/apps/azerothcore/mysql-tools/mysql-tools" dump "" "$database" "" "$base_conf"
}

DBS="$(mysql -u$MYSQL_USER -p$MYSQL_PASS -Bse 'show databases')"

for db in ${DBS[@]}
do
    if [ "$db" != "information_schema" ]; then
        export "$db"
    fi
done

echo "===== DONE ====="
