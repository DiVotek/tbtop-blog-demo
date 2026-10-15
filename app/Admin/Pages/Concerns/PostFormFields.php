<?php

namespace App\Admin\Pages\Concerns;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Tbtop\Admin\Dsl\Node;
use Tbtop\Admin\Dsl\S;

trait PostFormFields
{
    /** @return list<Node|\Tbtop\Admin\Dsl\Fields\Field> */
    protected function postFormSections(S $s, string $slugUniqueRule): array
    {
        return [
            $s->section(['title' => 'Content'], [
                $s->text('title')->label('Title')->required()->rules('max:200'),
                $s->slug('slug')->label('Slug')->required()
                    ->set('fromField', 'title')
                    ->rules(['max:200', 'regex:/^[a-z0-9-]+$/', $slugUniqueRule]),
                $s->textarea('excerpt')->label('Excerpt')->rules('nullable|string|max:500'),
                $s->richtext('body')->label('Body')->set('placeholder', 'Write the post…'),
            ]),
            $s->section(['title' => 'Publishing'], [
                $s->select('status')->label('Status')
                    ->set('options', [
                        ['value' => PostStatus::Draft->value, 'label' => 'Draft'],
                        ['value' => PostStatus::Published->value, 'label' => 'Published'],
                    ])
                    ->required()
                    ->rules('in:draft,published'),
                $s->date('published_at')->label('Published at')->rules('nullable|date')
                    ->hiddenIf('status', '!=', PostStatus::Published->value),
                $s->media('cover_media_id')->label('Cover image')
                    ->accept(['image/*'])
                    ->rules('nullable|integer'),
                $s->relation('category_id')->label('Category')
                    ->query(fn () => Category::query()->orderBy('name'))
                    ->labelKey('name')
                    ->searchable()
                    ->rules('nullable|exists:categories,id'),
                $s->select('tag_ids')->label('Tags')->multiple()
                    ->set('options', Tag::query()->orderBy('name')->get()
                        ->map(fn (Tag $tag): array => ['value' => (string) $tag->id, 'label' => $tag->name])
                        ->all())
                    ->rules('nullable|array'),
                $s->relation('author_id')->label('Author')
                    ->query(fn () => User::query()->orderBy('name'))
                    ->labelKey('name')
                    ->searchable()
                    ->rules('nullable|exists:users,id'),
            ]),
        ];
    }

    /** @param  array<string, mixed>  $form */
    protected function syncPostTags(array $form, Post $post): void
    {
        $tagIds = array_map('intval', $form['tag_ids'] ?? []);
        $post->tags()->sync($tagIds);
    }
}
