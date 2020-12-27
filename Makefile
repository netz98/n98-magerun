# this file is part of n98-magerun

PHP_BIN ?= php7.2

all: vendor/.make test

vendor/.make: composer.json
	composer install; \
		touch $@

composer.lock: vendor/.make composer.json

.PHONY: test
test: vendor/.make
	$(PHP_BIN) -f vendor/phpunit/phpunit/phpunit
