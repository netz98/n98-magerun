#!/usr/bin/env bats

function setup {
  load 'test_helper/bats-support/load'
  load 'test_helper/bats-assert/load'

  if [ -z "$N98_MAGERUN_BIN" ]; then
    echo "ENV variable N98_MAGERUN_BIN is missing"
    exit 1
  fi

  if [ -z "$N98_MAGERUN_TEST_MAGENTO_ROOT" ]; then
    echo "ENV variable N98_MAGERUN_TEST_MAGENTO_ROOT is missing"
    exit 1
  fi

  BIN="$N98_MAGERUN_BIN --root-dir=$N98_MAGERUN_TEST_MAGENTO_ROOT"
}

@test "sys:check " {
  run $BIN sys:check
  assert_output --partial "PHP"
  assert_output --partial "FILESYSTEM"
}
