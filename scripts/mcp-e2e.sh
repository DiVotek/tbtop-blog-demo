#!/usr/bin/env bash
# End-to-end MCP smoke against a running `php artisan serve` instance.
set -euo pipefail
cd "$(dirname "$0")/.."

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
OUT_DIR="docs/screenshots/mcp"
mkdir -p "$OUT_DIR"

export PATH="${HOME}/bin:${PATH}"
TOKEN=$(php8.4 artisan tinker --execute="echo App\Models\User::where('email','admin@blog.demo')->firstOrFail()->createToken('mcp-e2e-script',['tbtop-mcp'])->plainTextToken;")

mcp() {
  local id=$1
  local body=$2
  local out=$3
  curl -sS "${BASE_URL}/admin/mcp" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json, text/event-stream' \
    -d "$body" | tee "$out"
}

mcp 1 '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"mcp-e2e","version":"1"}}}' \
  "$OUT_DIR/01-initialize.json"

mcp 2 '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"search","arguments":{}}}' \
  "$OUT_DIR/02-search.json"

mcp 3 '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"query","arguments":{"page":"posts-index-page","table":"posts","filters":{"status":"draft"}}}}' \
  "$OUT_DIR/03-query-drafts.json"

mcp 4 '{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"execute","arguments":{"id":"posts-index-page:bulkPublish","selection":["2","10","11"]}}}' \
  "$OUT_DIR/04-execute-bulk-publish.json"

echo "MCP e2e artifacts written to ${OUT_DIR}/"
