# Takt

A time-tracking and billing application for freelancers. Track time spent on projects, manage invoicing, and share reports with clients.

## Stack

- **Backend:** Laravel 13, Inertia.js v3
- **Frontend:** Vue 3, TypeScript, Tailwind CSS v4
- **Database:** PostgreSQL
- **Auth:** GitHub OAuth (or local disabled mode for development)
- **Tests:** Pest v4

## Requirements

- PHP 8.3+
- Composer
- Node.js + Yarn
- Docker (for Postgres in development)

## Installation

```bash
git clone <repo> takt && cd takt
composer setup
```

The `composer setup` script installs PHP and JS dependencies, generates the application key, creates `.env`, and runs migrations.

## Development

```bash
composer dev
```

Starts PostgreSQL via Docker and the Laravel server on port **8084**.

For frontend hot-reload, in a second terminal:

```bash
yarn dev
```

## Authentication

### GitHub OAuth (default)

Create a GitHub OAuth App and configure `.env`:

```env
AUTH_ENABLED=true
GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...
GITHUB_REDIRECT_URI=http://localhost:8084/login/callback
```

### Local mode (development without OAuth)

```env
AUTH_ENABLED=false
```

The login page exposes a form allowing direct sign-in with any existing user's email — no password required.

## MCP server

Takt exposes an [MCP](https://modelcontextprotocol.io) server at `/mcp` so an AI agent can manage clients, projects, timesheet entries and invoices, and produce billing recaps (JSON plus a signed PDF link). It authenticates with the same API token as the REST API (`Authorization: Bearer <token>`, generated from your profile); the connection commands are shown, pre-filled, on the in-app API documentation page. Design choices are recorded in [ADR-0015](docs/adr/0015-mcp-server-over-http-with-the-existing-api-token.md).

## Testing

```bash
composer test
# or
php artisan test --compact
```

## Documentation

- [Features & routes](docs/features.md)
- [Data models](docs/models.md)
- [Technical architecture](docs/architecture.md)

## Contributing

Issues and pull requests are welcome. For anything beyond a small fix, please open an issue to discuss the change before submitting a new feature PR — see [CONTRIBUTING.md](CONTRIBUTING.md).

## License

Takt is open-source software licensed under the [GNU Affero General Public License v3.0](LICENSE).
