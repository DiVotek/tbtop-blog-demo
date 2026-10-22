<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_published_posts_only(): void
    {
        Post::factory()->published()->create(['title' => 'Visible post']);
        Post::factory()->draft()->create(['title' => 'Hidden draft']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Visible post');
        $response->assertDontSee('Hidden draft');
    }

    public function test_post_page_by_slug(): void
    {
        $post = Post::factory()->published()->create([
            'slug' => 'hello-world',
            'title' => 'Hello world',
        ]);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertSee('Hello world');
    }

    public function test_draft_post_is_not_public(): void
    {
        $post = Post::factory()->draft()->create(['slug' => 'secret']);

        $this->get(route('blog.show', $post->slug))->assertNotFound();
    }
}
