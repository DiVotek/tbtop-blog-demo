<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased">
<article class="mx-auto max-w-3xl px-4 py-10">
    <p class="mb-6"><a href="{{ route('blog.index') }}" class="text-sm text-blue-600 hover:underline">← All posts</a></p>
    <p class="text-xs uppercase tracking-wide text-zinc-500">{{ $post->category?->name }}</p>
    <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $post->title }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ $post->author?->name }} · {{ $post->published_at?->format('F j, Y') }}</p>
    @if ($post->excerpt)
        <p class="mt-6 text-lg text-zinc-700">{{ $post->excerpt }}</p>
    @endif
    <div class="prose prose-zinc mt-8 max-w-none">
        <p class="text-zinc-600">Full richtext rendering is omitted on the public site in this demo; open the post in the admin editor to see the body.</p>
    </div>
    @if ($post->tags->isNotEmpty())
        <ul class="mt-8 flex flex-wrap gap-2">
            @foreach ($post->tags as $tag)
                <li class="rounded-full bg-zinc-200 px-3 py-1 text-xs">{{ $tag->name }}</li>
            @endforeach
        </ul>
    @endif
</article>
</body>
</html>
