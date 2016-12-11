#!/bin/bash
#
# build from clean checkout
#
# usage: ./build.sh from project root
set -euo pipefail
IFS=$'\n\t'

remove_assume_unchanged() {
  local git_dir="${1}"
  local path="${2}"
  (
    cd "${git_dir}"
    rm -f "${path}"
    git update-index --assume-unchanged -- "${path}"
  )
}

name="$(awk '/<project name="([^"]*)"/ && !done {print gensub(/<project name="([^"]*)".*/, "\\1", "g"); done=1}' build.xml)"
phar="${name}.phar"

echo "Building ${phar}..."

remove_assume_unchanged "." "${phar}"

base_dir="$(pwd -P)"
echo "$0 executed in ${base_dir}"

build_dir="build/output"
finish() {
  echo "trap exit removing '${build_dir}'.."
  rm -rf "${base_dir}/${build_dir}"
}
trap finish EXIT

if [[ -d "${build_dir}" ]]; then
  rm -rf "${build_dir}"
fi
if [[ -d "${build_dir}" ]]; then
  >&2 echo "Error: Can not remove build-dir '${build_dir}'"
  echo "aborting."
  exit 1
fi
mkdir "${build_dir}"
if [[ ! -d "${build_dir}" ]]; then
  >&2 echo "Error: Can not create build-dir '${build_dir}'"
  echo "aborting."
  exit 1
fi

git clone --quiet --no-local -- . "${build_dir}"
# remove fake-phar directly after clone
remove_assume_unchanged "${build_dir}" "n98-magerun.phar"

composer="${build_dir}/composer.phar"

if [[ -e "${composer}" ]]; then
  rm "${composer}"
fi

# Set COMPOSER_HOME if HOME and COMPOSER_HOME not set (shell with no home-dir, e.g. build server with webhook)
if [[ -z ${HOME+x} ]]; then
  if [ -z ${COMPOSER_HOME+x} ]; then
    mkdir -p "build/composer-home"
    export COMPOSER_HOME="$(pwd -P)/build/composer-home"
  fi
fi

if [[ ! -e "${composer}" ]]; then
  echo "Downloading composer.phar..."
  wget --quiet -O "${composer}" https://getcomposer.org/download/1.1.3/composer.phar
  chmod +x "${composer}"
fi

"${composer}" --version
php --version

echo "Composer install in '${build_dir}'..."
if ! "${composer}" -d="${build_dir}" --profile -q install --no-dev --no-interaction; then
  echo "failed to install from composer.lock, installing without lockfile now"
  rm "${build_dir}"/composer.lock
  "${composer}" -d="${build_dir}" --profile -q install --no-dev --no-interaction
fi

echo "Composer install build requirements in '${build_dir}/build'..."
"${composer}" -d="${build_dir}"/build --profile -q install --no-interaction

if [[ -e "${phar}" ]]; then
  echo "Remove earlier created ${phar} file"
  rm "${phar}"
fi

cd "${build_dir}"

echo "building in $(pwd -P)"
git  --no-pager log --oneline -1

echo "setting ulimits (new setting is to $(ulimit -Hn))..."
ulimit -Sn $(ulimit -Hn)

echo "invoking phing dist_clean target..."
set +e
php -f build/vendor/phing/phing/bin/phing -dphar.readonly=0 -- dist_clean
BUILD_STATUS=$?
set -e
if [[ ${BUILD_STATUS} -ne 0 ]]; then
  >&2 echo "error: phing build failed with exit status ${BUILD_STATUS}"
  echo "aborting."
  exit ${BUILD_STATUS}
fi

php -f build/phar/phar-timestamp.php

php -f "${phar}" -- --version

ls -al "${phar}"

php -r 'echo "SHA1: ", sha1_file("'"${phar}"'"), "\nMD5.: ", md5_file("'"${phar}"'"), "\n";'

cd -

cp -vp "${build_dir}"/"${phar}" "${phar}"

rm -rf "${build_dir}"

echo "done."
