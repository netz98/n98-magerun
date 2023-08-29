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

# Command coverage
#  completion
#  help
#  install
#  list
#  mysql-client
#  open-browser
#  script
#  uninstall
#  admin:notifications
#  admin:user:change-password
#  admin:user:change-status
#  admin:user:create
#  admin:user:delete
#  admin:user:list [done]
#  cache:clean
#  cache:dir:flush
#  cache:disable
#  cache:enable
#  cache:flush
#  cache:list [done]
#  cache:report
#  cache:view
#  category:create:dummy
#  cms:block:list
#  cms:block:toggle
#  config:delete [done]
#  config:dump
#  config:get [done]
#  config:search [done]
#  config:set [done]
#  customer:change-password
#  customer:create
#  customer:create:dummy
#  customer:delete
#  customer:info
#  customer:list [done]
#  db:console
#  db:create
#  db:drop
#  db:dump
#  db:import
#  db:info [done]
#  db:maintain:check-tables
#  db:query [done]
#  db:status
#  db:variables [done]
#  design:demo-notice
#  dev:class:lookup [done]
#  dev:code:model:method
#  dev:console
#  dev:email-template:usage
#  dev:ide:phpstorm:meta
#  dev:log
#  dev:log:db
#  dev:log:size
#  dev:merge-css
#  dev:merge-js
#  dev:module:create
#  dev:module:dependencies:from
#  dev:module:dependencies:on
#  dev:module:disable
#  dev:module:enable
#  dev:module:list
#  dev:module:observer:list [done]
#  dev:module:rewrite:conflicts [done]
#  dev:module:rewrite:list [done]
#  dev:module:update
#  dev:profiler
#  dev:report:count
#  dev:setup:script:attribute
#  dev:symlinks
#  dev:template-hints
#  dev:template-hints-blocks
#  dev:theme:duplicates
#  dev:theme:info
#  dev:theme:list
#  dev:translate:admin
#  dev:translate:export
#  dev:translate:set
#  dev:translate:shop
#  eav:attribute:create-dummy-values
#  eav:attribute:list [done]
#  eav:attribute:remove
#  eav:attribute:view
#  index:list [done]
#  index:reindex
#  index:reindex:all
#  local-config:generate
#  media:cache:image:clear
#  media:cache:jscss:clear
#  media:dump
#  script:repo:list
#  script:repo:run
#  sys:check [done]
#  sys:cron:history [done]
#  sys:cron:list [done]
#  sys:cron:run
#  sys:info [done]
#  sys:maintenance
#  sys:modules:list [done]
#  sys:setup:change-version
#  sys:setup:compare-versions
#  sys:setup:incremental
#  sys:setup:remove
#  sys:setup:run
#  sys:store:config:base-url:list
#  sys:store:list [done]
#  sys:url:list
#  sys:website:list [done]

@test "Command: admin:user:list" {
  run $BIN admin:user:list
  assert_output --partial 'username'
}

@test "Command: cache:list" {
  run $BIN cache:list
  assert_output --partial 'config'
  assert_output --partial 'eav'
}

@test "Command: config:get" {
  run $BIN config:get 'web/unsecure/base_url'
  assert_output --partial 'Path'
  assert_output --partial 'Scope-ID'
  assert_output --partial 'Value'
  assert_output --partial 'http'

  run $BIN config:get 'web/*'
  assert_output --partial 'http'
}

@test "Command: config:search" {
  run $BIN config:search 'url'
  assert_output --partial "Admin Base URL"
}

@test "Command: config:set" {
  run $BIN config:set 'foo/bar/baz' 1
  assert_output --partial "foo/bar/baz => 1"
}

@test "Command: config:delete" {
  run $BIN config:delete --all 'foo/bar/baz'
  assert_output --partial "foo/bar/baz"
}

@test "Command: customer:list" {
  run $BIN customer:list
  assert_output --partial 'email'
  assert_output --partial 'firstname'
  assert_output --partial 'lastname'
}

@test "Command: db:info" {
  run $BIN db:info
  assert_output --partial 'PDO-Connection-String'
}

@test "Command: db:query" {
  run $BIN db:query "SELECT * FROM core_config_data WHERE path = 'web/unsecure/base_url'"
  assert_output --partial 'web/unsecure/base_url'
}

@test "Command: db:variables" {
  run $BIN db:variables
  assert_output --partial 'innodb_buffer_pool_size'
}

@test "Command: dev:class:lookup" {
  run $BIN dev:class:lookup block 'catalog/product_view'
  assert_output --partial 'Mage_Catalog_Block_Product_View'

  run $BIN dev:class:lookup model 'catalog/product'
  assert_output --partial 'Mage_Catalog_Model_Product'

  run $BIN dev:class:lookup helper 'catalog/data'
  assert_output --partial 'Mage_Catalog_Helper_Data'
}

@test "Command: dev:module:observer:list" {
  run $BIN dev:module:observer:list global
  assert_output --partial 'catalog_product_save_after'
}

@test "Command: dev:module:rewrite:conflicts" {
  run $BIN dev:module:rewrite:conflicts
}

@test "Command: dev:module:rewrite:list" {
  run $BIN dev:module:rewrite:list
}

@test "Command: eav:attribute:list" {
  run $BIN eav:attribute:list
  assert_output --partial 'price'
}

@test "Command: index:list" {
  run $BIN index:list
  assert_output --partial 'catalog_category_product'
}

@test "Command: sys:check" {
  run $BIN sys:check
  assert_output --partial "PHP"
  assert_output --partial "FILESYSTEM"

  run $BIN sys:check --format=json
  assert_output --partial '"Group": "settings"'
}

@test "Command: sys:info" {
  run $BIN sys:info
  assert_output --partial 'Version'

  run $BIN sys:info --format=json
  assert_output --partial '"name": "Version"'
}

@test "Command: sys:cron:history" {
  run $BIN sys:cron:history
  assert_output --partial 'Finished'
}

@test "Command: sys:cron:list" {
  run $BIN sys:cron:list
  assert_output --partial 'schedule'
}

@test "Command: sys:modules:list" {
  run $BIN sys:modules:list
  assert_output --partial 'Mage_Adminhtml'
}

@test "Command: sys:store:list" {
  run $BIN sys:store:list
  assert_output --partial 'code'
}

@test "Command: sys:website:list" {
  run $BIN sys:website:list
  assert_output --partial 'code'
}
