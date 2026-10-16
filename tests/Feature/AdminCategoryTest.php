<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_with_posts_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->create(['category_id' => $category->id]);

        $this->actingAs($user)
            ->postJson('/admin/categories/actions/delete', [
                'payload' => ['row' => ['id' => $category->id]],
            ])
            ->assertOk()
            ->assertJsonPath('effects.0.message', 'Cannot delete: category still has posts');

        $this->assertModelExists($category);
    }
}
