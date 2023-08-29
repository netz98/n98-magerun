#!/usr/bin/env bash

set -o errexit

# Basic tools

set -x

# bats (for testing)
git clone --branch v1.2.1 https://github.com/bats-core/bats-core.git /tmp/bats-core && pushd /tmp/bats-core >/dev/null && sudo ./install.sh /usr/local

# Show info to simplify debugging
lsb_release -a
