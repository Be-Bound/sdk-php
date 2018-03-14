# PHP binary
PHP_BIN:=php
# Composer executable
COMPOSER_BIN:=composer
# a list of directories containing sources
SRCS:=src tests
# a temporary folder
TMP_DIR:=.build
# PHPStan level
PHPSTAN_LEVEL:=1
# PHPCS warning severity
PHPCS_WARNING:=0

# finds all php files in a given directory
macro_find_phpfiles = $(shell find $(1) -type f -name "*.php")
# finds all php sources in SRCS directories
src = $(foreach d,$(SRCS),$(call macro_find_phpfiles,$(d)))

.PHONY: test
test: install lint phpstan phpunit phpcs

.PHONY: install
install: vendor/autoload.php

.PHONY: lint
lint: $(TMP_DIR)/phplint.lock

.PHONY: phpstan
phpstan: $(TMP_DIR)/phpstan.lock

.PHONY: phpunit
phpunit: $(TMP_DIR)/phpunit.lock

.PHONY: phpcs
phpcs: $(TMP_DIR)/phpcs.lock

.PHONY: clean
clean: clean_build clean_vendor

.PHONY: clean_vendor
clean_vendor:
	@echo Clean vendor folder...
	@test ! -e vendor || rm -r vendor

.PHONY: clean_build
clean_build:
	@echo Clean temporary build folder...
	@test ! -e .build || rm -r .build

.PHONY: csfix
csfix:
	@echo PHPCS: fix code style...
	@$(PHP_BIN) ./vendor/bin/phpcbf --standard=PSR2 --colors $(SRCS)

phpunit.xml:
	@echo Creating Local PHPUnit configuration...
	@cp phpunit.dist.xml phpunit.xml

$(TMP_DIR):
	@echo Creating temporary folder...
	@mkdir -p $@

$(TMP_DIR)/phplint.lock: $(TMP_DIR) $(src)
	@echo Linting source files...
	@$(foreach f,$(filter-out $(TMP_DIR),$?),$(PHP_BIN) -l $(f);)
	@touch $@

$(TMP_DIR)/phpstan.lock: $(TMP_DIR) vendor/autoload.php $(src)
	@echo Analysing statically...
	@$(PHP_BIN) ./vendor/bin/phpstan analyse --autoload-file=tests/autoload.php --level=$(PHPSTAN_LEVEL) --no-progress $(SRCS)
	@touch $@

$(TMP_DIR)/phpunit.lock: $(TMP_DIR) vendor/autoload.php phpunit.xml $(src)
	@echo Testing...
	@$(PHP_BIN) ./vendor/bin/phpunit --configuration phpunit.xml --testdox
	@touch $@

$(TMP_DIR)/phpcs.lock: $(TMP_DIR) vendor/autoload.php $(src)
	@echo PHPCS: analyses code style...
	@$(PHP_BIN) ./vendor/bin/phpcs --standard=PSR2 -s --colors --warning-severity=$(PHPCS_WARNING) $(SRCS)
	@touch $@

vendor/autoload.php: composer.lock
	@echo Composer installing...
	@$(COMPOSER_BIN) install --prefer-dist --no-suggest
	@touch $@

composer.lock: composer.json
	@echo Composer installing...
	@$(COMPOSER_BIN) install --prefer-dist --no-suggest
	@touch $@
