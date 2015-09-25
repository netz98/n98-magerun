#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

buildecho()
{
    echo -en "\e[44m[CIRCLECI]\e[49m "
    echo "${1}"
}

buildsmokerun()
{
    buildecho "smokerun:"
    build/circleci/smoke.sh
}

export CLOVER_XML="${CIRCLE_ARTIFACTS:-.}/clover.xml"
buildecho "clover.xml: '${CLOVER_XML}', exported as \$CLOVER_XML."

export MAGENTO_VERSION="magento-mirror-1.9.2.1"
export DB=mysql
export INSTALL_SAMPLE_DATA=yes
export COVERAGE=65
