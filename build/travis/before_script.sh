#
# prepare test directory by installing the requested magento version (if any)
#
# this file is sourced because environment variables are exported
#
# usage: source build/travis/before_script.sh
#

# enable uninstall testsuite (disabled by default as it destroys data)
grep -v 'remove uninstall test' phpunit.xml.dist > phpunit.xml

# print php debug informations
php -m

# only install magento if MAGENTO_VERSION has been set
if [ ! -z ${MAGENTO_VERSION+x} ]; then

    echo "installing magento ${MAGENTO_VERSION}"

    db_pass="${SETUP_DB_PASS:-}"

    if [ "" == "${db_pass}" ]; then
        mysql -uroot -e 'SELECT VERSION();'
        mysql -uroot -e 'CREATE DATABASE IF NOT EXISTS `magento_travis`;'
        mysql -uroot -e 'SHOW ENGINES;'
    else
        mysql -uroot -p"${db_pass}" -e 'SELECT VERSION();'
        mysql -uroot -p"${db_pass}" -e 'CREATE DATABASE IF NOT EXISTS `magento_travis`;'
        mysql -uroot -p"${db_pass}" -e 'SHOW ENGINES;'
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
