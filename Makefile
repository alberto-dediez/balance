# Executables (local)
DOCKER_COMP = docker compose --env-file .env $(shell [ -f .env.local ] && echo "--env-file .env.local")

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php
DATABASE_CONT = $(DOCKER_COMP) exec database
TEST_CONT     = $(DOCKER_COMP) exec -e APP_ENV=test -e DATABASE_URL="sqlite:///./var/data.db" -e SYMFONY_DEPRECATIONS_HELPER="disabled=1" php

# Executables
PHP      = $(PHP_CONT) php
PHP_TEST = $(TEST_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console
PHP_EXTERNAL = docker run --rm -v $(pwd):/app -w /app php:8.4-cli

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc

## —— 🎵 🐳 The Symfony Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs) || For development: MYSQL_USER=app MYSQL_PASSWORD=123 MYSQL_DATABASE=hydra MYSQL_ROOT_PASSWORD=1234 docker compose up --detach
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@$(PHP_CONT) bash

db-sh: ## Connect to the FrankenPHP Database container
	@$(DATABASE_CONT) sh

db-bash: ## Connect to the FrankenPHP Database container via bash so up and down arrows go to previous commands
	@$(DATABASE_CONT) bash

trust:
	docker cp $(docker compose ps -q php):/data/caddy/pki/authorities/local/root.crt /usr/local/share/ca-certificates/root.crt && sudo update-ca-certificates

build-prod:
	@$(DOCKER_COMP) -f compose.yaml -f compose.prod.yaml build --pull --no-cache

up-prod:
	@$(DOCKER_COMP) -f compose.yaml -f compose.prod.yaml up --wait

init:
	@$(SYMFONY) lexik:jwt:generate-keypair --skip-if-exists
	@$(SYMFONY) doctrine:database:create --if-not-exists
	@$(SYMFONY) doctrine:migrations:migrate

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

composer-external: ## Run composer from external container, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
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
	@$(eval options ?=)
	@$(PHP_TEST) bin/console doc:sch:drop -e test --force -q
	@$(PHP_TEST) bin/console doc:sch:upd -e test --force -q
	@$(PHP_TEST) bin/phpunit $(options)

test-acceptance: ## Execute acceptance tests
	@$(eval options ?=)
	@$(PHP_TEST) bin/console doc:sch:drop -e test --force -q
	@$(PHP_TEST) bin/console doc:sch:upd -e test --force -q
	@$(PHP_TEST) vendor/bin/behat $(options)

reset-test-cache: ## Clear testing cache
	@$(PHP) bin/console cache:clear --env=test -q

behat-init:
	@$(PHP) vendor/bin/behat --init

behat-snippets:
	@$(PHP) vendor/bin/behat --dry-run --append-snippets

## —— Bugs and errors ———————————————————————————————————————————————————————
external-php:
	@$(eval c ?=)
	@$(PHP_EXTERNAL) $(c)

## —— Code Quality ——————————————————————————————————————————————————————————
phpstan:
	@$(eval c ?=)
	@$(PHP) bin/phpstan $(c)

rector:
	@$(eval c ?=)
	$(PHP_CONT) ./vendor/bin/rector $(c)

cs:
	@$(PHP) bin/php-cs-fixer fix --dry-run --diff -v

cs-fix:
	@$(PHP) bin/php-cs-fixer fix

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

translation:
	@$(PHP) ./bin/console trans:extract es --force --prefix="NEW_" -q
	@$(PHP) ./bin/console trans:extract en --force --prefix="NEW_" -q

## —— Production ————————————————————————————————————————————————————————————
asset-prod:
	@$(PHP) bin/console sass:build
	@$(PHP) bin/console asset-map:compile
