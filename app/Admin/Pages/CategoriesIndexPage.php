<?php

namespace App\Admin\Pages;

use App\Models\Category;
use Tbtop\Admin\Actions\ActionCtx;
use Tbtop\Admin\Actions\Effects;
use Tbtop\Admin\Dsl\Actions\CreateAction;
use Tbtop\Admin\Dsl\Actions\DeleteAction;
use Tbtop\Admin\Dsl\Column;
use Tbtop\Admin\Dsl\Node;
use Tbtop\Admin\Dsl\S;
use Tbtop\Admin\Pages\Page;

class CategoriesIndexPage extends Page
{
    public static function path(): string
    {
        return 'categories';
    }

    public static function nav(): ?array
    {
        return ['group' => 'Content', 'label' => 'Categories', 'order' => 2, 'icon' => 'folder'];
    }

    public function title(): string
    {
        return 'Categories';
    }

    public function view(S $s): Node
    {
        return $s->stack([
            $s->table('categories')
                ->headerActions([
                    CreateAction::make(
                        $s,
                        form: $s->form('category', [
                            $s->text('name')->label('Name')->required()->rules('max:120'),
                            $s->slug('slug')->label('Slug')->required()
                                ->set('fromField', 'name')
                                ->rules(['max:120', 'regex:/^[a-z0-9-]+$/', 'unique:categories,slug']),
                            $s->textarea('description')->label('Description')->rules('nullable|string'),
                        ]),
                        storeUsing: function (ActionCtx $ctx): Effects {
                            Category::create($ctx->form);

                            return Effects::make()
                                ->notify('Category created')
                                ->closeModal()
                                ->refreshTable('categories');
                        },
                        defaultRecord: ['name' => '', 'slug' => '', 'description' => null],
                        title: 'New category',
                    )->label('New category'),
                ])
                ->columns([
                    Column::make('name')->label('Name')->searchable()->sortable(),
                    Column::make('slug')->label('Slug'),
                    Column::make('posts_count')->label('Posts')->align('right'),
                ])
                ->query(fn () => Category::query()->withCount('posts'))
                ->rowActions([
                    $s->action('edit')->label('Edit')->handle(
                        fn (ActionCtx $ctx): Effects => Effects::make()
                            ->redirect("/admin/categories/{$ctx->key()}/edit"),
                        needs: ['row'],
                    ),
                    DeleteAction::make($s, using: fn (ActionCtx $ctx) => Category::whereKey($ctx->key())->delete()),
                ])
                ->toNode(),
        ]);
    }
}
