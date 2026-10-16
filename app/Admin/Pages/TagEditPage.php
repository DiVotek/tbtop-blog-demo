<?php

namespace App\Admin\Pages;

use App\Models\Tag;
use Tbtop\Admin\Actions\ActionCtx;
use Tbtop\Admin\Actions\Effects;
use Tbtop\Admin\Dsl\Actions\FormActions;
use Tbtop\Admin\Dsl\Node;
use Tbtop\Admin\Dsl\S;
use Tbtop\Admin\Pages\Page;

class TagEditPage extends Page
{
    public static function path(): string
    {
        return 'tags/{tag}/edit';
    }

    public function title(): string
    {
        return 'Edit tag';
    }

    public function view(S $s): Node
    {
        $tag = Tag::findOrFail(request()->route('tag'));

        return $s->form('tag', [
            $s->text('name')->label('Name')->required()->rules('max:80'),
            $s->slug('slug')->label('Slug')->required()
                ->fromField('name')
                ->rules(['max:80', 'regex:/^[a-z0-9-]+$/', "unique:tags,slug,{$tag->id}"]),
            FormActions::saveCancel($s, '/admin/tags'),
        ])
            ->record($tag->toArray())
            ->onSubmit(function (ActionCtx $ctx): Effects {
                Tag::findOrFail($ctx->params['tag'] ?? null)->update($ctx->form);

                return Effects::make()->notify('Saved')->redirect('/admin/tags');
            });
    }
}
