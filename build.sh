#!/bin/bash
#
# build from clean checkout
#
# usage: ./build.sh from project root
set -euo pipefail

IFS=$'\n\t'
PHP_BIN="php"
BOX_BIN="./box.phar"
PHAR_OUTPUT_FILE="./n98-magerun.phar"
COMPOSER_BIN="composer"

function system_setup() {
  if [ "$(uname -s)" != "Darwin" ]; then
    ulimit -Sn $(ulimit -Hn)
  fi
}

function check_dependencies() {
  DEPENDENCY_ERROR=false

  if command -v curl &>/dev/null; then
    echo "curl found"
  else
    echo "curl not found!"
    DEPENDENCY_ERROR=true
  fi

  if command -v git &>/dev/null; then
    echo "git found"
  else
    echo "git not found!"
    DEPENDENCY_ERROR=true
  fi

  if command -v $PHP_BIN &>/dev/null; then
    echo "php found"
  else
    echo "php not found!"
    DEPENDENCY_ERROR=true
  fi

  if [ $DEPENDENCY_ERROR = true ]; then
    echo "Some dependecies are not found. Cannot build."
    exit 1
  fi

}

function download_box() {
  if [ ! -f box.phar ]; then
    curl -L https://github.com/box-project/box/releases/download/3.16.0/box.phar -o $BOX_BIN
    chmod +x ./box.phar
  fi
}

function download_composer() {
  if command -v composer &>/dev/null; then
    true; # do nothing
  else
    echo "Composer was not found. Try to install it ..."
    # install composer
    $PHP_BIN -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $PHP_BIN composer-setup.php --install-dir=/usr/local/bin --filename=composer
    if [ -f "./composer-setup.php" ]; then
      rm "./composer-setup.php";
    fi
  fi
}

function find_commit_timestamp() {
  LAST_COMMIT_TIMESTAMP="$(git log --format=format:%ct HEAD -1)" # reproducible build
}

function create_new_phar() {
  # set composer suffix, otherwise Composer will generate a file with a unique identifier
  # which will then create a no reproducable phar file with a differenz MD5
  $COMPOSER_BIN config autoloader-suffix N98MagerunNTS

  # Run install again to get the latest install.php and install.json file
  $COMPOSER_BIN install

  $PHP_BIN $BOX_BIN compile

  # unset composer suffix
  $COMPOSER_BIN config autoloader-suffix --unset

  # Set timestamp of newly generted phar file to the commit timestamp
  $PHP_BIN -f build/phar/phar-timestamp.php -- $LAST_COMMIT_TIMESTAMP

  # Run a signature verification after the timestamp manipulation
  $PHP_BIN $BOX_BIN verify $PHAR_OUTPUT_FILE

  # make phar executable
  chmod +x $PHAR_OUTPUT_FILE

  # Print version of new phar file which is also a test
  $PHP_BIN -f $PHAR_OUTPUT_FILE -- --version

  # List new phar file for debugging
  ls -al "$PHAR_OUTPUT_FILE"
}

function print_info_before_build() {
  echo "with: $($PHP_BIN --version | head -n 1)"
  echo "with: $("${COMPOSER_BIN}" --version)"
  echo "with: $("${BOX_BIN}" --version)"
  echo "build version: $(git --no-pager log --oneline -1)"
  echo "last commit timestamp: ${LAST_COMMIT_TIMESTAMP}"
  echo "provision: ulimits (soft) set from $(ulimit -Sn) to $(ulimit -Hn) (hard) for faster phar builds..."
}

check_dependencies
system_setup
download_box
download_composer
find_commit_timestamp
print_info_before_build
create_new_phar

echo "done."
