#!/usr/bin/env bash
#
# build from clean checkout
#
set -euo pipefail
IFS=$'\n\t'

name="$(awk '/<project name="([^"]*)"/ && !done {print gensub(/<project name="([^"]*)".*/, "\\1", "g"); done=1}' build.xml
)"
phar="${name}.phar"

echo "Building ${phar}..."

echo "$0 executed in $(pwd -P)"

build_dir="build/output"

if [ -d "${build_dir}" ]; then
    rm -rf "${build_dir}"
fi
if [ -d "${build_dir}" ]; then
    echo "Can not remove build-dir '${build_dir}'"
fi
mkdir "${build_dir}"
if [ ! -d "${build_dir}" ]; then
    echo "Can not create build-dir '${build_dir}'"
fi

git clone -l -- . "${build_dir}"

if [ ! -e "composer.phar" ]; then
    echo "Downloading composer.phar..."
    wget http://getcomposer.org/composer.phar
    chmod +x composer.phar
fi

./composer.phar --version

./composer.phar -d="${build_dir}" -q --profile install --no-dev --no-interaction

./composer.phar -d="${build_dir}"/build -q --profile install --no-interaction

if [ -e "${phar}" ]; then
    echo "Remove earlier created ${phar} file"
    rm "${phar}"
fi

cd "${build_dir}"

echo "building in $(pwd -P)"

git fetch origin

git reset --hard "$(git rev-parse --abbrev-ref --symbolic-full-name @{u})"

ulimit -Sn $(ulimit -Hn)

set +e
php -f build/vendor/phing/phing/bin/phing -dphar.readonly=0 -- -verbose dist
BUILD_STATUS=$?
set -e
if [ ${BUILD_STATUS} -ne 0 ]; then
    >&2 echo "error: phing build failed with exit status ${BUILD_STATUS}"
    exit ${BUILD_STATUS}
fi

php -f build/phar/phar-timestamp.php

php -f "${phar}" -- --version

ls -l "${phar}"

php -r 'echo "SHA1: ", sha1_file("'"${phar}"'"), "\nMD5.: ", md5_file("'"${phar}"'"), "\n";'

cd -

cp -vip "${build_dir}"/"${phar}" "${phar}"

rm -rf "${build_dir}"

echo "done."
