#!/bin/sh
rockbox_dir="$HOME/rockbox_git_clone"
unset ANDROID_SDK_PATH
unset ANDROID_NDK_PATH

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
