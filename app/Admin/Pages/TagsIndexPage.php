<?php

namespace App\Admin\Pages;

use App\Models\Tag;
use Tbtop\Admin\Actions\ActionCtx;
use Tbtop\Admin\Actions\Effects;
use Tbtop\Admin\Dsl\Actions\CreateAction;
use Tbtop\Admin\Dsl\Actions\DeleteAction;
use Tbtop\Admin\Dsl\Column;
use Tbtop\Admin\Dsl\Node;
use Tbtop\Admin\Dsl\S;
use Tbtop\Admin\Pages\Page;

class TagsIndexPage extends Page
{
    public static function path(): string
    {
        return 'tags';
    }

    public static function nav(): ?array
    {
        return ['group' => 'Content', 'label' => 'Tags', 'order' => 3, 'icon' => 'tag'];
    }

    public function title(): string
    {
        return 'Tags';
    }

    public function view(S $s): Node
    {
        return $s->stack([
            $s->table('tags')
                ->headerActions([
                    CreateAction::make(
                        $s,
                        form: $s->form('tag', [
                            $s->text('name')->label('Name')->required()->rules('max:80'),
                            $s->slug('slug')->label('Slug')->required()
                                ->set('fromField', 'name')
                                ->rules(['max:80', 'regex:/^[a-z0-9-]+$/', 'unique:tags,slug']),
                        ]),
                        storeUsing: function (ActionCtx $ctx): Effects {
                            Tag::create($ctx->form);

                            return Effects::make()
                                ->notify('Tag created')
                                ->closeModal()
                                ->refreshTable('tags');
                        },
                        defaultRecord: ['name' => '', 'slug' => ''],
                        title: 'New tag',
                    )->label('New tag'),
                ])
                ->columns([
                    Column::make('name')->label('Name')->searchable()->sortable(),
                    Column::make('slug')->label('Slug'),
                    Column::make('posts_count')->label('Posts')->align('right'),
                ])
                ->query(fn () => Tag::query()->withCount('posts'))
                ->rowActions([
                    $s->action('edit')->label('Edit')->handle(
                        fn (ActionCtx $ctx): Effects => Effects::make()
                            ->redirect("/admin/tags/{$ctx->key()}/edit"),
                        needs: ['row'],
                    ),
                    DeleteAction::make($s, using: fn (ActionCtx $ctx) => Tag::whereKey($ctx->key())->delete()),
                ])
                ->toNode(),
        ]);
    }
}
