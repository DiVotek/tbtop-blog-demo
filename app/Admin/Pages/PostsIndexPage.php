<?php

namespace App\Admin\Pages;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Tbtop\Admin\Actions\ActionCtx;
use Tbtop\Admin\Actions\Effects;
use Tbtop\Admin\Dsl\Actions\DeleteAction;
use Tbtop\Admin\Dsl\Color;
use Tbtop\Admin\Dsl\Column;
use Tbtop\Admin\Dsl\Fields\InFilter;
use Tbtop\Admin\Dsl\Fields\Select;
use Tbtop\Admin\Dsl\Node;
use Tbtop\Admin\Dsl\S;
use Tbtop\Admin\Dsl\Tab;
use Tbtop\Admin\Pages\Page;

class PostsIndexPage extends Page
{
    public static function path(): string
    {
        return 'posts';
    }

    public static function nav(): ?array
    {
        return ['group' => 'Content', 'label' => 'Posts', 'order' => 1, 'icon' => 'file-text', 'badge' => (string) Post::count()];
    }

    public function title(): string
    {
        return 'Posts';
    }

    public function view(S $s): Node
    {
        return $s->stack([
            $s->actionsRow([
                $s->action('new')->label('New post')->color('primary')->url('/admin/posts/new'),
            ]),
            $s->table('posts')
                ->rowClick('edit')
                ->columns([
                    Column::make('id')->label('ID')->formatUsing(fn ($value) => "#{$value}"),
                    Column::make('title')->label('Title')->searchable()->sortable(),
                    Column::make('slug')->label('Slug')->copyable(copyMessage: 'Slug copied'),
                    Column::make('status')->label('Status')
                        ->badge([
                            PostStatus::Published->value => ['label' => 'Published', 'color' => Color::Success],
                            PostStatus::Draft->value => ['label' => 'Draft'],
                        ]),
                    Column::make('category.name')->label('Category'),
                    Column::make('author.name')->label('Author'),
                    Column::make('published_at')->label('Published')->date('Y-m-d')->sortable(),
                ])
                ->filters([
                    InFilter::make('status')
                        ->label('Status')
                        ->options([
                            ['value' => PostStatus::Published->value, 'label' => 'Published'],
                            ['value' => PostStatus::Draft->value, 'label' => 'Draft'],
                        ]),
                    Select::make('author_id')
                        ->label('Author')
                        ->searchable()
                        ->query(
                            fn (array $deps, string $search): array => User::query()
                                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                                ->orderBy('name')
                                ->limit(20)
                                ->get(['id', 'name'])
                                ->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])
                                ->values()
                                ->all()
                        ),
                ])
                ->tabs([
                    Tab::make('all')->label('All'),
                    Tab::make('published')->label('Published')
                        ->query(fn ($q) => $q->where('status', PostStatus::Published)),
                    Tab::make('draft')->label('Draft')
                        ->query(fn ($q) => $q->where('status', PostStatus::Draft)),
                ])
                ->defaultSort('published_at', 'desc')
                ->paginate(25, [10, 25, 50])
                ->query(fn () => Post::query()->with(['author', 'category']))
                ->rowActions([
                    $s->action('edit')->label('Edit')->handle(
                        fn (ActionCtx $ctx): Effects => Effects::make()
                            ->redirect("/admin/posts/{$ctx->key()}/edit"),
                        needs: ['row'],
                    ),
                    $s->action('togglePublish')->label('Publish / unpublish')->handle(
                        function (ActionCtx $ctx): Effects {
                            $post = Post::query()->whereKey($ctx->key())->firstOrFail();
                            $published = $post->status !== PostStatus::Published;
                            $post->update([
                                'status' => $published ? PostStatus::Published : PostStatus::Draft,
                                'published_at' => $published ? ($post->published_at ?? now()) : null,
                            ]);

                            return Effects::make()
                                ->notify($published ? 'Post published' : 'Post unpublished')
                                ->refreshTable('posts');
                        },
                        needs: ['row'],
                    ),
                    DeleteAction::make($s, name: 'delete', using: function (ActionCtx $ctx): void {
                        Post::whereKey($ctx->key())->delete();
                    }),
                ])
                ->bulkActions([
                    $s->action('bulkPublish')->label('Publish selected')->color('primary')->handle(
                        function (ActionCtx $ctx): Effects {
                            $count = Post::whereKey($ctx->selection)->update([
                                'status' => PostStatus::Published,
                                'published_at' => now(),
                            ]);

                            return Effects::make()
                                ->notify("Published {$count} post(s)")
                                ->refreshTable('posts');
                        },
                        needs: ['selection'],
                    ),
                    DeleteAction::make($s, name: 'delete-selected', bulk: true, using: function (ActionCtx $ctx): Effects {
                        $count = Post::whereKey($ctx->selection)->delete();

                        return Effects::make()
                            ->notify("Deleted {$count} post(s)")
                            ->refreshTable('posts');
                    }),
                ])
                ->toNode(),
        ]);
    }
}
