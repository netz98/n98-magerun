#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

codecov_step()
{
    printf '::group::\e[44m[codecov]\e[49m %s\n' "${1}"
}

# download and install magento (by the git cloned magerun version itself)
magerun_install()
{
    local version="${1}"
    local space="."
    local dir="${space}/${version}"
    local data="${2:-no}"


    # mysql -uroot -e 'CREATE DATABASE IF NOT EXISTS `magento_travis`;'

    php -dmemory_limit=1g -f bin/n98-magerun -- install \
            --magentoVersionByName="${version}" --installationFolder="${dir}" \
            --dbHost=127.0.0.1 --dbUser=root --dbPass="${SETUP_DB_PASS-}" --dbName="magento_travis" \
            --installSampleData=${data} --useDefaultConfigParams=yes \
            --baseUrl="http://travis.magento.local/"
}

codecov_step "environment"

set -x
export CLOVER_XML="./build/coverage/clover.xml"
export MAGENTO_VERSION="${MAGENTO_VERSION-magento-mirror-1.9.2.1}"
export DB=mysql
export INSTALL_SAMPLE_DATA=yes
export COVERAGE=65
set +x
