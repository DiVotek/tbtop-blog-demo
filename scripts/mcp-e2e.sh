#!/usr/bin/env bash
# End-to-end MCP scenario: drafts older than 7 days → publish the 3 oldest (by created_at).
# Requires: running `php artisan serve`, php8.4, curl, jq.
set -euo pipefail
cd "$(dirname "$0")/.."

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
OUT_DIR="docs/screenshots/mcp"
mkdir -p "$OUT_DIR"
rm -f "$OUT_DIR"/*.json

export PATH="${HOME}/bin:${PATH}"

TOKEN=$(php8.4 artisan tinker --execute="echo App\Models\User::where('email','admin@blog.demo')->firstOrFail()->createToken('mcp-e2e-script',['tbtop-mcp'])->plainTextToken;")

CUTOFF_DATE=$(date -u -d '7 days ago' +%Y-%m-%d)

mcp_post() {
  local outfile=$1
  local body=$2
  curl -sS "${BASE_URL}/admin/mcp" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json, text/event-stream' \
    -d "$body" > "$outfile"
}

mcp_post "$OUT_DIR/01-initialize.json" \
  '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"mcp-e2e","version":"1"}}}'

mcp_post "$OUT_DIR/02-tools-list.json" \
  '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'

mcp_post "$OUT_DIR/03-search.json" \
  '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"search","arguments":{}}}'

QUERY_BODY=$(jq -nc \
  --arg cutoff "$CUTOFF_DATE" \
  '{
    jsonrpc: "2.0",
    id: 4,
    method: "tools/call",
    params: {
      name: "query",
      arguments: {
        page: "posts-index-page",
        table: "posts",
        tab: "draft",
        filters: { created_at: { to: $cutoff } },
        sort: "created_at",
        dir: "asc",
        perPage: 25
      }
    }
  }')

mcp_post "$OUT_DIR/04-query-old-drafts.json" "$QUERY_BODY"

SELECTION=$(jq -r '
  .result.content[0].text
  | fromjson
  | .data[0:3]
  | map(._key | tostring)
  | @json
' "$OUT_DIR/04-query-old-drafts.json")

COUNT=$(jq -r '.result.content[0].text | fromjson | .data | length' "$OUT_DIR/04-query-old-drafts.json")
if [[ "$COUNT" -lt 3 ]]; then
  echo "Expected at least 3 old drafts, got ${COUNT}. Check seed data and cutoff ${CUTOFF_DATE}." >&2
  exit 1
fi

EXEC_BODY=$(jq -nc \
  --argjson selection "$SELECTION" \
  '{
    jsonrpc: "2.0",
    id: 5,
    method: "tools/call",
    params: {
      name: "execute",
      arguments: {
        id: "posts-index-page:bulkPublish",
        selection: $selection
      }
    }
  }')

mcp_post "$OUT_DIR/05-execute-bulk-publish.json" "$EXEC_BODY"

echo "MCP e2e complete (cutoff created_at <= ${CUTOFF_DATE}, published keys: ${SELECTION})."
echo "Artifacts: ${OUT_DIR}/"
