<p align="center">
  <img src="controller/public/favicon.svg" alt="Open Looking Glass" width="80" height="80">
</p>

<h1 align="center">Open Looking Glass</h1>

<p align="center">
  A modern, open-source, multi-location Looking Glass platform for network operators, ISPs, data-centers, and hosting providers.
</p>

<p align="center">
  <a href="https://github.com/mdabubakkarhq/Looking-Glass/blob/main/LICENSE"><img alt="License" src="https://img.shields.io/badge/License-Apache%202.0-blue.svg"></a>
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3+-777BB4.svg">
  <img alt="Vue" src="https://img.shields.io/badge/Vue.js-3.5+-4FC08D.svg">
  <img alt="Go" src="https://img.shields.io/badge/Go-1.22+-00ADD8.svg">
  <img alt="CI" src="https://github.com/mdabubakkarhq/Looking-Glass/actions/workflows/ci.yml/badge.svg">
</p>

---

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [System Requirements](#system-requirements)
- [Quick Start](#quick-start--one-command-install)
- [Installation Methods](#installation-methods)
- [Configuration](#configuration)
- [Agent Deployment](#agent-deployment)
- [Upgrading & Rollback](#upgrading--rollback)
- [Project Structure](#project-structure)
- [Testing](#testing)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

---

## Features

| Category | Feature |
|---|---|
| **Network Tests** | Ping, Traceroute, MTR, DNS Lookup — all with IPv4 and IPv6 support |
| **Live Streaming** | Real-time command output via Server-Sent Events (SSE) |

---

## Architecture

```
┌──────────────┐      ┌────────────────────────┐      ┌────────────────────┐
│   Visitor    │ ───▶ │   NGINX + Vue 3 SPA    │ ───▶ │  Laravel API       │
│  (Browser)   │◀ SSE │   (TypeScript, Vite)    │      │  (PHP 8.3+)        │
└──────────────┘      └────────────────────────┘      │  PostgreSQL/SQLite  │
                                                       │  Redis              │
                                                       └────────┬───────────┘
                                                                │ HMAC-signed
                                                                │ REST + SSE
                                                       ┌────────▼───────────┐
                                                       │  Go Agent          │
                                                       │  (Remote Node)     │
                                                       │  • ping/traceroute │
                                                       │  • mtr / dns       │
                                                       │  • iperf3          │
                                                       │  • file downloads  │
                                                       └────────────────────┘
```

---

## System Requirements

### Controller (Central Server)

| Component | Minimum | Recommended |
|---|---|---|
| **OS** | Ubuntu 24.04 or Debian 12 | Ubuntu 24.04 LTS |
| **CPU** | 1 vCPU | 2 vCPU |
| **RAM** | 1 GB | 2 GB |
| **Disk** | 10 GB | 20 GB SSD |
| **PHP** | 8.3+ | 8.3 or 8.4 |
| **Database** | PostgreSQL 15+ / MariaDB 10.11+ / SQLite 3.35+ | PostgreSQL 16 |
| **Cache/Queue** | Redis 7+ | Redis 7 |
| **Web Server** | NGINX 1.24+ | NGINX |
| **Node.js** | 20+ (build only) | 20 LTS |
| **Composer** | 2.x | Latest |
| **Network** | Public IP | Public IPv4 + IPv6 |

### Remote Node Agent

| Component | Minimum |
|---|---|
| **OS** | Any modern Linux (Ubuntu, Debian, CentOS, etc.) |
| **CPU** | 1 vCPU |
| **RAM** | 64 MB |
| **Disk** | 50 MB |
| **Dependencies** | None — single statically-linked Go binary |
| **Network** | Public IP reachable from controller |
| **Tools** | `ping`, `traceroute`, `mtr`, `dig`, `iperf3` (installed automatically by the installer) |
| **Multi-Location** | Deploy agents across POPs and compare results side-by-side |
| **Automatic Latency** | Background ICMP probes on every page load with per-node status, packet loss, and jitter |
| **iPerf3** | Per-node iPerf3 sessions with copy-ready commands, concurrency limits, and auto-expiry |
| **Your Connection** | Visitor IP, ISP, ASN, reverse DNS, country, and IPv4/IPv6 availability detection |
| **File Downloads** | Serve test files from each node with color-coded speed categories |
| **Admin Panel** | Full management UI — nodes, tests, users, settings, security events, system health, downloads, menus, footer |
| **Security** | HMAC-signed agent auth, rate limiting, private-IP blocking, DNS rebinding protection, replay protection |
| **One-Command Install** | Interactive installer for Ubuntu 24.04 and Debian 12 with upgrade/rollback support |
| **Docker** | Production-ready Compose stack (PostgreSQL, Redis, Controller, Frontend NGINX, Queue Worker, Scheduler) |
| **Theming** | Full light/dark theme with accessible color contrast |

---

## Quick Start — One-Command Install

**Full installation** (controller + agent on the same server):

```bash
curl -fsSL https://raw.githubusercontent.com/mdabubakkarhq/Looking-Glass/main/install.sh | sudo bash
```

**Agent only** (on a remote server):

```bash
curl -fsSL https://raw.githubusercontent.com/mdabubakkarhq/Looking-Glass/main/install.sh | sudo bash -s agent --controller=https://lg.example.com --token=YOUR_REG_TOKEN
```

The installer will:
1. Detect your OS (Ubuntu/Debian)
2. Install PHP, PostgreSQL/SQLite, Redis, NGINX, Node.js
3. Clone the repo, install dependencies, build the frontend
4. Configure NGINX with SSL (optional — Let's Encrypt or existing cert)
5. Run migrations and seed the database
6. Set up systemd services for the queue worker and scheduler
7. Print the admin login credentials

---

## Installation Methods

### Method 1 — Interactive Installer

```bash
git clone https://github.com/mdabubakkarhq/Looking-Glass.git
cd Looking-Glass
sudo bash install.sh
```

The installer is interactive and will ask for:
- **Installation type:** Full / Controller-only / Agent-only / Docker
- **Domain name** and SSL preference
- **Database:** PostgreSQL (recommended) / MariaDB / SQLite
- **Redis:** Local or external

**Non-interactive (all options via flags):**

```bash
sudo bash install.sh --domain=lg.example.com --db=postgresql --non-interactive
```

**Installer modules** in `install/`:

| File | Purpose |
|---|---|
| `common.sh` | Shared functions, logging, color output |
| `controller.sh` | Laravel app setup, PHP-FPM, Composer |
| `database.sh` | PostgreSQL / MariaDB / SQLite provisioning |
| `redis.sh` | Redis server installation |
| `nginx.sh` | NGINX site config and PHP-FPM integration |
| `ssl.sh` | Let's Encrypt or existing certificate setup |
| `firewall.sh` | UFW / iptables rules |
| `agent.sh` | Go agent build and systemd registration |
| `update.sh` | In-place upgrade (pull, migrate, rebuild) |
| `rollback.sh` | Roll back to previous git commit |
| `uninstall.sh` | Full removal of all components |

### Method 2 — Docker Compose

```bash
git clone https://github.com/mdabubakkarhq/Looking-Glass.git
cd Looking-Glass/controller

# Create your .env
cp .env.example .env
# Edit .env — set APP_KEY, APP_URL, DB_PASSWORD, etc.

# From the repo root:
docker compose up -d
```

This starts 6 services:

| Service | Container | Port | Purpose |
|---|---|---|---|
| `postgres` | lg-postgres | 5432 | PostgreSQL database |
| `redis` | lg-redis | 6379 | Cache, sessions, queue |
| `controller` | lg-controller | 8000 | Laravel API |
| `frontend` | lg-frontend | 80, 443 | NGINX serving Vue SPA + API proxy |
| `queue` | lg-queue | — | Laravel queue worker |
| `scheduler` | lg-scheduler | — | Laravel task scheduler |

After `docker compose up -d`:

```bash
# Run migrations and seed
docker exec lg-controller php artisan migrate --force
docker exec lg-controller php artisan db:seed --force

# Generate app key (first time)
docker exec lg-controller php artisan key:generate --force
```

### Method 3 — Manual Installation

#### Prerequisites

```bash
# PHP 8.3+ with required extensions
sudo apt install php8.3-cli php8.3-fpm php8.3-pgsql php8.3-redis \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath \
  php8.3-intl php8.3-gd

# PostgreSQL
sudo apt install postgresql postgresql-contrib

# Redis
sudo apt install redis-server

# NGINX
sudo apt install nginx

# Node.js 20+
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

#### Controller Setup

```bash
git clone https://github.com/mdabubakkarhq/Looking-Glass.git /opt/looking-glass
cd /opt/looking-glass/controller

# PHP dependencies
composer install --no-dev --optimize-autoloader

# Environment
cp .env.example .env
php artisan key:generate

# Edit .env — set database credentials, APP_URL, Redis, etc.
nano .env

# Database
php artisan migrate
php artisan db:seed

# Storage symlink
php artisan storage:link
```

#### Frontend Build

```bash
cd /opt/looking-glass/frontend
npm ci
npm run build

# Copy built assets to controller public directory
cp -r dist/* ../controller/public/
```

#### NGINX Configuration

```bash
sudo cp /opt/looking-glass/controller/nginx_lg.conf /etc/nginx/sites-available/looking-glass
sudo ln -s /etc/nginx/sites-available/looking-glass /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

#### Systemd Services

```bash
# Queue worker
sudo cp /opt/looking-glass/deploy/systemd/lg-worker.service /etc/systemd/system/

# Scheduler (uses same pattern — edit WorkingDirectory if needed)
sudo cp /opt/looking-glass/deploy/systemd/lg-scheduler.service /etc/systemd/system/

sudo systemctl daemon-reload
sudo systemctl enable --now lg-worker lg-scheduler
```

### Method 4 — Agent-Only (Remote Node)

On a remote server that should act as a Looking Glass node:

```bash
# Option A: Use the installer
curl -fsSL https://raw.githubusercontent.com/mdabubakkarhq/Looking-Glass/main/install.sh | \
  sudo bash -s agent --controller=https://lg.example.com --token=YOUR_TOKEN

# Option B: Build manually
git clone https://github.com/mdabubakkarhq/Looking-Glass.git
cd Looking-Glass/agent
make build
sudo make install

# Register with controller
lg-agent register --controller=https://lg.example.com --token=YOUR_TOKEN

# Start the agent
sudo systemctl enable --now lg-agent
```

Generate a registration token from the admin panel: **Admin → Nodes → Add Node → Copy the registration token**

---

## Configuration

### Environment Variables

Key settings in `controller/.env`:

```env
# Application
APP_NAME="Open Looking Glass"
APP_ENV=production
APP_URL=https://lg.example.com
APP_KEY=                    # Generated by php artisan key:generate

# Database (PostgreSQL recommended)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=looking_glass
DB_USERNAME=looking_glass
DB_PASSWORD=your_secure_password

# Redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Cache / Session / Queue
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Looking Glass
LG_SITE_NAME="Open Looking Glass"
LG_ORGANIZATION_NAME="Your Org"
LG_ADMIN_EMAIL=admin@example.com
LG_DEFAULT_RATE_LIMIT_PER_MINUTE=30
LG_DEFAULT_CONCURRENT_TESTS=20
LG_TEST_TIMEOUT_SECONDS=60
LG_VISITOR_HASH_SALT=change-this-to-a-random-string
```

### Admin Panel

Access at `https://lg.example.com/admin` (default credentials are printed by the installer or seeded by `db:seed`).

| Section | Capabilities |
|---|---|
| **Dashboard** | Overview stats, recent tests, active nodes |
| **Nodes** | Add/edit/delete nodes, toggle IPv4/IPv6, enable iPerf3, hostname management, health check timestamps |
| **Tests** | View test history, filter by type/node/status |
| **Downloads** | Manage downloadable test files per node |
| **Settings** | Site name, organization, SMTP, rate limits, feature toggles |
| **Users** | Admin account management, password reset |
| **Security** | Security event log (auth failures, rate limits, DNS rebinding) |
| **System** | Laravel version, PHP version, database/queue/cache drivers, migrations, health checks |
| **Menus** | Navigation menu item management |
| **Footer** | Footer sections and links |
| **Logs** | Application log viewer |

### SMTP / Email

Configure SMTP in the admin panel at **Admin → Settings → SMTP** for:
- Test email delivery verification
- Password reset emails
- System notifications

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@lg.example.com
MAIL_FROM_NAME="Open Looking Glass"
```

### NGINX

The included `controller/nginx_lg.conf` handles:
- PHP-FPM integration for the Laravel API
- Static asset caching (1 year, immutable)
- Hidden file protection
- API request routing

For Docker, `deploy/nginx/default.conf` additionally handles:
- SSE proxy buffering disabled
- SPA fallback (`try_files $uri /index.html`)
- Health check endpoint (`/healthz`)
- Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy)

---

## Agent Deployment

The Go agent runs on each remote node and executes network tests on behalf of the controller.

### How It Works

1. Agent is registered with the controller using a one-time token
2. Agent sends heartbeats every 30 seconds with system stats (CPU, memory, disk)
3. Controller sends signed test requests; agent validates HMAC signature before executing
4. Agent blocks private/reserved IP destinations and validates DNS resolution (anti-rebinding)
5. Results stream back to the controller as structured JSON

### Supported Tools

The agent automatically installs these tools if missing:
- `ping` / `ping6`
- `traceroute` / `traceroute6`
- `mtr` / `mtr6`
- `dig` (DNS)
- `iperf3` (bandwidth testing)

### Agent Configuration

Config file: `/etc/lg-agent/config.yaml`

```yaml
controller_url: "https://lg.example.com"
node_slug: "us-east-1"
listen: ":8443"
log_level: "info"
```

### Systemd Management

```bash
sudo systemctl status lg-agent    # Check status
sudo systemctl restart lg-agent   # Restart
sudo journalctl -u lg-agent -f    # View logs
```

---

## Upgrading & Rollback

### Upgrade

```bash
# Interactive — uses install/update.sh
sudo bash install/update.sh

# Or manually
cd /opt/looking-glass
git pull origin main

cd controller
composer install --no-dev --optimize-autoloader
php artisan migrate --force

cd ../frontend
npm ci && npm run build
cp -r dist/* ../controller/public/

sudo systemctl restart lg-worker lg-scheduler
```

### Rollback

```bash
# Rolls back the last git commit, re-runs migrations, and rebuilds
sudo bash install/rollback.sh
```

---

## Project Structure

```
Looking-Glass/
├── .github/workflows/ci.yml       # GitHub Actions CI
├── agent/                          # Go remote node agent
│   ├── cmd/agent/                  # Entry point (main.go, register.go)
│   ├── auth.go                     # HMAC signature verification
│   ├── config.go                   # YAML config loader
│   ├── executor.go                 # Test execution engine
│   ├── heartbeat.go                # Periodic heartbeat with system stats
│   ├── validation.go               # Target validation, private IP blocking
│   ├── Makefile
│   └── README.md
├── controller/                     # Laravel 11 API backend
│   ├── app/Http/Controllers/Api/   # API controllers (admin + public)
│   ├── app/Models/                 # Eloquent models
│   ├── app/Services/               # Business logic
│   ├── database/migrations/        # Schema migrations
│   ├── database/seeders/           # Default data
│   ├── nginx_lg.conf               # Native NGINX config
│   ├── Dockerfile                  # PHP 8.3 FPM Alpine
│   ├── .env.example
│   └── tests/                      # PHPUnit tests
├── frontend/                       # Vue 3 SPA
│   ├── src/components/             # Vue components
│   ├── src/views/                  # Page views
│   ├── src/stores/                 # Pinia stores
│   ├── src/api/                    # API client
│   ├── Dockerfile                  # Multi-stage build → NGINX
│   └── package.json
├── deploy/                         # Deployment configs
│   ├── nginx/default.conf          # Docker NGINX config
│   └── systemd/                    # Systemd unit files
├── install/                        # Installer modules
├── install.sh                      # One-command entry point
├── docker-compose.yml              # Production Docker stack
├── LOOKINGGLASS_SPEC.md            # Full technical specification
├── CONTRIBUTING.md
├── SECURITY.md
├── LICENSE                         # Apache 2.0
└── README.md
```

---

## Testing

### PHP Tests

```bash
cd controller
php artisan test
```

### Go Agent Tests

```bash
cd agent
go test -v -race ./...
```

### Frontend Type Check & Build

```bash
cd frontend
npm run type-check
npm run build
```

### Static Analysis

```bash
# PHP (PHPStan)
cd controller && vendor/bin/phpstan analyse --memory-limit=512M

# Go
cd agent && go vet ./...
```

### CI Pipeline

All tests run automatically on every push/PR to `main` via GitHub Actions:

| Job | What it does |
|---|---|
| `php-tests` | PHPUnit on PHP 8.3 and 8.4 |
| `php-static-analysis` | PHPStan level analysis |
| `go-tests` | `go test -v -race` on Go 1.22 and 1.23 |
| `go-lint` | `go vet` |
| `frontend` | `vue-tsc --noEmit` + `vite build` |
| `docker` | Docker image builds (controller + agent) |
| `install-scripts` | Bash syntax validation for all `install/*.sh` |

---

## Security

| Protection | Implementation |
|---|---|
| **No arbitrary shell access** | All commands are predefined and validated before execution |
| **HMAC-signed agent communication** | All controller-to-agent requests signed with a per-node shared secret |
| **Replay protection** | Request timestamps validated within a 30-second window |
| **Private/reserved IP blocking** | Agents reject targets in RFC 1918, RFC 4193, loopback, link-local ranges |
| **DNS rebinding protection** | Resolved IPs re-validated before test execution |
| **Rate limiting** | Per-IP throttling on all public endpoints (configurable, default 30/min) |
| **Agent sandboxing** | systemd `NoNewPrivileges`, `ProtectSystem=strict` |
| **Security event logging** | Auth failures, rate limit hits, DNS rebinding attempts logged to admin panel |
| **Output limits** | Maximum output size prevents resource exhaustion (default 1 MB) |
| **CSRF / XSS** | Laravel Sanctum, Vue auto-escaping, CSP headers |

For vulnerability reports, please see [SECURITY.md](./SECURITY.md).

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](./CONTRIBUTING.md) for guidelines on:
- Development environment setup
- Code style and conventions
- Pull request process
- Testing requirements

---

## License

This project is licensed under the **Apache License 2.0** — see [LICENSE](./controller/LICENSE) for details.

Copyright 2026 Open Looking Glass Contributors