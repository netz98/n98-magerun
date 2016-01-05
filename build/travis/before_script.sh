# only install magento if MAGENTO_VERSION has been set

if [ ! -z ${MAGENTO_VERSION+x} ]; then

    echo "installing magento ${MAGENTO_VERSION}"

    db_pass="${SETUP_DB_PASS:-}"

    if [ "" == "${db_pass}" ]; then
        mysql -uroot -e 'CREATE DATABASE IF NOT EXISTS `magento_travis`;'
    else
        mysql -uroot -p"${db_pass}" -e 'CREATE DATABASE IF NOT EXISTS `magento_travis`;'
    fi;

    target_directory="${SETUP_DIR:-./}${MAGENTO_VERSION}"

    export N98_MAGERUN_TEST_MAGENTO_ROOT="${target_directory}"

    php -dmemory_limit=1g -f bin/n98-magerun -- install \
                --magentoVersionByName="${MAGENTO_VERSION}" --installationFolder="${target_directory}" \
                --dbHost=127.0.0.1 --dbUser=root --dbPass="${db_pass}" --dbName="magento_travis" \
                --installSampleData=${INSTALL_SAMPLE_DATA} --useDefaultConfigParams=yes \
                --baseUrl="http://travis.magento.local/"

else

    echo "no magento version to install"

fi
