#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

. build/circleci/source.sh

# download and install mangento (by the git cloned magerun version itself)
magerun_install()
{
    local version="${1}"
    local space="."
    local dir="${space}/${version}"
    local data="${2:-no}"


    # mysql -uroot -e 'CREATE DATABASE IF NOT EXISTS `magento_travis`;'

    php -dmemory_limit=1g -f bin/n98-magerun -- install \
            --magentoVersionByName="${version}" --installationFolder="${dir}" \
            --dbHost=127.0.0.1 --dbUser=root --dbPass="" --dbName="magento_travis" \
            --installSampleData=${data} --useDefaultConfigParams=yes \
            --baseUrl="http://travis.magento.local/"
}

# enable xdebug
sed -i 's/^;//' ~/.phpenv/versions/$(phpenv global)/etc/conf.d/xdebug.ini

# php.ini (memory limit)
cp build/circleci/php.ini ~/.phpenv/versions/$(phpenv global)/etc/conf.d/

# warumup composer dist packages
composer install --prefer-dist --no-interaction --quiet

# on circleci, the magento installation itself counts as a dependency as assets and it can be cached
buildecho "install magento incl. sampledata with the installer:"
magerun_install "${MAGENTO_VERSION}" "${INSTALL_SAMPLE_DATA}"
