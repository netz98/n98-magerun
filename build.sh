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

php -f build/vendor/phing/phing/bin/phing -dphar.readonly=0 -- -verbose dist

php -f "n98-magerun.phar" -- --version

ls -go --full-time n98-magerun.phar

php -r 'echo "SHA1: ", sha1_file("n98-magerun.phar"), "\nMD5.: ", md5_file("n98-magerun.phar"), "\n";'
