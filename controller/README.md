# Open Looking Glass

A modern, open-source, multi-location Looking Glass platform for network operators, ISPs, datacenters, and hosting providers.

## Features

- 🌍 Multi-location Looking Glass nodes
- 🏓 Ping, Traceroute, MTR, DNS Lookup
- 🌐 IPv4 and IPv6 support
- ⚡ Live streaming command output (SSE)
- 📊 Automatic latency checks across all nodes
- 🔄 Multi-location comparison
- 📥 File download speed tests
- 🔒 Secure remote execution (no arbitrary shell access)
- 🎛️ Centralized admin configuration
- 🚀 One-command installation
- 🐳 Docker Compose support (optional)
- 🔁 Upgrade and rollback support

## Architecture

```
Vue 3 Frontend  →  Laravel API Controller  →  Go Remote Node Agent
(TypeScript)        (PHP 8.3+ / PostgreSQL)     (Single binary)
                    (Redis for caching)
```

## Requirements

### Controller (Central Server)
- Ubuntu 24.04 / Debian 12
- PHP 8.3+
- Composer
- PostgreSQL 15+ (or MySQL/MariaDB)
- Redis 7+
- NGINX
- Node.js 22+ (for frontend build)

### Remote Node Agent
- Linux (any modern distribution)
- No runtime dependencies (single Go binary)

## Quick Start

### One-Command Installation

```bash
curl -sSL https://raw.githubusercontent.com/mdabubakkar-official/Looking-Glass/main/install.sh | sudo bash
```

### Manual Installation

#### 1. Clone the Repository

```bash
git clone https://github.com/mdabubakkar-official/Looking-Glass.git
cd Looking-Glass
```

#### 2. Set Up the Controller

```bash
cd controller
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

#### 3. Build the Frontend

```bash
cd frontend
npm install
npm run build
```

#### 4. Build the Agent (for remote nodes)

```bash
cd agent
go build -o looking-glass-agent ./cmd/agent
```

### Docker Compose

```bash
docker compose up -d
```

## Project Structure

```
Looking-Glass/
├── controller/          # Laravel API backend
├── frontend/            # Vue 3 SPA
├── agent/               # Go remote node agent
├── deploy/              # NGINX configs, systemd units
├── install.sh           # One-command installer
├── docker-compose.yml   # Docker deployment
└── LOOKINGGLASS_SPEC.md # Full technical specification
```

## Documentation

See [LOOKINGGLASS_SPEC.md](./LOOKINGGLASS_SPEC.md) for the complete technical specification.

## Security

- No arbitrary shell access is ever exposed to visitors
- All commands are predefined and validated
- Rate limiting on all public endpoints
- HMAC-signed agent communication
- DNS rebinding protection
- Private/reserved IP destination blocking

## License

Apache-2.0 (subject to final dependency/license review)

## Contributing

Contributions are welcome! Please read the contributing guidelines before submitting a pull request.
