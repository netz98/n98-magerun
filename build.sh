#!/bin/bash
#
# build from clean checkout
#
# usage: ./build.sh [--changes]
#
# options: --changes    build with local changes
#
# note:    run from project root
#
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

exit_trap() {
  local status=$?
  if [[ -d "${base_dir:?}/${build_dir}" ]]; then
    echo "trap: removing '${build_dir}'.."
    rm -rf "${base_dir:?}/${build_dir}"
  fi
  echo "exit ($status)."
}

name="$(awk '/<project name="([^"]*)"/ && !done {print gensub(/<project name="([^"]*)".*/, "\\1", "g"); done=1}' build.xml)"
nice_name="$(php -r "echo str_replace(' ', '', ucwords(strtr('${name}', '-', ' ')));")"
phar="${name}.phar"
echo "Building ${phar}..."

# remove stub which is also the build result destination, so if build fails the file does not exists
remove_assume_unchanged "." "${phar}"

base_dir="$(pwd -P)"
build_dir="build/output"

echo "$0 executed in ${base_dir}"

trap exit_trap EXIT

rm -rf "${build_dir}"
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

git clone --quiet --no-local --depth 1 -- . "${build_dir}"

# --changes : incorporate changes into the build, w/o builds latest revision
if [[ "${1-}" == "--changes" ]]; then
  echo "apply changes and copy untracked files..."
  git diff HEAD | (cd "${build_dir}" && git apply)
  # copy over files that are not tracked
  git status --porcelain | awk 'match($1, "\\?\\?"){print "cp " $2 " '"${build_dir}"'/" $2}' | sh
  (cd "${build_dir}" && git status --short)
fi

# remove fake-phar directly after clone
remove_assume_unchanged "${build_dir}" "n98-magerun.phar"

composer_bin="${base_dir}/vendor/bin/composer"
phing_bin="${base_dir}/vendor/bin/phing"

# Set COMPOSER_HOME if HOME and COMPOSER_HOME not set (shell with no home-dir, e.g. build server with webhook)
if [[ -z ${HOME+x} && -z ${COMPOSER_HOME+x} ]]; then
  echo "provision: create COMPOSER_HOME directory for composer (no HOME)"
  mkdir -p "build/composer-home"
  export COMPOSER_HOME
  COMPOSER_HOME="$(pwd -P)/build/composer-home"
fi

echo "with: $(php --version|head -n 1)"
echo "with: $("${composer_bin}" --version)"
echo "with: $("${phing_bin}" -version)"

cd "${build_dir}"

echo "building in $(pwd -P)"
echo "build version: $(git --no-pager log --oneline -1)"

echo "provision: ulimits (soft) set from $(ulimit -Sn) to $(ulimit -Hn) (hard) for faster phar builds..."
ulimit -Sn "$(ulimit -Hn)"
timestamp="$(git log --format=format:%ct HEAD -1)" # reproduceable build
echo "build timestamp: ${timestamp}"

php -f "${phing_bin}" -dphar.readonly=0 -- \
  -Dcomposer_suffix="${nice_name}${timestamp}" \
  -Dcomposer_bin="${composer_bin}" \
  dist_clean

php -f build/phar/phar-timestamp.php

php -f "${phar}" -- --version
ls -al "${phar}"

cd -
cp -vp "${build_dir}"/"${phar}" "${phar}"
rm -rf "${build_dir}"

trap - EXIT

echo "done."
