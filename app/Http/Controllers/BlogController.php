<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = Post::query()
            ->with(['author', 'category', 'tags'])
            ->where('status', PostStatus::Published)
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('blog.index', compact('posts'));
    }

    public function show(string $slug): View
    {
        $post = Post::query()
            ->with(['author', 'category', 'tags'])
            ->where('slug', $slug)
            ->where('status', PostStatus::Published)
            ->firstOrFail();

        return view('blog.show', compact('post'));
    }
}
