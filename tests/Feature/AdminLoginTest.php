<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin/posts')->assertRedirect('/admin/login');
    }

    public function test_admin_user_can_open_posts_table(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/posts')
            ->assertOk();
    }
}
