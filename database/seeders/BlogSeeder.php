<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        $posts = [
            [
                'title' => 'Shipping a blog admin with tbtop',
                'slug' => 'shipping-blog-admin-tbtop',
                'excerpt' => 'A walkthrough of modeling posts, categories, and tags in a Laravel admin panel.',
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(5),
                'category_id' => $categories[0]->id,
                'tag_ids' => [$tags[0]->id, $tags[1]->id, $tags[2]->id],
            ],
            [
                'title' => 'Draft: upcoming editor improvements',
                'slug' => 'draft-editor-improvements',
                'excerpt' => 'Notes on richtext embeds and media pickers — still in progress.',
                'status' => PostStatus::Draft,
                'published_at' => null,
                'category_id' => $categories[1]->id,
                'tag_ids' => [$tags[1]->id],
            ],
            [
                'title' => 'Why we chose Inertia for internal tools',
                'slug' => 'why-inertia-internal-tools',
                'excerpt' => 'Server-driven UIs keep auth and validation where Laravel already shines.',
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(12),
                'category_id' => $categories[0]->id,
                'tag_ids' => [$tags[0]->id, $tags[3]->id],
            ],
        ];

        foreach ($posts as $index => $data) {
            $tagIds = $data['tag_ids'];
            unset($data['tag_ids']);

            $post = Post::create([
                ...$data,
                'author_id' => $authors[$index % $authors->count()]->id,
                'body' => [
                    'root' => [
                        'type' => 'root',
                        'children' => [
                            [
                                'type' => 'paragraph',
                                'children' => [
                                    ['type' => 'text', 'text' => fake()->paragraphs(3, true)],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $post->tags()->sync($tagIds);
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
            ->count(3)
            ->recycle($authors)
            ->recycle($categories)
            ->draft()
            ->create()
            ->each(fn (Post $post) => $post->tags()->attach(
                $tags->random(rand(1, 2))->pluck('id')
            ));
    }
}
