#!/usr/bin/env bash
#
# build from clean checkout
#

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

php -f "n98-magerun.phar" -- --version

ls -go --full-time n98-magerun.phar

php -r 'echo "SHA1: ", sha1_file("n98-magerun.phar"), "\nMD5.: ", md5_file("n98-magerun.phar"), "\n";'

if [ ${BUILD_STATUS} -ne 0 ]; then
    >&2 echo "error: phing build failed with exit status ${BUILD_STATUS}"
    exit ${BUILD_STATUS}
fi
