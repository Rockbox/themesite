#!/bin/sh
VERSION="4.0"
rockbox_dir="$ROCKBOX_GIT_DIR"

unset ANDROID_SDK_PATH
unset ANDROID_NDK_PATH

# Don't rebuild if VERSION hasn't changed
VER2=`cat VERSION`
if [ "$VER" == "$VER2" ]; then
    exit 0
fi

cd `dirname "$0"`
target=`pwd`
cd "${rockbox_dir}"
git pull --rebase
git checkout "v$VERSION-final"
./tools/version.sh . > "${target}/VERSION"
cd "tools/checkwps"
./cleanall.sh
./buildall.sh
cp output/checkwps.* "${target}/"
cd "${target}"
rm -f checkwps.c checkwps.h
echo $VERSION > VERSION
cd "${rockbox_dir}"
git checkout master
