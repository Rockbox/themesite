#!/bin/sh
rockbox_dir="/home/themes/rockbox-current"
export ANDROID_SDK_PATH=""
export ANDROID_NDK_PATH=""

cd `dirname "$0"`
target=`pwd`
cd "${rockbox_dir}"
git pull --rebase
git checkout master
./tools/version.sh . > "${target}/VERSION"
cd "tools/checkwps"
./cleanall.sh
./buildall.sh
cp output/checkwps.* "${target}/"
cd "${target}"
rm -f checkwps.c checkwps.h
