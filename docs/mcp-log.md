# MCP access log — blog demo

Material for **“Giving Claude access to your admin without fearing for your data”**. Scenario: **find draft posts older than a week and publish the three oldest** using only documented tbtop MCP tools (`search`, `query`, `execute`) per [wiring.md](https://github.com/DiVotek/tbtop/blob/v0.7.0/docs/ai/wiring.md).

All captures below come from **one run** on a **fresh database** (`php artisan migrate:fresh --seed`) via `./scripts/mcp-e2e.sh` (2026-10-16, app served at `http://127.0.0.1:8000`).

## Capture index

| Step | File | What it records |
|------|------|-----------------|
| 1 | `docs/screenshots/mcp/01-initialize.json` | MCP `initialize` handshake |
| 2 | `docs/screenshots/mcp/02-tools-list.json` | `tools/list` — `readOnlyHint` on `search`/`query`, `destructiveHint` on `execute` |
| 3 | `docs/screenshots/mcp/03-search.json` | Full panel `search` (pages, filters, sortable columns) |
| 4 | `docs/screenshots/mcp/04-query-old-drafts.json` | `query` — draft tab + `created_at` daterange + sort |
| 5 | `docs/screenshots/mcp/05-execute-bulk-publish.json` | `execute` — `posts-index-page:bulkPublish` |

## Host prerequisites

- `PostsIndexPage` exposes **`created_at`** as a **sortable** column and a **`Daterange`** filter (`Daterange::make('created_at')`) so MCP `query` can filter/sort per `search()` metadata.
- Seed data includes **five** drafts with `created_at` **> 7 days ago** (`stale-draft-*` in `BlogSeeder`) plus recent drafts that must **not** match the cutoff filter.
- `bootstrap/app.php`: `abilities` → `CheckAbilities::class`.
- `AdminPanel::mcp(['auth:sanctum', 'abilities:tbtop-mcp', 'throttle:60,1'])`.

## Token

```bash
php artisan tinker
>>> $u = App\Models\User::where('email', 'admin@blog.demo')->first();
>>> $u->createToken('mcp-demo', ['tbtop-mcp'])->plainTextToken;
```

PHPUnit: `AdminMcpTest` — token **without** `tbtop-mcp` → **403**; with ability → **200**.

## Client config

- **Cursor (HTTP):** [`docs/mcp-http-client.json`](mcp-http-client.json) — `Authorization: Bearer ${env:TBTOP_TOKEN}`.
- **Claude Desktop:** bridge with `mcp-remote` per [wiring.md § Connect a client](https://github.com/DiVotek/tbtop/blob/v0.7.0/docs/ai/wiring.md) (not exercised on the cloud VM).

## Scripted scenario (`scripts/mcp-e2e.sh`)

**Selection rule (documented in script):** after `query` with `sort: created_at`, `dir: asc`, take the **first three rows** (`_key` values) — i.e. the **three oldest drafts** that match the filters.

**Cutoff:** `created_at.to = UTC date of (today − 7 days)` so rows with `created_at` on or before that date are “older than a week” (matches `Daterange` filter `to` bound in `TableFilterApplier`).

**Query arguments (id 4):**

```json
{
  "page": "posts-index-page",
  "table": "posts",
  "tab": "draft",
  "filters": { "created_at": { "to": "2026-10-09" } },
  "sort": "created_at",
  "dir": "asc",
  "perPage": 25
}
```

(`2026-10-09` was the cutoff for the captured run; the script computes this dynamically.)

### Excerpt — initialize (`01-initialize.json`)

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2025-11-25",
    "serverInfo": { "name": "Tabletop Admin", "version": "1.0.0" }
  }
}
```

### Excerpt — tools/list (`02-tools-list.json`)

```json
{
  "name": "search",
  "annotations": { "readOnlyHint": true }
}
{
  "name": "query",
  "annotations": { "readOnlyHint": true }
}
{
  "name": "execute",
  "annotations": { "destructiveHint": true }
}
```

`tbtop` marks `execute` destructive; **the MCP client** decides whether to ask the user to confirm — the server does not enforce confirmations.

### Excerpt — search posts table filters (`03-search.json`)

From `posts-index-page` → `tables[0].filters`:

```json
{
  "name": "created_at",
  "kind": "daterange",
  "label": "Created",
  "value": "{\"from\": \"Y-m-d\", \"to\": \"Y-m-d\"}, either bound optional"
}
```

`sortable` includes `created_at`. Auth pages are **not** listed: `LoginPage` extends `AuthPage`, which sets `Page::mcp(): false` in v0.7.0 (verified by `AdminMcpTest::test_mcp_search_does_not_list_auth_pages`).

### Excerpt — query result (`04-query-old-drafts.json`)

First three rows returned (oldest drafts at or before cutoff):

```json
[
  { "title": "Stale draft: onboarding doc", "created_at": "2026-08-17", "_key": 8 },
  { "title": "Stale draft: perf audit backlog", "created_at": "2026-09-01", "_key": 7 },
  { "title": "Stale draft: migration checklist", "created_at": "2026-09-25", "_key": 5 }
]
```

### Excerpt — execute (`05-execute-bulk-publish.json`)

Request (built by script from `_key` values above):

```json
{
  "id": "posts-index-page:bulkPublish",
  "selection": ["8", "7", "5"]
}
```

Response body (`result.content[0].text`):

```json
{
  "ok": true,
  "effects": [
    { "kind": "notify", "message": "Published 3 post(s)", "level": "success" },
    { "kind": "refreshTable", "table": "posts" }
  ]
}
```

## Threat model (accurate for v0.7.0)

| Control | Behavior |
|---------|----------|
| Token ability | MCP route uses `abilities:tbtop-mcp`. Tokens **without** that ability receive **403** (`AdminMcpTest`). |
| Auth pages | `AuthPage::mcp()` is **false** — login/forgot/reset are **hidden** from `search`, not merely “impractical”. |
| Origin | Browser `Origin` checked; CLI/curl with no `Origin` passes (`ValidateMcpOrigin`). |
| Throttle | `throttle:60,1` on MCP middleware stack. |
| Destructive actions | `execute` annotated `destructiveHint`; confirmations are **client-side**. |

## Verified vs not

| Item | Status |
|------|--------|
| Full script on fresh DB | Verified (this capture set) |
| Cursor / Claude Desktop UI | Not verified on cloud VM (config documented) |
| “Best” editorial judgment | **Agent logic** — demo uses **oldest three by `created_at`** as a stand-in |

## Undocumented / package-defined

- JSON-RPC envelope and `tools/list` shape come from `laravel/mcp`; tbtop documents the three tool **names** and behavior in wiring.md.
