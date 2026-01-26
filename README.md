# WordPress Playground

A local WordPress development environment for testing and experimentation. Uses Docker containers for nginx and PHP-FPM with WordPress installed via Composer.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Traefik (external)                      │
│                 https://playground.finder.dev                │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│   wp-playground-nginx   │     │  wp-playground-php-fpm  │
│      (nginx:alpine)     │────▶│       (php:8.3-fpm)     │
└─────────────────────────┘     └─────────────────────────┘
                                          │
                    ┌─────────────────────┼─────────────────────┐
                    ▼                     ▼                     ▼
          ┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
          │  coeval-mysql   │   │      redis      │   │   Log Files     │
          │   (external)    │   │   (external)    │   │  ~/logs/wp-...  │
          └─────────────────┘   └─────────────────┘   └─────────────────┘
```

## Directory Structure

```
wordpress-playground/
├── config/                          # Configuration files
│   ├── playground.defaults.env      # Default environment variables
│   ├── playground.secrets.env       # Secrets (git-ignored, create from .example)
│   ├── playground.local.env         # Local overrides (git-ignored)
│   └── xdebug.ini                   # Xdebug configuration
├── docker/
│   ├── nginx/Dockerfile             # Production nginx image
│   └── php-fpm/Dockerfile           # Production PHP-FPM image (if exists)
├── nginx/
│   ├── nginx-site.conf              # Main nginx configuration
│   └── includes/
│       └── wordpress.conf           # WordPress-specific nginx rules
├── wordpress-core/
│   ├── composer.json                # Composer dependencies (WordPress, etc.)
│   ├── composer.lock                # Locked dependency versions
│   ├── vendor/                      # Composer packages (generated, git-ignored)
│   ├── wordpress/                   # WordPress core (generated, git-ignored)
│   └── src/                         # Custom wp-content (themes, plugins)
│       ├── themes/
│       ├── plugins/
│       └── mu-plugins/
├── www/                             # Webroot files (mounted to /var/www/src/)
│   ├── index.php                    # WordPress entry point
│   ├── wp-config.php                # WordPress configuration
│   ├── robots.txt                   # Robots file
│   └── status.php                   # Health check endpoint
├── docker-compose.yml               # Docker Compose configuration
├── Makefile                         # Make commands
└── Taskfile.yml                     # Task commands (alternative to Make)
```

### Container File Structure

Inside the containers, files are mounted to:

```
/var/www/src/
├── index.php              # From www/
├── wp-config.php          # From www/
├── robots.txt             # From www/
├── status.php             # From www/
├── wordpress/             # From wordpress-core/wordpress/
├── wp-content/            # From wordpress-core/src/
└── vendor/                # From wordpress-core/vendor/
```

## Prerequisites

### Required Software

- **Docker** (with Docker Compose v2)
- **Composer** (for PHP dependency management)
- **Make** or **Task** (task runner)

### External Services

This project depends on external Docker services that must be running:

| Service | Container Name | Network | Purpose |
|---------|---------------|---------|---------|
| MySQL 8.x | `coeval-mysql` | `mysql` | Database |
| Redis | `redis` | `redis` | Object caching |
| Traefik | traefik | `traefik` | HTTPS reverse proxy |

These are typically provided by a parent development environment (e.g., the main Finder dev setup).

### DNS Configuration

Add to `/etc/hosts`:

```
127.0.0.1 playground.finder.dev
```

Or configure your local DNS resolver accordingly.

### Docker Registry Access

The Docker images are pulled from a private Google Artifact Registry. Ensure you have access:

```bash
gcloud auth configure-docker australia-southeast1-docker.pkg.dev
```

## Setup Instructions

### Quick Start

```bash
# 1. Clone the repository
git clone <repository-url>
cd wordpress-playground

# 2. Create secrets file from example
cp config/playground.secrets.env.example config/playground.secrets.env
# Edit the file and add your authentication keys

# 3. Run initial setup (installs WordPress and dependencies via Composer)
make setup
# Or: task setup

# 4. Ensure external services are running
# (coeval-mysql, redis, traefik from parent dev environment)

# 5. Build Docker images (first time only)
make build

# 6. Start the containers
make start
# Or: task start

# 7. Seed the database (or use db-create for fresh install)
make db-seed
# Or: make db-create for a fresh WordPress install

# 8. Access WordPress
# Site: https://playground.finder.dev/
# Admin: https://playground.finder.dev/wordpress/wp-admin/
```

**Note:** The `make setup` command runs `composer install` which downloads WordPress core and PHP dependencies into `wordpress-core/wordpress/` and `wordpress-core/vendor/`. These directories are git-ignored and must be generated locally.

### Detailed Setup

#### Step 1: Environment Files

The setup creates two empty environment files:

- `config/playground.secrets.env` - For authentication keys and secrets
- `config/playground.local.env` - For local overrides

Copy the example secrets file and generate authentication keys:

```bash
cp config/playground.secrets.env.example config/playground.secrets.env
```

Generate keys at: https://api.wordpress.org/secret-key/1.1/salt/

#### Step 2: Run Setup

```bash
make setup
```

This command:
- Creates environment files (if missing)
- Creates Docker networks
- Creates log directories
- Installs Composer dependencies
- Creates the wp-content source directories

#### Step 3: Start Containers

```bash
make start
```

Verify containers are running:

```bash
make status
```

#### Step 4: Create Database

```bash
make db-create
```

#### Step 5: Complete WordPress Installation

1. Navigate to https://playground.finder.dev/
2. Follow the WordPress installation wizard
3. Create your admin account

## Standalone Mode (Self-Contained Environment)

If you don't have access to the parent development environment (coeval-mysql, redis, traefik), you can run WordPress Playground in standalone mode, which includes all dependencies.

### Standalone Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    wp-playground-standalone                      │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                   wp-playground-traefik                     │ │
│  │              https://playground.finder.dev:443              │ │
│  │                Dashboard: http://localhost:8080             │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│              ┌───────────────┴───────────────┐                  │
│              ▼                               ▼                  │
│  ┌─────────────────────────┐     ┌─────────────────────────┐   │
│  │   wp-playground-nginx   │     │  wp-playground-php-fpm  │   │
│  │      (nginx:alpine)     │────▶│       (php:8.3-fpm)     │   │
│  └─────────────────────────┘     └─────────────────────────┘   │
│                                          │                      │
│                    ┌─────────────────────┼─────────────────┐   │
│                    ▼                     ▼                 │   │
│          ┌─────────────────┐   ┌─────────────────┐        │   │
│          │wp-playground-   │   │wp-playground-   │        │   │
│          │     mysql       │   │     redis       │        │   │
│          │    :3308        │   │    :6380        │        │   │
│          └─────────────────┘   └─────────────────┘        │   │
└─────────────────────────────────────────────────────────────────┘
```

### Standalone Prerequisites

- **Docker** (with Docker Compose v2)
- **Composer** (for PHP dependency management)
- **mkcert** (recommended for SSL certificates) or **OpenSSL**

### Standalone Quick Start

```bash
# 1. Run standalone setup
make standalone-setup

# 2. Generate SSL certificates
cd traefik/ssl

# Option A: Using mkcert (recommended - creates trusted certs)
brew install mkcert        # macOS
mkcert -install            # Install local CA (one-time)
mkcert playground.finder.dev
mv playground.finder.dev.pem playground.crt
mv playground.finder.dev-key.pem playground.key

# Option B: Using OpenSSL (browser will show security warning)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout playground.key -out playground.crt \
  -subj "/CN=playground.finder.dev"

cd ../..

# 3. Add DNS entry
echo "127.0.0.1 playground.finder.dev" | sudo tee -a /etc/hosts

# 4. Start all services
make standalone-start

# 5. Create database (auto-created, but run if needed)
make standalone-db-create

# 6. Access WordPress
# Site: https://playground.finder.dev/
# Traefik Dashboard: http://localhost:8080
```

### Standalone Commands

```bash
make standalone-help       # Show all standalone commands

# Lifecycle
make standalone-setup      # Initial setup
make standalone-start      # Start all containers
make standalone-stop       # Stop all containers  
make standalone-restart    # Restart containers
make standalone-status     # Show container status
make standalone-logs       # View logs (follow mode)

# Development
make standalone-shell      # Shell into PHP container
make standalone-db-shell   # MySQL shell
make standalone-db-create  # Create database

# Cleanup
make standalone-clean      # Remove containers, networks, and volumes
```

### Standalone vs Standard Mode

| Feature | Standard Mode | Standalone Mode |
|---------|--------------|-----------------|
| Compose file | `docker-compose.yml` | `docker-compose-standalone.yml` |
| MySQL | External (`coeval-mysql`) | Included (`wp-playground-mysql`) |
| Redis | External (`redis`) | Included (`wp-playground-redis`) |
| Traefik | External (`traefik`) | Included (`wp-playground-traefik`) |
| SSL Certs | Shared from parent | Generate locally |
| MySQL Port | 3308 (external) | 3308 |
| Redis Port | 6379 (external) | 6380 |
| Traefik Dashboard | N/A | http://localhost:8080 |

### Switching Between Modes

To switch from standalone to standard mode (or vice versa):

```bash
# Stop current mode
make standalone-stop   # if using standalone
# OR
make stop              # if using standard

# Start the other mode
make start             # for standard mode
# OR
make standalone-start  # for standalone mode
```

**Note:** Both modes use the same database name (`wp_playground_db`) but different MySQL containers. Data is not shared between modes.

## Available Commands

### Using Make

```bash
make help              # Show all available commands

# Setup
make setup             # Full initial setup
make create-env-files  # Create env files only
make check-env-files   # Verify env files exist

# Container Management
make start             # Start containers
make stop              # Stop containers
make restart           # Restart containers
make status            # Show container status
make logs              # View logs (follow mode)

# Development
make shell             # Shell into PHP container
make shell-nginx       # Shell into nginx container
make composer-install  # Install Composer dependencies
make composer-update   # Update Composer dependencies

# Database
make db-create         # Create database
make db-shell          # Open MySQL shell

# Debugging
make xdebug-enable     # Enable Xdebug
make xdebug-disable    # Disable Xdebug

# Cleanup
make clean             # Remove containers and networks
```

### Using Task

```bash
task                   # List all tasks
task setup             # Full initial setup
task start             # Start containers
task stop              # Stop containers
task restart           # Restart containers
task logs              # View logs
task logs -- -f        # View logs (follow mode)
task shell             # Shell into PHP container
task db-create         # Create database
task db-shell          # Open MySQL shell
task xdebug-enable     # Enable Xdebug
task xdebug-disable    # Disable Xdebug
task clean             # Remove containers and networks
```

## Development

### Adding Themes or Plugins

Custom themes and plugins go in `wordpress-core/src/`:

```
wordpress-core/src/
├── themes/           # Custom themes
├── plugins/          # Custom plugins
└── mu-plugins/       # Must-use plugins
```

These are mounted to `/var/www/src/wp-content/` in the container.

### Using Xdebug

1. Enable Xdebug:
   ```bash
   make xdebug-enable
   make restart
   ```

2. Configure your IDE:
   - Host: `host.docker.internal`
   - Port: `9003`
   - IDE Key: `PHPSTORM`
   - Path mapping: `./` → `/var/www/src/`

3. Disable when not needed (improves performance):
   ```bash
   make xdebug-disable
   make restart
   ```

### Accessing Logs

Container logs:
```bash
make logs
```

File-based logs are written to:
```
~/logs/wp-playground/
├── access.log
├── error.log
└── php-fpm.log
```

Or use `$DEVHOME/logs/wp-playground/` if `DEVHOME` is set.

## Troubleshooting

### Containers won't start

1. Check if external services are running:
   ```bash
   docker ps | grep -E "coeval-mysql|redis|traefik"
   ```

2. Check Docker networks exist:
   ```bash
   docker network ls | grep -E "mysql|redis|traefik"
   ```

3. View container logs:
   ```bash
   make logs
   ```

### Database connection errors

1. Verify MySQL container is running:
   ```bash
   docker ps | grep coeval-mysql
   ```

2. Verify database exists:
   ```bash
   make db-shell
   # Then: SHOW DATABASES;
   ```

3. Create database if missing:
   ```bash
   make db-create
   ```

### "Site can't be reached" in browser

1. Check hosts file has the entry:
   ```bash
   grep playground.finder.dev /etc/hosts
   ```

2. Verify Traefik is routing correctly:
   ```bash
   docker logs traefik 2>&1 | grep playground
   ```

3. Check nginx container is healthy:
   ```bash
   curl -k https://localhost/status.php
   ```

### Permission errors

If you see permission errors on mounted volumes:

```bash
# Check file ownership
ls -la wordpress-core/src/

# The containers run as www-data (UID 33 typically)
# Adjust permissions if needed
chmod -R 775 wordpress-core/src/
```

### Composer errors

```bash
# Clear Composer cache and reinstall
cd wordpress-core
rm -rf vendor composer.lock
composer install
```

## Environment Variables

Key variables in `config/playground.defaults.env`:

| Variable | Default | Description |
|----------|---------|-------------|
| `WP_HOME` | `https://playground.finder.dev` | Site URL |
| `WP_SITEURL` | `/wordpress` | WordPress core location |
| `WP_DEBUG` | `true` | Enable debugging |
| `DB_HOST` | `coeval-mysql` | Database host |
| `DB_NAME` | `wp_playground_db` | Database name |
| `XDEBUG_MODE` | `off` | Xdebug mode |

Override any variable in `config/playground.local.env`.

## URLs

- **Site Home**: https://playground.finder.dev/
- **WordPress Admin**: https://playground.finder.dev/wordpress/wp-admin/
- **Status Check**: https://playground.finder.dev/status.php

## Additional Resources

- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Detailed guide for common issues and their solutions

## Notes

### MySQL Password Configuration

The default configuration assumes MySQL root user has password `abcd1234`. If your `coeval-mysql` container uses a different password (or no password), update `config/playground.local.env`:

```bash
# For no password
echo "DB_PASS=" >> config/playground.local.env

# For a different password
echo "DB_PASS=your-password" >> config/playground.local.env
```

Then restart: `make restart`
