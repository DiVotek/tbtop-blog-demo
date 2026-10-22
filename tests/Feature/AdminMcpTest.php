<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_mcp_initialize_requires_sanctum_token_with_ability(): void
    {
        $this->postJson('/admin/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => [],
                'clientInfo' => ['name' => 'phpunit', 'version' => '1'],
            ],
        ])->assertUnauthorized();
    }

    public function test_mcp_initialize_rejects_token_without_tbtop_mcp_ability(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('other-app', ['read-posts'])->plainTextToken;

        $this->withToken($token)
            ->postJson('/admin/mcp', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2025-11-25',
                    'capabilities' => [],
                    'clientInfo' => ['name' => 'phpunit', 'version' => '1'],
                ],
            ], [
                'Accept' => 'application/json, text/event-stream',
            ])
            ->assertForbidden();
    }

    public function test_mcp_initialize_succeeds_with_tbtop_mcp_ability(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mcp-test', ['tbtop-mcp'])->plainTextToken;

        $this->withToken($token)
            ->postJson('/admin/mcp', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2025-11-25',
                    'capabilities' => [],
                    'clientInfo' => ['name' => 'phpunit', 'version' => '1'],
                ],
            ], [
                'Accept' => 'application/json, text/event-stream',
            ])
            ->assertSuccessful();
    }

    public function test_mcp_search_does_not_list_auth_pages(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mcp-test', ['tbtop-mcp'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/admin/mcp', [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/call',
                'params' => ['name' => 'search', 'arguments' => []],
            ], [
                'Accept' => 'application/json, text/event-stream',
            ])
            ->assertSuccessful();

        $pages = json_decode($response->json('result.content.0.text'), true)['pages'] ?? [];
        $slugs = array_column($pages, 'page');

        $this->assertNotContains('login-page', $slugs);
        $this->assertNotContains('forgot-password-page', $slugs);
    }
}
