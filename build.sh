#!/usr/bin/env bash
#
# build from clean checkout
#

if [ ! -d "vendor" ]; then
    composer install
fi

if [ ! -d "build/vendor" ]; then
    composer -d=build install
fi

build/vendor/bin/phing -verbose dist

php -f "n98-magerun.phar" -- --version
