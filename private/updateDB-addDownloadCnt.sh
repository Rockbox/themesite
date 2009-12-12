#!/bin/sh

#backup db
cp themes.db themes.db.downloadcnt.bak &&
# dump table
echo ".dump themes" | sqlite themes.db > themes.tbl &&
#update table def
sed "/CREATE TABLE/s/);/, downloadcnt);/g" themes.tbl > themes-tmp.tbl &&
#update values
sed "/INSERT INTO/s/);/, 0);/g" themes-tmp.tbl > themes.tbl &&
#drop table
echo "DROP TABLE themes;" | sqlite themes.db &&
#create new table and import
echo ".read themes.tbl" | sqlite themes.db
