#!/bin/sh
rockbox_dir="$HOME/rockbox_git_clone"

export ANDROID_NDK_PATH="/home/rbbuild/x-tools/android-ndk-r10e"

cd `dirname "$0"`
target=`pwd`
cd "${rockbox_dir}"
#git pull --rebase
#git checkout master
./tools/version.sh . > "${target}/VERSION"
cd "tools/checkwps"
./cleanall.sh
./buildall.sh --jobs=`nproc` >/dev/null
cp output/checkwps.* "${target}/"
cp checkwps.failures "${target}/"
cd "${target}"
rm -f checkwps.c checkwps.h
