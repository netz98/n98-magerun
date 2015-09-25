#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

smokerun() {
    local exit_status=0

    echo -e "\e[43m smokerun \e[49m n98-magerun.phar ""${@}"

    if [ -z ${N98_MAGERUN_TEST_MAGENTO_ROOT+x} ]; then
        php -f bin/n98-magerun -- "${@}"
        exit_status=$?
    else
        php -f bin/n98-magerun -- --root-dir="${N98_MAGERUN_TEST_MAGENTO_ROOT}" "${@}"
        exit_status=$?
    fi

    echo -e "\e[43m smokerun \e[49m exit status: ${exit_status}"

    # exit early
    if [ ${exit_status} -ne 0 ]; then
        : exit ${exit_status}
    fi

    return ${exit_status}
}

set +e

smokerun --version
smokerun --help
smokerun sys:info
smokerun extension:list

echo "smokerun is done."
