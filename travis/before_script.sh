#!/usr/bin/env bash

# only install magento if MAGENTO_VERSION has been set

if [ ! -z ${MAGENTO_VERSION+x} ]; then

    echo "installing magento ${MAGENTO_VERSION}"

    mysql -e 'CREATE DATABASE IF NOT EXISTS `magento_travis`;'

    export N98_MAGERUN_TEST_MAGENTO_ROOT="./${MAGENTO_VERSION}"

    bin/n98-magerun install --magentoVersionByName="${MAGENTO_VERSION}" --installationFolder="./${MAGENTO_VERSION}" --dbHost=127.0.0.1 --dbUser=root --dbPass='' --dbName="magento_travis" --installSampleData=${INSTALL_SAMPLE_DATA} --useDefaultConfigParams=yes --baseUrl="http://travis.magento.local/"

else

    echo "no magento version to install"

fi
