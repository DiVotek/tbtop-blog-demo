# tbtop blog demo

A small Laravel **13** blog with an admin panel built on [tbtop/admin](https://github.com/DiVotek/tbtop) v0.7. It covers posts (richtext body, cover image, draft/published), categories, tags, and authors (`users`), plus a minimal public blog front.

## Requirements

- PHP **8.4+**
- Composer
- Node.js 20+ and npm
- SQLite (default) or another database configured in `.env`

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using SQLite
php artisan migrate --seed
php artisan storage:link
npm install --legacy-peer-deps
npm run build
php artisan serve
```

- Public blog: http://127.0.0.1:8000/
- Admin panel: http://127.0.0.1:8000/admin (redirects to login)

For local development with hot reload, run `npm run dev` in a second terminal.

### Demo login

| Email | Password |
|-------|----------|
| `admin@blog.demo` | `password` |

## MCP (optional)

The admin panel exposes an MCP endpoint at `/admin/mcp` when `laravel/mcp` is installed. Create a Sanctum token with the `tbtop-mcp` ability and point your MCP client at the panel URL. See `docs/mcp-http-client.json` (Cursor HTTP) and `docs/mcp-log.md` (full walkthrough + Claude Desktop via `mcp-remote`).

## Build journal

See [`docs/build-log.md`](docs/build-log.md) for the step-by-step notes behind this repo (commands, pitfalls, screenshots).

## License

MIT
