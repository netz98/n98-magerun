#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

. build/circleci/source.sh

buildecho "codecov clover file upload:"
build/codecov/bash -f "${CLOVER_XML}"
