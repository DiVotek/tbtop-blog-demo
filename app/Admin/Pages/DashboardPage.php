<?php

namespace App\Admin\Pages;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Tbtop\Admin\Dsl\Color;
use Tbtop\Admin\Dsl\Node;
use Tbtop\Admin\Dsl\S;
use Tbtop\Admin\Dsl\Stat;
use Tbtop\Admin\Pages\Page;

class DashboardPage extends Page
{
    public static function path(): string
    {
        return 'dashboard';
    }

    public static function nav(): ?array
    {
        return ['group' => 'Overview', 'label' => 'Dashboard', 'order' => 0, 'icon' => 'home'];
    }

    public function title(): string
    {
        return 'Dashboard';
    }

    public function view(S $s): Node
    {
        return $s->stack([
            $s->grid(['cols' => 4], [
                Stat::make('Posts')
                    ->value(fn () => Post::count())
                    ->description('Total posts')
                    ->icon('file-text')
                    ->color(Color::Primary)
                    ->toNode(),
                Stat::make('Published')
                    ->value(fn () => Post::query()->where('status', PostStatus::Published)->count())
                    ->description('Live on the blog')
                    ->icon('globe')
                    ->color(Color::Success)
                    ->toNode(),
                Stat::make('Categories')
                    ->value(fn () => Category::count())
                    ->icon('folder')
                    ->color(Color::Info)
                    ->toNode(),
                Stat::make('Tags')
                    ->value(fn () => Tag::count())
                    ->icon('tag')
                    ->color(Color::Warning)
                    ->toNode(),
            ]),
            $s->displayText('Quick links')->variant('heading'),
            $s->actionsRow([
                $s->action('posts')->label('Manage posts')->url('/admin/posts'),
                $s->action('new')->label('New post')->color('primary')->url('/admin/posts/new'),
            ]),
        ]);
    }
}
