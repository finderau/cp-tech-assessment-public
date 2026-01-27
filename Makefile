SHELL := /bin/bash

# WordPress Playground - Makefile
# Helper targets for common operations

.DEFAULT_GOAL := help

# Configuration
COMPOSE_FILE := docker-compose.yml
PROJECT_NAME := wp-playground
PHP_CONTAINER := wp-playground-php-fpm
NGINX_CONTAINER := wp-playground-nginx
DB_CONTAINER := coeval-mysql
DB_NAME := wp_playground_db
DB_USER := root
DB_PASS := abcd1234

# Environment files
SECRETS_FILE := config/playground.secrets.env
LOCAL_FILE := config/playground.local.env

#
# Help target
#
help:
	@echo "WordPress Playground - Available Commands"
	@echo ""
	@echo "Setup & Configuration:"
	@echo "  make setup              - Initial setup (creates env files, networks, installs deps)"
	@echo "  make create-env-files   - Create environment files if they don't exist"
	@echo "  make check-env-files    - Check if required environment files exist"
	@echo ""
	@echo "Container Management:"
	@echo "  make build              - Build Docker images"
	@echo "  make start              - Start all containers"
	@echo "  make stop               - Stop all containers"
	@echo "  make restart            - Restart all containers"
	@echo "  make status             - Show container status"
	@echo "  make logs               - View container logs"
	@echo ""
	@echo "Development:"
	@echo "  make shell              - Open shell in PHP container"
	@echo "  make composer-install   - Install composer dependencies"
	@echo "  make xdebug-enable      - Enable Xdebug"
	@echo "  make xdebug-disable     - Disable Xdebug"
	@echo ""
	@echo "Database:"
	@echo "  make db-create          - Create the database"
	@echo "  make db-seed            - Seed database from db_backups/seed.sql"
	@echo "  make db-backup          - Backup database to db_backups/"
	@echo "  make db-shell           - Open MySQL shell"
	@echo ""
	@echo "Cleanup:"
	@echo "  make clean              - Remove containers and networks"
	@echo ""
	@echo "Standalone Mode (run 'make standalone-help' for full list):"
	@echo "  make standalone-setup   - Setup standalone environment (includes MySQL + Traefik)"
	@echo "  make standalone-start   - Start standalone environment"
	@echo "  make standalone-stop    - Stop standalone environment"
	@echo ""
.PHONY: help

#
# Setup targets
#
setup: create-env-files create-networks create-log-dirs composer-install create-src-dir
	@echo "Setup complete! Run 'make start' to start the containers."
.PHONY: setup

create-env-files:
	@if [ ! -f $(SECRETS_FILE) ]; then \
		touch $(SECRETS_FILE); \
		echo "Created $(SECRETS_FILE)"; \
	else \
		echo "$(SECRETS_FILE) already exists"; \
	fi
	@if [ ! -f $(LOCAL_FILE) ]; then \
		touch $(LOCAL_FILE); \
		echo "Created $(LOCAL_FILE)"; \
	else \
		echo "$(LOCAL_FILE) already exists"; \
	fi
.PHONY: create-env-files

check-env-files:
	@missing=0; \
	for file in $(SECRETS_FILE) $(LOCAL_FILE); do \
		if [ ! -f $$file ]; then \
			echo "Missing: $$file"; \
			missing=1; \
		fi; \
	done; \
	if [ $$missing -eq 1 ]; then \
		echo "Run 'make create-env-files' to create missing files"; \
		exit 1; \
	else \
		echo "All environment files present"; \
	fi
.PHONY: check-env-files

create-networks:
	@docker network create mysql 2>/dev/null || true
	@docker network create redis 2>/dev/null || true
	@docker network create traefik 2>/dev/null || true
	@echo "External networks created/verified (wp-playground network managed by docker-compose)"
.PHONY: create-networks

create-log-dirs:
	@mkdir -p $${DEVHOME:-$$HOME}/logs/$(PROJECT_NAME)
	@echo "Log directories created"
.PHONY: create-log-dirs

create-src-dir:
	@mkdir -p wordpress-core/src/themes
	@mkdir -p wordpress-core/src/plugins
	@mkdir -p wordpress-core/src/mu-plugins
	@echo "Source directories created"
.PHONY: create-src-dir

#
# Container management targets
#
build:
	@docker compose -f $(COMPOSE_FILE) --project-name $(PROJECT_NAME) build
	@echo "Docker images built"
.PHONY: build

start: check-env-files create-networks create-log-dirs
	@export DOCKER_PLATFORM=linux/amd64 && \
	docker compose -f $(COMPOSE_FILE) --project-name $(PROJECT_NAME) up -d --remove-orphans
	@echo "WordPress Playground started at https://playground.finder.dev"
.PHONY: start

stop:
	@docker compose -f $(COMPOSE_FILE) --project-name $(PROJECT_NAME) down --volumes --remove-orphans
.PHONY: stop

restart: stop start
.PHONY: restart

status:
	@docker compose -f $(COMPOSE_FILE) --project-name $(PROJECT_NAME) ps
.PHONY: status

logs:
	@docker compose -f $(COMPOSE_FILE) --project-name $(PROJECT_NAME) logs -f
.PHONY: logs

#
# Development targets
#
shell:
	@docker exec -it $(PHP_CONTAINER) /bin/bash
.PHONY: shell

shell-nginx:
	@docker exec -it $(NGINX_CONTAINER) /bin/sh
.PHONY: shell-nginx

composer-install:
	@cd wordpress-core && composer install --no-interaction
.PHONY: composer-install

composer-update:
	@cd wordpress-core && composer update --no-interaction
.PHONY: composer-update

xdebug-enable:
	@if grep -q '^XDEBUG_MODE=' $(LOCAL_FILE) 2>/dev/null; then \
		sed -i '' 's/^XDEBUG_MODE=.*/XDEBUG_MODE=debug/' $(LOCAL_FILE); \
	else \
		echo 'XDEBUG_MODE=debug' >> $(LOCAL_FILE); \
	fi
	@echo "Xdebug enabled. Run 'make restart' to apply."
.PHONY: xdebug-enable

xdebug-disable:
	@if grep -q '^XDEBUG_MODE=' $(LOCAL_FILE) 2>/dev/null; then \
		sed -i '' 's/^XDEBUG_MODE=.*/XDEBUG_MODE=off/' $(LOCAL_FILE); \
	else \
		echo 'XDEBUG_MODE=off' >> $(LOCAL_FILE); \
	fi
	@echo "Xdebug disabled. Run 'make restart' to apply."
.PHONY: xdebug-disable

#
# Database targets
#
db-create:
	@docker exec $(DB_CONTAINER) mysql -u$(DB_USER) -e \
		"CREATE DATABASE IF NOT EXISTS $(DB_NAME) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@echo "Database $(DB_NAME) created (or already exists)"
.PHONY: db-create

db-seed:
	@if [ ! -f db_backups/seed.sql ]; then \
		echo "Error: db_backups/seed.sql not found"; \
		exit 1; \
	fi
	@docker exec $(DB_CONTAINER) mysql -u$(DB_USER) -e \
		"DROP DATABASE IF EXISTS $(DB_NAME); CREATE DATABASE $(DB_NAME) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@docker exec -i $(DB_CONTAINER) mysql -u$(DB_USER) $(DB_NAME) < db_backups/seed.sql
	@echo "Database seeded from db_backups/seed.sql"
.PHONY: db-seed

db-backup:
	@mkdir -p db_backups
	@docker exec $(DB_CONTAINER) mysqldump -u$(DB_USER) $(DB_NAME) > db_backups/backup-$$(date +%Y%m%d-%H%M%S).sql
	@echo "Database backed up to db_backups/backup-$$(date +%Y%m%d-%H%M%S).sql"
.PHONY: db-backup

db-shell:
	@docker exec -it $(DB_CONTAINER) mysql -u$(DB_USER) $(DB_NAME)
.PHONY: db-shell

#
# Cleanup targets
#
clean: stop
	@echo "Cleanup complete (wp-playground network removed by docker-compose down)"
.PHONY: clean

# ===========================================
# Standalone Mode (includes MySQL + Traefik)
# ===========================================
# Use these targets when running without the root dev environment

STANDALONE_COMPOSE_FILE := docker-compose-standalone.yml
STANDALONE_PROJECT_NAME := wp-playground-standalone
STANDALONE_DB_CONTAINER := wp-playground-mysql

standalone-help:
	@echo ""
	@echo "Standalone Mode Commands (includes MySQL + Traefik):"
	@echo "  make standalone-setup   - Initial standalone setup"
	@echo "  make standalone-start   - Start all containers (MySQL, Redis, Traefik, WordPress)"
	@echo "  make standalone-stop    - Stop all containers"
	@echo "  make standalone-restart - Restart all containers"
	@echo "  make standalone-status  - Show container status"
	@echo "  make standalone-logs    - View container logs"
	@echo "  make standalone-shell   - Shell into PHP container"
	@echo "  make standalone-db-shell  - MySQL shell"
	@echo "  make standalone-db-create - Create database"
	@echo "  make standalone-db-seed   - Seed database from db_backups/seed.sql"
	@echo "  make standalone-clean   - Remove containers, networks, and volumes"
	@echo ""
	@echo "Prerequisites:"
	@echo "  1. Generate SSL certs: see traefik/README.md"
	@echo "  2. Add to /etc/hosts: 127.0.0.1 playground.finder.dev"
	@echo ""
.PHONY: standalone-help

standalone-setup: create-env-files standalone-create-log-dirs composer-install create-src-dir
	@echo ""
	@echo "Standalone setup complete!"
	@echo ""
	@echo "Next steps:"
	@echo "  1. Generate SSL certificates (see traefik/README.md)"
	@echo "  2. Add '127.0.0.1 playground.finder.dev' to /etc/hosts"
	@echo "  3. Run 'make standalone-start' to start all containers"
	@echo ""
.PHONY: standalone-setup

standalone-create-log-dirs:
	@mkdir -p logs/nginx logs/php-fpm
	@echo "Log directories created"
.PHONY: standalone-create-log-dirs

standalone-build:
	@docker compose -f $(STANDALONE_COMPOSE_FILE) --project-name $(STANDALONE_PROJECT_NAME) build
	@echo "Docker images built"
.PHONY: standalone-build

standalone-start: check-env-files standalone-create-log-dirs
	@export DOCKER_PLATFORM=linux/amd64 && \
	docker compose -f $(STANDALONE_COMPOSE_FILE) --project-name $(STANDALONE_PROJECT_NAME) up -d --remove-orphans
	@echo ""
	@echo "WordPress Playground (standalone) started!"
	@echo "  - Site: https://playground.finder.dev"
	@echo "  - Traefik Dashboard: http://localhost:8080"
	@echo ""
.PHONY: standalone-start

standalone-stop:
	@docker compose -f $(STANDALONE_COMPOSE_FILE) --project-name $(STANDALONE_PROJECT_NAME) down --remove-orphans
.PHONY: standalone-stop

standalone-restart: standalone-stop standalone-start
.PHONY: standalone-restart

standalone-status:
	@docker compose -f $(STANDALONE_COMPOSE_FILE) --project-name $(STANDALONE_PROJECT_NAME) ps
.PHONY: standalone-status

standalone-logs:
	@docker compose -f $(STANDALONE_COMPOSE_FILE) --project-name $(STANDALONE_PROJECT_NAME) logs -f
.PHONY: standalone-logs

standalone-shell:
	@docker exec -it $(PHP_CONTAINER) /bin/sh
.PHONY: standalone-shell

standalone-db-shell:
	@docker exec -it $(STANDALONE_DB_CONTAINER) mysql -uroot -pabcd1234 $(DB_NAME)
.PHONY: standalone-db-shell

standalone-db-create:
	@docker exec $(STANDALONE_DB_CONTAINER) mysql -uroot -pabcd1234 -e \
		"CREATE DATABASE IF NOT EXISTS $(DB_NAME) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@echo "Database $(DB_NAME) created (or already exists)"
.PHONY: standalone-db-create

standalone-db-seed:
	@if [ ! -f db_backups/seed.sql ]; then \
		echo "Error: db_backups/seed.sql not found"; \
		exit 1; \
	fi
	@docker exec $(STANDALONE_DB_CONTAINER) mysql -uroot -pabcd1234 -e \
		"DROP DATABASE IF EXISTS $(DB_NAME); CREATE DATABASE $(DB_NAME) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@docker exec -i $(STANDALONE_DB_CONTAINER) mysql -uroot -pabcd1234 $(DB_NAME) < db_backups/seed.sql
	@echo "Database seeded from db_backups/seed.sql"
.PHONY: standalone-db-seed

standalone-clean: standalone-stop
	@docker compose -f $(STANDALONE_COMPOSE_FILE) --project-name $(STANDALONE_PROJECT_NAME) down --volumes --remove-orphans
	@echo "Standalone cleanup complete (containers, networks, and volumes removed)"
.PHONY: standalone-clean
