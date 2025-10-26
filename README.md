# AI Chess Openings

An AI-powered chess openings application built with Laravel and Hexagonal Architecture.

## Technologies

### Backend
- **PHP 8.4** - Latest PHP version with modern features
- **Laravel 12** - Web application framework
- **Laravel Octane** - High-performance application server using FrankenPHP
- **PostgreSQL** - Primary database
- **Redis** - Caching and queue backend

### Frontend
- **React** - Modern JavaScript library for building user interfaces
- **Inertia.js** - Modern monolith approach connecting Laravel and React
- **shadcn/ui** - Re-usable component library built with Radix UI and Tailwind
- **Vite** - Frontend build tool
- **Tailwind CSS 4** - Utility-first CSS framework

### Development & Testing
- **Docker & Docker Compose** - Containerized development environment
- **Pest 4** - Testing framework with browser testing support
- **PHPStan/Larastan** - Static analysis at maximum strictness
- **Laravel Pint** - Code formatter
- **Rector** - Automated refactoring

## Getting Started

### Prerequisites
- Docker and Docker Compose installed
- Make (usually pre-installed on macOS/Linux)

### Initial Setup

Complete development setup in one command:

```bash
make setup
```

This will:
- Create `.env` file if it doesn't exist
- Build Docker containers
- Install PHP and npm dependencies
- Set up Laravel Octane with FrankenPHP
- Run database migrations
- Build frontend assets

**Access the application at: http://localhost**

### Common Commands

#### Container Management
```bash
make start          # Start all containers
make stop           # Stop all containers
make restart        # Restart all containers
make logs           # View logs from all containers
make shell          # Access app container shell
```

#### Laravel Commands
```bash
make artisan cmd="route:list"    # Run artisan command
make migrate                      # Run migrations
make fresh                        # Fresh database with seeders
make tinker                       # Open Laravel Tinker
```

#### Development
```bash
make npm-dev        # Run Vite dev server
make npm-build      # Build frontend assets
make format         # Format code with Pint
make test           # Run all tests
```

#### Database
```bash
make db             # Access PostgreSQL database
make redis-cli      # Access Redis CLI
```

### Available Commands

See all available commands:

```bash
make help
```

## Architecture

This project follows **Hexagonal Architecture** (Ports and Adapters) principles to maintain clean separation of concerns and testability.

## License

This project is open-source software.
