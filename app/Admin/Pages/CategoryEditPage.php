<?php

namespace App\Admin\Pages;

use App\Models\Category;
use Tbtop\Admin\Actions\ActionCtx;
use Tbtop\Admin\Actions\Effects;
use Tbtop\Admin\Dsl\Actions\FormActions;
use Tbtop\Admin\Dsl\Node;
use Tbtop\Admin\Dsl\S;
use Tbtop\Admin\Pages\Page;

class CategoryEditPage extends Page
{
    public static function path(): string
    {
        return 'categories/{category}/edit';
    }

    public function title(): string
    {
        return 'Edit category';
    }

    public function view(S $s): Node
    {
        $category = Category::findOrFail(request()->route('category'));

        return $s->form('category', [
            $s->text('name')->label('Name')->required()->rules('max:120'),
            $s->slug('slug')->label('Slug')->required()
                ->fromField('name')
                ->rules(['max:120', 'regex:/^[a-z0-9-]+$/', "unique:categories,slug,{$category->id}"]),
            $s->textarea('description')->label('Description')->rules('nullable|string'),
            FormActions::saveCancel($s, '/admin/categories'),
        ])
            ->record($category->toArray())
            ->onSubmit(function (ActionCtx $ctx): Effects {
                Category::findOrFail($ctx->params['category'] ?? null)->update($ctx->form);

                return Effects::make()->notify('Saved')->redirect('/admin/categories');
            });
    }
}
