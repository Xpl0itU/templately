.PHONY: help build up down reset restart logs test shell mysql-shell migrate seed

# Load environment variables from .env.docker if it exists
ifneq (,$(wildcard .env.docker))
    include .env.docker
    export
endif

# Default database name
DB_NAME ?= templately

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(firstword $(MAKEFILE_LIST)) | awk 'BEGIN {FS = ":.*?## "}; {printf "  %-15s %s\n", $$1, $$2}'

build: ## Build Docker containers
	docker compose --env-file .env.docker build

up: ## Start Docker containers
	docker compose --env-file .env.docker up -d

down: ## Stop Docker containers
	docker compose down

reset: ## Reset Docker containers
	@docker exec templately_app bash -c "rm -rf /var/www/html/writable/cache/*" 2>/dev/null || true
	@docker exec templately_app bash -c "rm -f /var/www/html/writable/session/*" 2>/dev/null || true
	docker compose down -v

restart: ## Restart Docker containers
	docker compose restart

logs: ## View logs from all containers
	docker compose logs -f

logs-app: ## View application logs
	docker compose logs -f app

logs-mysql: ## View MySQL logs
	docker compose logs -f mysql

test: ## Run PHPUnit tests
	docker exec -it templately_app vendor/bin/phpunit

shell: ## Access application container shell
	docker exec -it templately_app bash

mysql-shell: ## Access MySQL shell
	docker exec -it templately_mysql mysql -uroot -p$(DB_ROOT_PASSWORD) $(DB_NAME)

migrate: ## Run database migrations
	docker exec -it templately_app php spark migrate --all

migrate-rollback: ## Rollback last migration
	docker exec -it templately_app php spark migrate:rollback

seed: ## Run database seeders
	docker exec -it templately_app php spark db:seed

key-generate: ## Generate application encryption key
	docker exec -it templately_app php spark key:generate

permissions: ## Fix writable directory permissions
	docker exec -it templately_app chown -R www-data:www-data /var/www/html/writable
	docker exec -it templately_app chmod -R 755 /var/www/html/writable

composer-install: ## Install composer dependencies
	docker exec -it templately_app composer install

composer-update: ## Update composer dependencies
	docker exec -it templately_app composer update

ps: ## Show running containers
	docker compose ps

rebuild: down build up ## Rebuild and restart containers

init: ## Initialize application (first-time setup)
	@echo "Setting up Templately..."
	@if [ ! -f .env.docker ]; then \
		cp .env.docker.example .env.docker; \
		echo "Created .env.docker file. Please edit it with your configuration."; \
	fi
	@make build
	@make up
	@echo "Waiting for services to start..."
	@sleep 10
	@echo "Installing Composer dependencies..."
	@make composer-install
	@make key-generate
	@make migrate
	@echo "Setup complete! Access the application at http://localhost:8080"
