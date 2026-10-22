<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_syncs_tags(): void
    {
        $post = Post::factory()->create(['status' => PostStatus::Draft]);
        $tags = Tag::factory()->count(2)->create();

        $post->tags()->sync($tags->pluck('id'));

        $this->assertCount(2, $post->fresh()->tags);
    }
}
