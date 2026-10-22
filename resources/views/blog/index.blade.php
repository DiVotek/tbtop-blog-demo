<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog demo</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased">
<main class="mx-auto max-w-3xl px-4 py-10">
    <header class="mb-10 flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">tbtop blog demo</h1>
        <a href="{{ url('/admin') }}" class="text-sm text-blue-600 hover:underline">Admin</a>
    </header>
    <ul class="space-y-8">
        @foreach ($posts as $post)
            <li class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-zinc-500">{{ $post->category?->name }}</p>
                <h2 class="mt-1 text-xl font-medium">
                    <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-blue-600">{{ $post->title }}</a>
                </h2>
                <p class="mt-2 text-zinc-600">{{ $post->excerpt }}</p>
                <p class="mt-3 text-sm text-zinc-500">{{ $post->author?->name }} · {{ $post->published_at?->format('M j, Y') }}</p>
            </li>
        @endforeach
    </ul>
    <div class="mt-8">{{ $posts->links() }}</div>
</main>
</body>
</html>
