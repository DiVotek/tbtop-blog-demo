<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminPostActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpublish_action_redirects_without_calling_reload(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create([
            'published_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson("/admin/posts/{$post->id}/edit/actions/unpublish", [
                'payload' => [],
            ])
            ->assertOk()
            ->assertJsonPath('effects.1.kind', 'redirect')
            ->assertJsonPath('effects.1.href', "/admin/posts/{$post->id}/edit");

        $post->refresh();
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_edit_form_preserves_published_at_when_only_excerpt_changes(): void
    {
        $user = User::factory()->create();
        $publishedAt = Carbon::parse('2026-03-10 15:30:00');
        $post = Post::factory()->published()->create([
            'published_at' => $publishedAt,
            'excerpt' => 'Before',
        ]);
        $tag = Tag::factory()->create();

        $this->actingAs($user)
            ->postJson("/admin/posts/{$post->id}/edit/forms/post", [
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => 'After',
                'body' => $post->body,
                'status' => PostStatus::Published->value,
                'published_at' => $publishedAt->toDateString(),
                'cover_media_id' => null,
                'category_id' => $post->category_id,
                'author_id' => $post->author_id,
                'tag_ids' => [(string) $tag->id],
            ])
            ->assertRedirect();

        $post->refresh();
        $this->assertSame('After', $post->excerpt);
        $this->assertNotNull($post->published_at);
        $this->assertSame('2026-03-10', $post->published_at->toDateString());
    }

    public function test_edit_form_rejects_invalid_tag_ids(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson("/admin/posts/{$post->id}/edit/forms/post", [
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => PostStatus::Draft->value,
                'tag_ids' => ['99999'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tag_ids.0']);
    }

    public function test_edit_form_shows_validation_errors_for_invalid_title(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson("/admin/posts/{$post->id}/edit/forms/post", [
                'title' => '',
                'slug' => $post->slug,
                'status' => PostStatus::Draft->value,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_bulk_publish_does_not_overwrite_existing_published_at(): void
    {
        $user = User::factory()->create();
        $original = Carbon::parse('2025-06-01 08:00:00');
        $published = Post::factory()->published()->create(['published_at' => $original]);
        $draft = Post::factory()->draft()->create();

        $this->actingAs($user)
            ->postJson('/admin/posts/actions/bulkPublish', [
                'payload' => ['selection' => [$published->id, $draft->id]],
            ])
            ->assertOk();

        $published->refresh();
        $draft->refresh();

        $this->assertSame('2025-06-01', $published->published_at->toDateString());
        $this->assertNotNull($draft->published_at);
    }
}
