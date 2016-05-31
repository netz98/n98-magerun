#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

echo 'preparing git repository for build clone...'

if [ -f $(git rev-parse --git-dir)/shallow ]; then
    echo "unshallowing via fetch..."
    git fetch -q --unshallow origin
else
    echo "fetching..."
    git fetch -q origin
fi

echo "running build script build.sh..."
./build.sh
