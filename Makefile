.PHONY: help build up down restart logs shell db-shell test phpstan cs-check cs-fix quality install install-dev status db db-reset

DOCKER_COMPOSE = docker compose
PHP_CONTAINER = php
DB_CONTAINER = database

.DEFAULT_GOAL := help

help: ## Display this help message
	@echo "Audit Project - Available Commands"
	@echo "==================================="
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build all Docker containers
	$(DOCKER_COMPOSE) build

up: ## Start all containers in detached mode
	$(DOCKER_COMPOSE) up -d

down: ## Stop and remove all containers
	$(DOCKER_COMPOSE) down

restart: ## Restart all containers
	$(DOCKER_COMPOSE) restart

logs: ## Show logs from all containers
	$(DOCKER_COMPOSE) logs -f

status: ## Show status of all containers
	$(DOCKER_COMPOSE) ps

shell: ## Open shell in PHP container
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) bash

db-shell: ## Open PostgreSQL shell in database container
	$(DOCKER_COMPOSE) exec $(DB_CONTAINER) psql -U audit_user -d audit

install: up ## Install dependencies
	$(DOCKER_COMPOSE) exec --user www-data $(PHP_CONTAINER) composer install --prefer-dist --no-dev

install-dev: up ## Install all dependencies including dev dependencies
	$(DOCKER_COMPOSE) exec --user www-data $(PHP_CONTAINER) composer install --prefer-dist
	@echo "Development setup complete!"

test: ## Run all tests
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/phpunit

phpstan: ## Run PHPStan static analysis
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/phpstan analyse src tests --level=8

cs-check: ## Check code style violations
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix code style violations automatically
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/php-cs-fixer fix

quality: ## Run complete quality check (PHPStan + CS-Fixer + Tests)
	@echo "Running PHPStan..."
	@$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/phpstan analyse src tests --level=8 || true
	@echo ""
	@echo "Checking code style..."
	@$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/php-cs-fixer fix --dry-run --diff || true
	@echo ""
	@echo "Running tests..."
	@$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) vendor/bin/phpunit

db: ## Create database schema
	@echo "Creating database schema..."
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:schema:create --quiet || echo "Schema already exists"
	@echo "Database setup complete!"

db-reset: ## Drop and recreate database schema
	@echo "Dropping existing schema..."
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:schema:drop --force --quiet || true
	@echo "Creating fresh schema..."
	$(DOCKER_COMPOSE) exec $(PHP_CONTAINER) php bin/console doctrine:schema:create --quiet
	@echo "Database reset complete!"
