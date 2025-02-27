# Executables
PHP      = php
COMPOSER = composer
SYMFONY  = $(PHP) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc

## —— 🎵 🐳 The Symfony Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

## —— Make ——————————————————————————————————————————————————————————————————
entity:
	@$(PHP) bin/console make:entity

## —— Migrations ————————————————————————————————————————————————————————————
migration:
	@$(PHP) bin/console make:migration --formatted

migrate:
	@$(PHP) bin/console doctrine:migrations:migrate -q

## —— Testing ———————————————————————————————————————————————————————————————
test: | test-acceptance test-unit

test-unit: ## Execute unit tests
	@$(PHP) bin/console doc:sch:drop -e test --force -q
	@$(PHP) bin/console doc:sch:upd -e test --force -q
	# @$(PHP) bin/console doc:fix:load -e test -q
	@$(PHP) bin/phpunit

test-acceptance: ## Execute acceptance tests
	@$(PHP) bin/console doc:sch:drop -e test --force -q
	@$(PHP) bin/console doc:sch:upd -e test --force -q
	# @$(PHP) bin/console doc:fix:load -e test -q
	@$(PHP) vendor/bin/behat

reset-test-cache: ## Clear testing cache
	@$(PHP) bin/console cache:clear --env=test -q

behat-init:
	@$(PHP) vendor/bin/behat --init

behat-snippets:
	@$(PHP) vendor/bin/behat --dry-run --append-snippets

## —— Develop ———————————————————————————————————————————————————————————————
develop:
	rm ./public/assets/ -rf
	@$(PHP) bin/console sass:build

develop-watch:
	rm ./public/assets/ -rf
	@$(PHP) bin/console sass:build --watch

asset-require:
	@$(eval r ?=)
	@$(PHP) bin/console importmap:require $(r)

asset-install:
	@$(PHP) bin/console importmap:install

icon-import:
	@$(eval p ?=)
	@$(eval s ?=)
	@$(PHP) bin/console ux:icons:import $(p) $(s)

icon-lock:
	@$(PHP) bin/console ux:icons:lock

## —— Production ————————————————————————————————————————————————————————————
production:
	@$(PHP) bin/console sass:build
	@$(PHP) bin/console asset-map:compile