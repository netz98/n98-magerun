#!/bin/bash
#
# test_setup.sh - install magento to run tests on a local development box
#
set -euo pipefail
IFS=$'\n\t'

buildecho()
{
    echo -en "\e[44m[TEST-SETUP]\e[49m "
    echo "${1}"
}

installed_magerun_cmd() {
    local magerun_cmd="${test_setup_magerun_cmd="bin/${test_setup_basename}"}"

    if [ ! -e "${magerun_cmd}" ]; then
        echo >&2 "Error: can not find magerun '${magerun_cmd}', ensure you're running from project root."
        exit 2
    fi;

    echo "${magerun_cmd}"
}

# obtain installed vesion of the test-setup
installed_version() {
    local magerun_cmd="$(installed_magerun_cmd)"
    local directory="${test_setup_directory}"

    local magento_local_xml="${directory}/app/etc/local.xml"

    if [ -e  "${magento_local_xml}" ]; then
        local version="$(php -dmemory_limit=1g -f "${magerun_cmd}" -- --root-dir="${directory}" sys:info -- "version")"
        echo "${version}"
    fi
}

# checks befor starting the setup
ensure_environment() {
    local magerun_cmd="$(installed_magerun_cmd)"
    local directory="${test_setup_directory}"

    buildecho "magerun command: '${test_setup_magerun_cmd}'"

    if [ ! -d "${directory}" ]; then
        mkdir -p "${directory}"
    fi;
    buildecho "directory: '${directory}'"
}

# create mysql database ($1) if it does not yet exists
ensure_mysql_db() {
    local db_host="${test_setup_db_host}"
    local db_user="${test_setup_db_user}"
    local db_pass="${test_setup_db_pass}"
    local db_name="${test_setup_db_name}"


    if [ "" == "${db_pass}" ]; then
        mysql -u"${db_user}" -h"${db_host}" -e "CREATE DATABASE IF NOT EXISTS \`${db_name}\`;"
    else
        mysql -u"${db_user}" -p"${db_pass}" -h"${db_host}" -e "'CREATE DATABASE IF NOT EXISTS \`${db_name}\`;'"
    fi;

    buildecho "mysql database: '${db_name}' (${db_user}@${db_host})"
}

# install into a directory ($1) a Magento version ($2) with or w/o sample-data ($3)
ensure_magento() {
    local directory="${test_setup_directory}"
    local db_host="${test_setup_db_host}"
    local db_user="${test_setup_db_user}"
    local db_pass="${test_setup_db_pass}"
    local db_name="${test_setup_db_name}"

    local magento_version="${1}"
    local install_sample_data="${2:-no}"

    local magerun_cmd="${test_setup_magerun_cmd}"

    local magento_local_xml="${directory}/app/etc/local.xml"

    if [ -e  "${magento_local_xml}" ]; then
        local version="$(php -dmemory_limit=1g -f "${magerun_cmd}" -- --root-dir="${directory}" sys:info -- "version")"
        buildecho "version '${version}' already installed, skipping setup"
    else
        php -dmemory_limit=1g -f "${magerun_cmd}" -- install \
                    --magentoVersionByName="${magento_version}" --installationFolder="${directory}" \
                    --dbHost="${db_host}" --dbUser="${db_user}" --dbPass="${db_pass}" --dbName="${db_name}" \
                    --installSampleData="${install_sample_data}" --useDefaultConfigParams=yes \
                    --baseUrl="http://dev.magento.local/"
        buildecho "magento version '${magento_version}' installed."

        # automatically ignore the test installation directory as this is a local and interactive development env
        local gitignore="${directory}/.gitignore"
        if [ ! -e "${gitignore}" ]; then
            touch "${gitignore}"
        fi

        echo '*' > "${gitignore}"
    fi
}

test_setup_basename="n98-magerun"
test_setup_magerun_cmd="bin/${test_setup_basename}"
test_setup_directory="./magento/www"
test_setup_db_host="127.0.0.1"
test_setup_db_user="root"
test_setup_db_pass=""
test_setup_db_name="magento_magerun_test"

if [ "" != "$(installed_version)" ]; then
    buildecho "version '$(installed_version)' already installed, skipping setup"
    exit 0
fi

ensure_environment
ensure_mysql_db
ensure_magento "magento-mirror-1.9.2.3"

buildecho "export N98_MAGERUN_TEST_MAGENTO_ROOT='${test_setup_directory}'"
