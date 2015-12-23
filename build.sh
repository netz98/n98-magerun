#!/usr/bin/env bash
#
# build from clean checkout
#

name="$(awk '/<project name="([^"]*)"/ && !done {print gensub(/<project name="([^"]*)".*/, "\\1", "g"); done=1}' build.xml
)"
phar="${name}.phar"

echo "building ${phar}..."

if [ ! -e "composer.phar" ]; then
    wget http://getcomposer.org/composer.phar
    chmod +x composer.phar
fi

if [ ! -d "vendor" ]; then
    ./composer.phar install
fi

if [ ! -d "build/vendor" ]; then
    ./composer.phar -d=build install
fi

ulimit -Sn $(ulimit -Hn)
php -f build/vendor/phing/phing/bin/phing -dphar.readonly=0 -- -verbose dist
BUILD_STATUS=$?

if [ ${BUILD_STATUS} -ne 0 ]; then
    >&2 echo "error: phing build failed with exit status ${BUILD_STATUS}"
    exit ${BUILD_STATUS}
fi

php -f build/phar/phar-timestamp.php

php -f "${phar}" -- --version

ls -l "${phar}"

php -r 'echo "SHA1: ", sha1_file("'"${phar}"'"), "\nMD5.: ", md5_file("'"${phar}"'"), "\n";'

