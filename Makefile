mak.PHONY: help
.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[36m
YELLOW := \033[33m
GREEN := \033[32m
RED := \033[31m
RESET := \033[0m

help: ## Show this help message
	@echo ''
	@echo '$(BLUE)Available commands:$(RESET)'
	@echo ''
	@awk 'BEGIN {FS = ":.*?## "; section=""} \
		/^## / {section=$$0; gsub(/^## /, "", section); printf "\n$(YELLOW)%s$(RESET)\n", section; next} \
		/^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-20s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ''

## Setup & Installation
setup: ## Complete development setup (first time)
	@echo "$(BLUE)Starting development setup...$(RESET)"
	@if [ ! -f .env ]; then \
		echo "$(YELLOW)Creating .env file...$(RESET)"; \
		cp .env.example .env; \
	fi
	@echo "$(YELLOW)Building containers...$(RESET)"
	docker compose build
	@echo "$(YELLOW)Starting containers...$(RESET)"
	docker compose up -d
	@echo "$(YELLOW)Installing dependencies...$(RESET)"
	docker compose exec app composer install --no-interaction --prefer-dist
	docker compose exec app npm install --no-audit
	@echo "$(YELLOW)Setting up Laravel...$(RESET)"
	docker compose exec app php artisan key:generate
	@echo "$(YELLOW)Installing Laravel Octane...$(RESET)"
	docker compose exec app composer require laravel/octane
	docker compose exec app php artisan octane:install --server=frankenphp --no-interaction
	@echo "$(YELLOW)Running migrations...$(RESET)"
	docker compose exec app php artisan migrate --force
	@echo "$(YELLOW)Building assets...$(RESET)"
	docker compose exec app npm run build
	@echo "$(GREEN)Setup complete! Restarting containers...$(RESET)"
	docker compose restart app
	@echo "$(GREEN)✓ Development environment ready!$(RESET)"
	@echo "$(BLUE)Access your app at: http://localhost$(RESET)"

setup-prod: ## Complete production setup
	@echo "$(BLUE)Starting production setup...$(RESET)"
	@if [ ! -f .env ]; then \
		echo "$(RED)Error: .env file not found. Create it from .env.example$(RESET)"; \
		exit 1; \
	fi
	@echo "$(YELLOW)Building production containers...$(RESET)"
	docker compose -f docker-compose.prod.yml build
	@echo "$(YELLOW)Starting production containers...$(RESET)"
	docker compose -f docker-compose.prod.yml up -d
	@echo "$(YELLOW)Running migrations...$(RESET)"
	docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
	@echo "$(GREEN)✓ Production environment ready!$(RESET)"

## Container Management
build: ## Build development containers
	docker compose build

build-prod: ## Build production containers
	docker compose -f docker-compose.prod.yml build

start: ## Start development containers
	docker compose up -d
	@echo "$(GREEN)✓ Containers started$(RESET)"

start-prod: ## Start production containers
	docker compose -f docker-compose.prod.yml up -d
	@echo "$(GREEN)✓ Production containers started$(RESET)"

stop: ## Stop all containers
	docker compose down
	@echo "$(GREEN)✓ Containers stopped$(RESET)"

stop-prod: ## Stop production containers
	docker compose -f docker-compose.prod.yml down
	@echo "$(GREEN)✓ Production containers stopped$(RESET)"

restart: ## Restart all containers
	docker compose restart
	@echo "$(GREEN)✓ Containers restarted$(RESET)"

restart-app: ## Restart only app container
	docker compose restart app
	@echo "$(GREEN)✓ App container restarted$(RESET)"

restart-worker: ## Restart only worker container
	docker compose restart worker
	@echo "$(GREEN)✓ Worker container restarted$(RESET)"

ps: ## Show container status
	docker compose ps

clean: ## Stop containers and remove volumes
	@echo "$(RED)This will delete all data in volumes. Continue? [y/N]$(RESET)" && read ans && [ $${ans:-N} = y ]
	docker compose down -v
	@echo "$(GREEN)✓ Containers and volumes removed$(RESET)"

rebuild: ## Rebuild and restart containers
	docker compose down
	docker compose build --no-cache
	docker compose up -d
	@echo "$(GREEN)✓ Containers rebuilt$(RESET)"

## Logs
logs: ## View logs from all containers
	docker compose logs -f

logs-app: ## View app logs
	docker compose logs -f app

logs-worker: ## View worker logs
	docker compose logs -f worker

logs-postgres: ## View postgres logs
	docker compose logs -f postgres

logs-redis: ## View redis logs
	docker compose logs -f redis

## Shell Access
shell: ## Access app container shell
	docker compose exec app bash

shell-worker: ## Access worker container shell
	docker compose exec worker bash

shell-root: ## Access app container as root
	docker compose exec -u root app bash

db: ## Access PostgreSQL database
	docker compose exec postgres psql -U postgres -d ai_chess

redis-cli: ## Access Redis CLI
	docker compose exec redis redis-cli

## Laravel Commands
artisan: ## Run artisan command (usage: make artisan cmd="route:list")
	docker compose exec app php artisan $(cmd)

tinker: ## Run Laravel Tinker
	docker compose exec app php artisan tinker

migrate: ## Run database migrations
	docker compose exec app php artisan migrate

migrate-fresh: ## Fresh database migrations
	docker compose exec app php artisan migrate:fresh

fresh: ## Fresh database with seeders
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker compose exec app php artisan db:seed

rollback: ## Rollback last migration
	docker compose exec app php artisan migrate:rollback

optimize: ## Optimize Laravel (cache config, routes, views)
	docker compose exec app php artisan optimize
	@echo "$(GREEN)✓ Laravel optimized$(RESET)"

clear: ## Clear all Laravel caches
	docker compose exec app php artisan optimize:clear
	@echo "$(GREEN)✓ Caches cleared$(RESET)"

queue-restart: ## Restart queue workers
	docker compose exec app php artisan queue:restart
	docker compose restart worker
	@echo "$(GREEN)✓ Queue workers restarted$(RESET)"

queue-work: ## Run queue worker (foreground)
	docker compose exec app php artisan queue:work

queue-failed: ## List failed queue jobs
	docker compose exec app php artisan queue:failed

## Dependency Management
composer: ## Run composer command (usage: make composer cmd="install")
	docker compose exec app composer $(cmd)

composer-install: ## Install composer dependencies
	docker compose exec app composer install

composer-update: ## Update composer dependencies
	docker compose exec app composer update

npm: ## Run npm command (usage: make npm cmd="install")
	docker compose exec app npm $(cmd)

npm-install: ## Install npm dependencies
	docker compose exec app npm install

npm-update: ## Update npm dependencies
	docker compose exec app npm update

npm-dev: ## Run Vite dev server
	docker compose exec app npm run dev

npm-build: ## Build frontend assets
	docker compose exec app npm run build

## Testing & Quality
test: ## Run all tests
	docker compose exec app php artisan test

test-filter: ## Run specific test (usage: make test-filter filter="TestName")
	docker compose exec app php artisan test --filter=$(filter)

test-coverage: ## Run tests with coverage
	docker compose exec app php artisan test --coverage

test-parallel: ## Run tests in parallel
	docker compose exec app php artisan test --parallel

format: ## Format code with Pint
	docker compose exec app vendor/bin/pint

format-test: ## Check code formatting (dry-run)
	docker compose exec app vendor/bin/pint --test

lint: ## Run all linters (Pint + npm lint)
	docker compose exec app vendor/bin/pint
	docker compose exec app npm run lint

## Octane
octane-status: ## Check Octane server status
	docker compose exec app php artisan octane:status

octane-reload: ## Reload Octane server
	docker compose exec app php artisan octane:reload

octane-stop: ## Stop Octane server
	docker compose exec app php artisan octane:stop

## Development Helpers
watch: ## Watch logs from app and worker
	docker compose logs -f app worker

stats: ## Show Docker container stats
	docker stats

prune: ## Remove unused Docker resources
	docker system prune -f
	@echo "$(GREEN)✓ Docker resources pruned$(RESET)"

install-octane: ## Install and configure Laravel Octane
	docker compose exec app composer require laravel/octane
	docker compose exec app php artisan octane:install --server=frankenphp --no-interaction
	@echo "$(GREEN)✓ Octane installed. Run 'make restart' to apply changes.$(RESET)"

dump-autoload: ## Regenerate Composer autoload files
	docker compose exec app composer dump-autoload

key-generate: ## Generate new application key
	docker compose exec app php artisan key:generate

storage-link: ## Create storage symbolic link
	docker compose exec app php artisan storage:link

## Production Commands
prod-logs: ## View production logs
	docker compose -f docker-compose.prod.yml logs -f

prod-shell: ## Access production app shell
	docker compose -f docker-compose.prod.yml exec app bash

prod-optimize: ## Optimize for production
	docker compose -f docker-compose.prod.yml exec app php artisan optimize
	@echo "$(GREEN)✓ Production optimized$(RESET)"

prod-migrate: ## Run production migrations
	docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

prod-restart: ## Restart production containers
	docker compose -f docker-compose.prod.yml restart
	@echo "$(GREEN)✓ Production containers restarted$(RESET)"
