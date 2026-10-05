# Executables (local)
DOCKER_COMP = docker compose --env-file .env $(shell [ -f .env.local ] && echo "--env-file .env.local")

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php
DATABASE_CONT = $(DOCKER_COMP) exec database
TEST_CONT     = $(DOCKER_COMP) exec -e APP_ENV=test -e DATABASE_URL="sqlite:///./var/data.db" -e SYMFONY_DEPRECATIONS_HELPER="disabled=1" -e STRIPE_SECRET_KEY="sk_test_dummy" -e STRIPE_WEBHOOK_SECRET="whsec_dummy" -e ACCOUNT_IMPERSONATION_ENABLED=true -e ANNOUNCEMENTS_ENABLED=true php
# Executables
PHP      = $(PHP_CONT) php
PHP_TEST = $(TEST_CONT) php -d memory_limit=512M
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console
PHP_EXTERNAL = docker run --rm -v $(CURDIR):/app -w /app php:8.4-cli
COMPOSER_EXTERNAL = docker run --rm -v $(CURDIR):/app -w /app composer:latest

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

external-composer: ## Run composer from external container, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER_EXTERNAL) $(c)

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

test-parallel: | test-acceptance-parallel test-unit

test-acceptance-parallel: ## Execute acceptance tests in parallel, pass "processes=N" and/or "options="
	@$(eval options ?=)
	@$(eval processes ?=)
	@$(PHP_TEST) bin/console doc:sch:drop -e test --force -q
	@$(PHP_TEST) bin/console doc:sch:upd -e test --force -q
	@$(PHP_TEST) bin/console cache:warmup -e test -q
	@$(TEST_CONT) bash bin/behat-parallel $(if $(processes),-j $(processes)) $(options)

reset-test-cache: ## Clear testing cache
	@$(PHP) bin/console cache:clear --env=test -q

behat-init:
	@$(PHP) vendor/bin/behat --init

behat-snippets:
	@$(PHP) vendor/bin/behat --dry-run --append-snippets

stripe-webhook:
	stripe listen --skip-verify --forward-to https://events.local/webhooks/stripe

## —— Bugs and errors ———————————————————————————————————————————————————————
external-php:
	@$(eval c ?=)
	@$(PHP_EXTERNAL) $(c)

## —— Code Quality ——————————————————————————————————————————————————————————
phpstan:
	@$(eval c ?=)
	@$(PHP) bin/phpstan $(c)

phpstan-json:
	@$(eval c ?=)
	@$(PHP) bin/phpstan $(c) --error-format=json > phpstan-report.json

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
	@$(PHP) ./bin/console trans:extract tr --force --prefix="NEW_" -q

## —— Messages ————————————————————————————————————————————————————————————
queues: ## Start workers for all priority queues (high, medium, low)
	@$(PHP) bin/console messenger:consume --all --exclude-receivers=failed -vv

queues-high: ## Start worker for the high priority queue
	@$(PHP) bin/console messenger:consume async_high -vv

queues-medium: ## Start worker for the medium priority queue
	@$(PHP) bin/console messenger:consume async -vv

queues-low: ## Start worker for the low priority queue
	@$(PHP) bin/console messenger:consume async_low -vv

## —— Production ————————————————————————————————————————————————————————————
asset-prod:
	@$(PHP) bin/console sass:build
	@$(PHP) bin/console asset-map:compile

asset-email:
	./var/dart-sass/sass assets/styles/email.scss assets/styles/email.css --no-source-map

## —— AI 🤖 ——————————————————————————————————————————————————————————————
ai-setup: ## Initialize the AI (Claude) settings: .claude folders, shared/skills/CLAUDE.md links and fileSuggestion hook
	../ai/setup-ai.sh $(CURDIR)
