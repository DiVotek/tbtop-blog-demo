# MCP access log — blog demo

Material for **“Giving Claude access to your admin without fearing for your data”**. All calls target the seeded app (`admin@blog.demo`) with **documented** tbtop MCP tools (`search`, `query`, `execute`) from [wiring.md](https://github.com/DiVotek/tbtop/blob/v0.7.0/docs/ai/wiring.md).

## What we verified

| Step | Verified |
|------|----------|
| Sanctum token with `tbtop-mcp` ability | Yes (tinker + PHPUnit `AdminMcpTest`) |
| `abilities` middleware alias | Yes (`bootstrap/app.php`) |
| `POST /admin/mcp` initialize | Yes (curl + script) |
| `search` lists pages/executables | Yes |
| `query` reads `posts` table with filters | Yes |
| `execute` runs `posts-index-page:bulkPublish` | Yes (3 drafts → published) |
| Claude Desktop / mcp-remote on this VM | **Not run** (no desktop app); config documented from wiring.md |
| Cursor IDE MCP UI | **Not run** in cloud VM; HTTP config template committed |

## Threat model (from docs, observed)

- **Token ability:** Only tokens with `tbtop-mcp` pass `abilities:tbtop-mcp` on the MCP route; other Sanctum tokens get 403.
- **Panel middleware not reused:** MCP uses its own stack (`auth:sanctum`, abilities, `throttle:60,1`); bearer auth, not session cookies.
- **Origin check:** Browser `Origin` must match `app.url` or `mcpAllowedOrigins`; **no Origin** (curl, Claude Desktop, Cursor CLI) passes — we used curl without Origin.
- **Page/action curation:** `LoginPage` is listed in `search` but sign-in over MCP is impractical (stateless token). Destructive actions are exposed; **`execute` is always annotated destructive** — the **MCP client** is responsible for confirmation UI, not tbtop.
- **Row trust:** `selection` / `row` are client-supplied; handlers should re-load by key (our bulk publish uses Eloquent `whereKey`).

## 1. Host wiring (already in repo)

```php
// app/Admin/AdminPanel.php
->mcp(['auth:sanctum', 'abilities:tbtop-mcp', 'throttle:60,1']);

// bootstrap/app.php
$middleware->alias(['abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class]);
```

## 2. Create a token (once per client)

```bash
php artisan tinker
>>> $u = App\Models\User::where('email', 'admin@blog.demo')->first();
>>> $u->createToken('cursor-mcp', ['tbtop-mcp'])->plainTextToken;
```

Store the plain text value in an env var (never commit it).

## 3. Cursor (HTTP MCP)

Project template: [`docs/mcp-http-client.json`](mcp-http-client.json)

```json
{
  "mcpServers": {
    "tbtop-blog-demo": {
      "url": "http://127.0.0.1:8000/admin/mcp",
      "headers": {
        "Authorization": "Bearer ${env:TBTOP_TOKEN}"
      }
    }
  }
}
```

Set `TBTOP_TOKEN` in your shell or Cursor secrets.

## 4. Claude Desktop (stdio bridge)

From [wiring.md § Connect a client](https://github.com/DiVotek/tbtop/blob/v0.7.0/docs/ai/wiring.md):

```json
{
  "mcpServers": {
    "tbtop": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "http://127.0.0.1:8000/admin/mcp", "--header", "Authorization:${TBTOP_AUTH}"],
      "env": { "TBTOP_AUTH": "Bearer 1|your-token" }
    }
  }
}
```

## 5. Scripted scenario (this run)

**Goal:** “Find drafts and publish three of them” — stand-in for “drafts older than a week, publish the best 3” (seed data does not age posts; filter `status=draft` used instead).

```bash
php artisan serve   # terminal 1
./scripts/mcp-e2e.sh
```

### Request / response excerpts

**Initialize** (`docs/screenshots/mcp/01-initialize.json`):

```http
POST /admin/mcp
Authorization: Bearer …
```

```json
{"jsonrpc":"2.0","id":1,"result":{"protocolVersion":"2025-11-25","serverInfo":{"name":"Tabletop Admin","version":"1.0.0"},…}}
```

**search** — returns `posts-index-page` with executable `posts-index-page:bulkPublish` (`needs: ["selection"]`). Full payload: `02-search.json`.

**query** — drafts only:

```json
{"name":"query","arguments":{"page":"posts-index-page","table":"posts","filters":{"status":"draft"}}}
```

Sample row: `{"title":"Draft: upcoming editor improvements","_key":2,…}` — see `03-query-drafts.json`.

**execute** — bulk publish (destructive; client should confirm):

```json
{
  "name": "execute",
  "arguments": {
    "id": "posts-index-page:bulkPublish",
    "selection": ["2", "10", "11"]
  }
}
```

Response (`04-execute-bulk-publish.json`):

```json
{"ok":true,"effects":[{"kind":"notify","message":"Published 3 post(s)"},{"kind":"refreshTable","table":"posts"}]}
```

### PHPUnit

`tests/Feature/AdminMcpTest.php` — 401 without token, 200 initialize with `tbtop-mcp` ability.

## Failures & workarounds

| Issue | Workaround |
|-------|------------|
| `execute` with `executable` key | Use **`id`** per `ExecuteTool` schema (`posts-index-page:bulkPublish`). |
| `abilities` middleware unknown | Register alias in `bootstrap/app.php` (documented in wiring.md, easy to miss). |
| Wrong `page` slug in `query` | Use slug from `search()` (e.g. `posts-index-page`, not `posts`). |

## Undocumented / inferred

- Exact JSON shape for `tools/call` is defined by `laravel/mcp`, not duplicated on docs.tbtop.dev — we followed wiring.md curl examples + `ExecuteTool::schema()`.
- Choosing “best 3” drafts is **agent logic**; tbtop only exposes `query` + `execute`.

## Screenshots / captures

Terminal JSON artifacts: `docs/screenshots/mcp/01-initialize.json` … `04-execute-bulk-publish.json`.
