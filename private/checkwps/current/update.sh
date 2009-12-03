#!/bin/sh
rockbox_dir="/home/themes/rockbox-current"

cd `dirname "$0"`
target=`pwd`
cd "${rockbox_dir}"
svn up
./tools/version.sh . > "${target}/VERSION"
cd "tools/checkwps"
./cleanall.sh
./buildall.sh
cp output/checkwps.* "${target}/"
cd "${target}"
rm -f checkwps.c checkwps.h
