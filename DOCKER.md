# Docker Setup Guide

This application uses Docker with FrankenPHP and Laravel Octane for high-performance PHP execution.

## Stack

- **FrankenPHP + Laravel Octane**: High-performance PHP server
- **PostgreSQL 17**: Database
- **Redis 7**: Cache, sessions, and queue driver
- **Node.js 20**: Frontend build tools
- **Vite**: Hot module replacement for frontend development

## Features

- Hot reload for frontend (Vite)
- Code changes auto-reload in container (volume mounting)
- XDebug debugging support
- HTTPS support (via FrankenPHP/Caddy)
- Separate development and production configurations

## Quick Start

### First Time Setup

```bash
# Complete development setup (one command does everything!)
make setup
```

This command will:
- Create `.env` file from `.env.example`
- Build Docker containers
- Start all services
- Install PHP and Node dependencies
- Install and configure Laravel Octane
- Run database migrations
- Build frontend assets
- Restart containers with Octane configured

**That's it!** Your app is ready at http://localhost

### Daily Development Workflow

```bash
# Start containers
make start

# View logs
make logs

# Access container shell
make shell

# Run migrations
make migrate

# Run tests
make test

# Stop containers
make stop
```

## Available Commands

The Makefile includes 60+ commands organized into categories. Run `make help` or just `make` to see all available commands:

```bash
make help
```

### Key Commands by Category

#### Setup & Installation
- `make setup` - Complete development setup (first time)
- `make setup-prod` - Complete production setup

#### Container Management
- `make build` - Build development containers
- `make start` - Start containers
- `make stop` - Stop containers
- `make restart` - Restart containers
- `make ps` - Show container status
- `make clean` - Remove containers and volumes (destructive)
- `make rebuild` - Rebuild from scratch

#### Logs
- `make logs` - All logs
- `make logs-app` - App logs only
- `make logs-worker` - Worker logs only
- `make watch` - Watch app and worker logs

#### Shell Access
- `make shell` - Access app container
- `make db` - Access PostgreSQL
- `make redis-cli` - Access Redis

#### Laravel Commands
- `make migrate` - Run migrations
- `make fresh` - Fresh database with seeds
- `make tinker` - Laravel Tinker
- `make optimize` - Optimize Laravel
- `make clear` - Clear all caches
- `make artisan cmd="..."` - Run any artisan command

#### Testing & Quality
- `make test` - Run all tests
- `make test-coverage` - Tests with coverage
- `make format` - Format code with Pint
- `make lint` - Run all linters

#### Queue Management
- `make queue-restart` - Restart queue workers
- `make queue-work` - Run worker in foreground
- `make queue-failed` - List failed jobs

#### Dependencies
- `make composer-install` - Install PHP deps
- `make npm-install` - Install Node deps
- `make npm-build` - Build frontend
- `make npm-dev` - Vite dev server

#### Production
- `make setup-prod` - Production setup
- `make build-prod` - Build production images
- `make start-prod` - Start production
- `make prod-optimize` - Optimize for production

## Environment Configuration

The Docker setup uses these environment variables (configured in `docker-compose.yml`):

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ai_chess
DB_USERNAME=postgres
DB_PASSWORD=postgres

REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

OCTANE_SERVER=frankenphp
```

## Services

### App Service
- **Container**: `ai-chess-app`
- **Ports**: 80 (HTTP), 443 (HTTPS), 5173 (Vite)
- **Command**: `php artisan octane:frankenphp --watch`
- **Features**: Auto-reload on code changes, XDebug enabled

### Worker Service
- **Container**: `ai-chess-worker`
- **Command**: `php artisan queue:work`
- **Features**: Processes queued jobs from Redis

### PostgreSQL Service
- **Container**: `ai-chess-postgres`
- **Port**: 5432
- **Database**: `ai_chess`
- **Data**: Persisted in `postgres_data` volume

### Redis Service
- **Container**: `ai-chess-redis`
- **Port**: 6379
- **Data**: Persisted in `redis_data` volume

### Vite Service
- **Container**: `ai-chess-vite`
- **Port**: 5173
- **Features**: Hot module replacement for frontend

## XDebug Configuration

XDebug is pre-configured in the development container for debugging.

### IntelliJ IDEA / PhpStorm Setup

#### Step 1: Configure PHP Interpreter

1. Go to **Settings/Preferences** → **PHP**
2. Click the **...** button next to **CLI Interpreter**
3. Click the **+** button and select **From Docker, Vagrant, VM, WSL, Remote...**
4. Select **Docker Compose**
5. Choose your `docker-compose.yml` file
6. Select the `app` service
7. Click **OK**

#### Step 2: Configure Path Mappings

1. In **Settings/Preferences** → **PHP** → **Servers**
2. Click **+** to add a new server
3. Configure:
   - **Name**: `ai-chess` (must match `PHP_IDE_CONFIG` env var)
   - **Host**: `localhost`
   - **Port**: `80`
   - **Debugger**: `Xdebug`
   - Check **Use path mappings**
4. Map your project root to `/app`:
   ```
   Project files → /app
   ```

#### Step 3: Configure Debug Settings

1. Go to **Settings/Preferences** → **PHP** → **Debug**
2. Set **Debug port** to `9003`
3. Ensure the following are checked:
   - **Can accept external connections**
   - **Break at first line in PHP scripts** (optional, for testing)

#### Step 4: Start Debugging

1. Click the **Start Listening for PHP Debug Connections** button (phone icon) in the toolbar
2. Set a breakpoint in your code
3. Access your application in the browser (http://localhost)
4. IntelliJ should break at your breakpoint

### XDebug Environment Variables

The following XDebug settings are configured in `docker-compose.yml`:

```yaml
XDEBUG_MODE=debug
XDEBUG_CONFIG=client_host=host.docker.internal client_port=9003 start_with_request=yes
PHP_IDE_CONFIG=serverName=ai-chess
```

### Troubleshooting XDebug

#### XDebug not connecting

1. Ensure you're listening for debug connections (phone icon in toolbar)
2. Check that port 9003 is not blocked by firewall
3. Verify the server name matches: `ai-chess`
4. View XDebug logs in container:
   ```bash
   make shell
   cat /tmp/xdebug.log
   ```

#### Check XDebug is loaded

```bash
make shell
php -v
# Should show: "with Xdebug v3.x.x"
```

## Hot Reload (Frontend)

Vite runs in a separate container and provides hot module replacement:

1. Frontend changes are automatically detected
2. Browser updates without full page refresh
3. Access Vite dev server at http://localhost:5173

## HTTPS Support

FrankenPHP includes Caddy, which automatically provides HTTPS:

- Development: Self-signed certificates (browser warning expected)
- Production: Automatic Let's Encrypt certificates (configure domain in production)

Access your app at https://localhost (accept self-signed certificate in browser)

## Production Deployment

### Build and Run Production

```bash
# Build production images
make prod-build

# Start production containers
make prod-up

# View logs
make prod-logs
```

### Production Differences

- No XDebug (performance)
- No code volume mounting (uses code baked into image)
- Optimized autoloader
- Cached routes, config, and views
- No Vite dev server (built assets only)
- Redis password protection

### Production Environment

Set these variables in your production `.env` or environment:

```env
DB_PASSWORD=<secure-password>
REDIS_PASSWORD=<secure-password>
APP_ENV=production
APP_DEBUG=false
```

## Common Tasks

### Reset Database

```bash
make fresh
```

### Access PostgreSQL

```bash
make db
```

### Access Redis CLI

```bash
make redis-cli
```

### View Worker Logs

```bash
make logs-worker
```

### Restart Specific Service

```bash
make restart-app
make restart-worker
```

### Run Queue Worker

```bash
# Background (default in worker container)
make queue-restart

# Foreground (for debugging)
make queue-work
```

### Check Container Status

```bash
make ps
make stats  # With resource usage
```

### Format Code

```bash
make format
```

### Run Specific Test

```bash
make test-filter filter="MyTestName"
```

## Volumes

The following volumes persist data:

- `postgres_data`: PostgreSQL database
- `redis_data`: Redis data
- `vendor`: PHP dependencies (speeds up container builds)
- `node_modules`: Node.js dependencies

### Clean Volumes

```bash
# Stop containers and remove all volumes (will prompt for confirmation)
make clean
```

Or manually:

```bash
docker compose down -v
```

## Performance Tips

1. **Volume mounting**: Vendor and node_modules are in named volumes for better performance
2. **Octane watch mode**: Auto-reloads PHP code changes
3. **Redis**: Used for cache, sessions, and queues
4. **FrankenPHP**: Built on Caddy's high-performance server

## Debugging Tips

### Check Service Health

```bash
docker compose ps
```

### View All Logs

```bash
make logs
```

### Check Octane Status

```bash
make artisan cmd="octane:status"
```

### Restart Octane

```bash
make artisan cmd="octane:reload"
```

## Quick Reference

Most commonly used commands:

```bash
# First time setup
make setup

# Daily workflow
make start          # Start containers
make stop           # Stop containers
make logs           # View logs
make shell          # Access container
make test           # Run tests

# Development
make migrate        # Run migrations
make fresh          # Reset database with seeds
make tinker         # Laravel Tinker
make format         # Format code

# Debugging
make logs-app       # App logs
make logs-worker    # Worker logs
make queue-failed   # Failed jobs
make stats          # Container stats

# Production
make setup-prod     # Production setup
make build-prod     # Build production
make start-prod     # Start production
```

For all commands: `make help`

## Additional Resources

- [Laravel Octane Documentation](https://laravel.com/docs/octane)
- [FrankenPHP Documentation](https://frankenphp.dev)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
