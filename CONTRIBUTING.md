# Contributing to Open Looking Glass

Thank you for your interest in contributing to Open Looking Glass!

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+ and npm
- Go 1.22+ (for agent development)
- PostgreSQL 14+
- Redis 6+

### Development Setup

1. **Clone the repository**

   ```bash
   git clone https://github.com/openlookingglass/looking-glass.git
   cd looking-glass
   ```

2. **Controller (Laravel)**

   ```bash
   cd controller
   cp .env.example .env
   composer install
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   php artisan serve
   ```

3. **Frontend (Vue 3)**

   ```bash
   cd frontend
   npm install
   npm run dev
   ```

4. **Agent (Go)**

   ```bash
   cd agent
   go mod download
   go build -o lg-agent ./cmd/agent
   ```

## Project Structure

```text
looking-glass/
├── controller/         # Laravel 12 API backend
│   ├── app/
│   │   ├── Http/Controllers/Api/   # API controllers
│   │   ├── Models/                 # Eloquent models
│   │   └── Services/               # Business logic
│   ├── config/                     # Configuration files
│   ├── database/migrations/        # Database schema
│   └── routes/api.php              # API route definitions
├── frontend/           # Vue 3 + TypeScript SPA
│   └── src/
│       ├── api/                    # API client
│       ├── components/             # Vue components
│       ├── stores/                 # Pinia state management
│       ├── types/                  # TypeScript types
│       └── views/                  # Page components
├── agent/              # Go agent binary
│   ├── cmd/agent/                    # CLI entrypoint
│   ├── internal/                   # Internal packages
│   └── scripts/                    # systemd service files
└── LOOKINGGLASS_SPEC.md            # Full specification
```

## How to Contribute

### Reporting Bugs

1. Check existing issues to avoid duplicates
2. Use the bug report template
3. Include:
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Environment details (OS, PHP version, Node version, etc.)

### Suggesting Features

1. Check the [specification](LOOKINGGLASS_SPEC.md) for planned features
2. Open a feature request issue
3. Describe the use case and proposed solution

### Submitting Code

1. Fork the repository
2. Create a feature branch from `main`

   ```bash
   git checkout -b feature/your-feature-name
   ```

3. Make your changes
4. Follow the coding standards (see below)
5. Write or update tests as needed
6. Commit with clear messages

   ```bash
   git commit -m "feat: add multi-location comparison table"
   ```

7. Push and open a Pull Request

### Commit Convention

We use [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` — New feature
- `fix:` — Bug fix
- `docs:` — Documentation changes
- `style:` — Code style (formatting, no logic change)
- `refactor:` — Code refactoring
- `test:` — Adding or updating tests
- `chore:` — Build, CI, dependency updates

## Coding Standards

### PHP (Controller)

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1)`
- Use Laravel conventions (Eloquent, Form Requests, etc.)
- Run `composer pint` for code style checking

### TypeScript (Frontend)

- Use TypeScript strict mode
- Follow Vue 3 Composition API patterns with `<script setup>`
- Use Pinia for state management
- Run `npm run lint` for code style checking

### Go (Agent)

- Follow standard Go conventions (`gofmt`)
- Use meaningful package names
- Write godoc comments for exported functions
- Run `go vet ./...` before committing

## Testing

### Controller

```bash
cd controller
php artisan test
```

### Frontend

```bash
cd frontend
npm run test
```

### Agent

```bash
cd agent
go test ./...
```

## Architecture Decisions

- **API-first**: The controller is a pure API backend; the frontend is a separate SPA
- **Agent pattern**: Remote nodes run a lightweight Go agent that executes network tools
- **SSE streaming**: Live test output uses Server-Sent Events for real-time feedback
- **HMAC authentication**: Agent-to-controller communication uses signed requests
- **Privacy-first**: Visitor IPs are hashed; no PII stored by default

## License

By contributing, you agree that your contributions will be licensed under the Apache-2.0 License.

## Questions?

Open a discussion on GitHub or reach out to the maintainers.
