<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $authors = User::query()->get();
        if ($authors->isEmpty()) {
            $authors = collect([
                User::factory()->create([
                    'name' => 'Alex Rivera',
                    'email' => 'alex@blog.demo',
                ]),
                User::factory()->create([
                    'name' => 'Morgan Lee',
                    'email' => 'morgan@blog.demo',
                ]),
            ]);
        }

        $categories = Category::factory()->createMany([
            ['name' => 'Engineering', 'slug' => 'engineering', 'description' => 'Build logs, architecture, and tooling.'],
            ['name' => 'Product', 'slug' => 'product', 'description' => 'Roadmaps, releases, and customer stories.'],
            ['name' => 'Culture', 'slug' => 'culture', 'description' => 'Team notes and how we work.'],
        ]);

        $tags = Tag::factory()->createMany([
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'Admin UI', 'slug' => 'admin-ui'],
            ['name' => 'Tutorial', 'slug' => 'tutorial'],
            ['name' => 'Release', 'slug' => 'release'],
        ]);

        $body = [
            'root' => [
                'type' => 'root',
                'children' => [
                    [
                        'type' => 'paragraph',
                        'children' => [
                            ['type' => 'text', 'text' => fake()->paragraphs(2, true)],
                        ],
                    ],
                ],
            ],
        ];

        $posts = [
            [
                'title' => 'Shipping a blog admin with tbtop',
                'slug' => 'shipping-blog-admin-tbtop',
                'excerpt' => 'A walkthrough of modeling posts, categories, and tags in a Laravel admin panel.',
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(5),
                'category_id' => $categories[0]->id,
                'tag_ids' => [$tags[0]->id, $tags[1]->id, $tags[2]->id],
                'created_at' => now()->subDays(20),
            ],
            [
                'title' => 'Draft: upcoming editor improvements',
                'slug' => 'draft-editor-improvements',
                'excerpt' => 'Notes on richtext embeds and media pickers — still in progress.',
                'status' => PostStatus::Draft,
                'published_at' => null,
                'category_id' => $categories[1]->id,
                'tag_ids' => [$tags[1]->id],
                'created_at' => now()->subDays(2),
            ],
            [
                'title' => 'Why we chose Inertia for internal tools',
                'slug' => 'why-inertia-internal-tools',
                'excerpt' => 'Server-driven UIs keep auth and validation where Laravel already shines.',
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(12),
                'category_id' => $categories[0]->id,
                'tag_ids' => [$tags[0]->id, $tags[3]->id],
                'created_at' => now()->subDays(30),
            ],
        ];

        foreach ($posts as $index => $data) {
            $tagIds = $data['tag_ids'];
            $createdAt = $data['created_at'];
            unset($data['tag_ids'], $data['created_at']);

            $post = Post::create([
                ...$data,
                'author_id' => $authors[$index % $authors->count()]->id,
                'body' => $body,
            ]);
            $this->backdate($post, $createdAt);
            $post->tags()->sync($tagIds);
        }

        $oldDrafts = [
            ['title' => 'Stale draft: Q3 roadmap notes', 'slug' => 'stale-draft-q3-roadmap', 'days' => 14],
            ['title' => 'Stale draft: migration checklist', 'slug' => 'stale-draft-migration-checklist', 'days' => 21],
            ['title' => 'Stale draft: interview questions', 'slug' => 'stale-draft-interview-questions', 'days' => 10],
            ['title' => 'Stale draft: perf audit backlog', 'slug' => 'stale-draft-perf-audit', 'days' => 45],
            ['title' => 'Stale draft: onboarding doc', 'slug' => 'stale-draft-onboarding', 'days' => 60],
        ];

        foreach ($oldDrafts as $i => $draft) {
            $post = Post::create([
                'title' => $draft['title'],
                'slug' => $draft['slug'],
                'excerpt' => 'Intentionally old draft for MCP date-filter demos.',
                'status' => PostStatus::Draft,
                'published_at' => null,
                'author_id' => $authors[$i % $authors->count()]->id,
                'category_id' => $categories[$i % $categories->count()]->id,
                'body' => $body,
            ]);
            $this->backdate($post, now()->subDays($draft['days']));
            $post->tags()->sync($tags->random(rand(1, 2))->pluck('id'));
        }

        Post::factory()
            ->count(6)
            ->recycle($authors)
            ->recycle($categories)
            ->published()
            ->create()
            ->each(fn (Post $post) => $post->tags()->attach(
                $tags->random(rand(1, 3))->pluck('id')
            ));

        Post::factory()
            ->count(2)
            ->recycle($authors)
            ->recycle($categories)
            ->draft()
            ->create()
            ->each(function (Post $post) use ($tags): void {
                $this->backdate($post, now()->subDays(3));
                $post->tags()->attach($tags->random(rand(1, 2))->pluck('id'));
            });
    }

    private function backdate(Post $post, Carbon $createdAt): void
    {
        $post->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();
    }
}
