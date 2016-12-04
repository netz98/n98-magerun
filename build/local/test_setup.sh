#!/bin/bash
#
# install magento to run tests on a local development box
#
# usage: build/local/test_setup.sh
#

set -euo pipefail
IFS=$'\n\t'

source ./shared/build/sh/setup.sh

# whether or not magento is already installed, normally a quick-check based on file existence.
magento_is_installed() {
    local directory="${test_setup_directory}"
    local magento_local_xml="${directory}/app/etc/local.xml"

    if [ -e  "${magento_local_xml}" ]; then
        return 0
    else
        return 1
    fi
}

test_setup_basename="n98-magerun"
test_setup_magerun_cmd="bin/${test_setup_basename}"
test_setup_directory="./magento/www"
test_setup_db_host="127.0.0.1"
test_setup_db_port="${test_setup_db_port:-3306}"
test_setup_db_user="root"
test_setup_db_pass=""
test_setup_db_name="magento_magerun_test"

if [ "" != "$(installed_version)" ]; then
    buildecho "version '$(installed_version)' already installed, skipping setup"
else
    ensure_environment
    ensure_mysql_db
    ensure_magento "magento-mirror-1.9.2.3"
fi

# create stopfile if it does not yet exists
test_stopfile=".${test_setup_basename}"
if [ ! -f "${test_stopfile}" ]; then
    echo "${test_setup_directory}" > "${test_stopfile}"
    buildecho "stopfile ${test_stopfile} created: $(cat "${test_stopfile}")"
else
    buildecho "stopfile ${test_stopfile} exists: $(cat "${test_stopfile}")"
fi

buildecho "export N98_MAGERUN_TEST_MAGENTO_ROOT='${test_setup_directory}'"
