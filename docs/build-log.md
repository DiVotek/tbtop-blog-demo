# Build log — tbtop blog demo

Chronological journal for the quick-start article. Times are approximate wall-clock for this agent run (2026-10-15, UTC).

## 0. Repo bootstrap (~2 min)

- Cloned empty `DiVotek/tbtop-blog-demo`; `master` already had a one-line `README.md` (sufficient PR base).
- Created branch `cursor/tbtop-blog-demo-350d` for all application work.

## 1. Environment (~15 min)

**Commands**

```bash
# PHP 8.4 required by tbtop/admin ^0.7 (Packagist constraint)
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get install -y php8.4-cli php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-sqlite3 php8.4-gd php8.4-intl
curl -sS https://getcomposer.org/installer | php -- --install-dir=$HOME/bin --filename=composer
```

**Surprise:** `tbtop/admin` v0.7.0 requires **PHP ^8.4**, not called out loudly on the docs home page. Fresh Ubuntu images often ship 8.3 only — install 8.4 first or `composer require` fails.

**Docs friction:** Common install URLs like `https://docs.tbtop.dev/installation` return 404. The working entry points are:

- https://docs.tbtop.dev (introduction + “Where to start”)
- Package README in `vendor/tbtop/admin/packages/php/README.md` after install
- Reference app under `vendor/tbtop/admin/apps/demo/` (not linked from every doc page)

## 2. Fresh Laravel + Composer packages (~5 min)

```bash
composer create-project laravel/laravel laravel-blog
cd laravel-blog
php8.4 composer require tbtop/admin:^0.7 inertiajs/inertia-laravel:^3.1 laravel/sanctum:^4.3 tightenco/ziggy:^2.4
php8.4 composer require laravel/mcp:^1.0   # optional, for MCP endpoint
```

**Files touched:** `composer.json`, `composer.lock` (generated).

## 3. Panel registration before `admin:install --auth` (~10 min)

**Docs:** [wiring.md](https://github.com/DiVotek/tbtop/blob/v0.7.0/packages/php/docs/ai/wiring.md) (in vendor) states `admin:install --auth` needs a registered panel.

**Commands**

```bash
php8.4 artisan vendor:publish --tag=tbtop-admin-config
php8.4 artisan admin:install
```

**Files created**

| File | Why |
|------|-----|
| `config/tbtop-admin.php` | Published; set `'panels' => [\App\Admin\AdminPanel::class]` |
| `app/Admin/AdminPanel.php` | Panel id `admin`, prefix, `discoverPages`, `rootView('admin')`, MCP middleware |
| `resources/views/admin.blade.php` | Inertia root for admin Vite entry |
| `resources/js/admin.tsx` | Resolves `@tbtop/inertia-admin` for `admin/page` |
| `resources/css/admin.css` | Tailwind v4 source importing client styles + `@source` PHP |

**Stuck:** `php artisan admin:install --auth` failed with:

```text
No panel registered: add one to tbtop-admin.panels before --auth.
```

**Stuck:** After adding the panel, boot failed until `app/Admin/Pages/` existed and `php artisan tbtop:clear-cached-pages` was run (stale discovery cache from earlier attempts).

**Wrong import once:** `Tbtop\Admin\Panels\NavGroup` does not exist — correct class is `Tbtop\Admin\Navigation\NavGroup` (copied from reference `AdminPanel.php`).

**Auth scaffold**

```bash
php8.4 artisan admin:install --auth
```

Published empty subclasses:

- `app/Admin/Pages/Auth/LoginPage.php`
- `app/Admin/Pages/Auth/ForgotPasswordPage.php`
- `app/Admin/Pages/Auth/ResetPasswordPage.php`

**Guest redirect** (documented in install output, not auto-patched):

```php
// bootstrap/app.php
$middleware->redirectGuestsTo(
    fn (): string => \Tbtop\Admin\Auth\LoginPage::currentUrl() ?? '/admin/login',
);
```

## 4. Frontend wiring (~12 min)

**Commands**

```bash
npm install @tbtop/inertia-admin@0.7.0 react@^19 react-dom@^19 @inertiajs/react@^3.3 @vitejs/plugin-react@^4 --legacy-peer-deps
```

Added `.npmrc` with `legacy-peer-deps=true` because `@vitejs/plugin-react@4` conflicts with Vite 8’s peer range on a stock Laravel 12 app.

**Files edited**

| File | Change |
|------|--------|
| `vite.config.js` | Added `resources/css/admin.css`, `resources/js/admin.tsx`, `@vitejs/plugin-react`, `tailwindcss()` |
| `package.json` / `package-lock.json` | Client deps |

```bash
npm run build
```

Admin pages 500 without a built manifest or running `npm run dev` — called out in the reference README.

## 5. Domain model (~20 min)

**Migration:** `database/migrations/2026_10_15_100000_create_blog_tables.php`

- `posts`: title, slug, excerpt, JSON `body`, `status` (`draft`/`published`), `published_at`, `cover_media_id`, `author_id`, `category_id`
- `categories`, `tags`, `post_tag` pivot

**Models / enum**

- `app/Models/Post.php`, `Category.php`, `Tag.php`
- `app/Enums/PostStatus.php`

**Seeders**

- `database/seeders/DatabaseSeeder.php` — demo admin `admin@blog.demo` / `password`
- `database/seeders/BlogSeeder.php` — curated posts + factories

**Commands**

```bash
php8.4 artisan migrate --seed
php8.4 artisan storage:link
```

tbtop media migrations run automatically with `migrate`.

## 6. Admin pages (~45 min)

Pattern: one PHP class per route under `app/Admin/Pages/`, discovered automatically (see package README “Your first panel”).

| Page | Path | Notes |
|------|------|-------|
| `DashboardPage` | `/admin/dashboard` | `Stat` widgets for posts/categories/tags |
| `PostsIndexPage` | `/admin/posts` | Search, filters, tabs, row publish toggle, **bulk publish** |
| `PostCreatePage` | `/admin/posts/new` | Shared `PostFormFields` trait |
| `PostEditPage` | `/admin/posts/{post}/edit` | Unpublish action, tag sync on save |
| `CategoriesIndexPage` | `/admin/categories` | `CreateAction` modal |
| `CategoryEditPage` | `/admin/categories/{category}/edit` | |
| `TagsIndexPage` | `/admin/tags` | Same pattern |

**`app/Admin/Pages/Concerns/PostFormFields.php`**

- `richtext('body')`, `media('cover_media_id')`, `relation()` for author/category, `select()->multiple()` for tags (manual `sync()` in submit handlers — not automatic).

**Reference:** Heavily informed by `vendor/tbtop/admin/apps/demo/app/Admin/Pages/PostsIndexPage.php` and `PostFormFields.php`, simplified for a single-locale blog (no `->translatable()`).

**API guess removed:** Initially used `Column::make('posts_count')->counts('posts')` — **no such method** in v0.7; switched to `withCount('posts')` + plain column.

## 7. Public site (~10 min)

| File | Purpose |
|------|---------|
| `app/Http/Controllers/BlogController.php` | Published posts only |
| `resources/views/blog/index.blade.php` | Post list |
| `resources/views/blog/show.blade.php` | Single post (body not rendered — demo focuses on admin) |
| `routes/web.php` | `/` and `/posts/{slug}` |

## 8. MCP (~5 min)

- `AdminPanel::configure()` → `->mcp(['auth:sanctum', 'abilities:tbtop-mcp'])`
- `User` uses `Laravel\Sanctum\HasApiTokens`
- Template: `docs/mcp-claude-desktop.json`

**Not fully E2E-tested** in this run (no token minted in CI). Route registers as `POST admin/mcp` when `laravel/mcp` is present. Token creation per [wiring.md MCP section](https://github.com/DiVotek/tbtop/blob/v0.7.0/packages/php/docs/ai/wiring.md).

## 9. Tests & CI (~15 min)

**Tests**

- `tests/Feature/BlogPublicTest.php`
- `tests/Feature/AdminLoginTest.php`
- `tests/Feature/PostModelTest.php`
- Fixed default `ExampleTest` to use `RefreshDatabase` (home route hits `posts` table).

```bash
php8.4 artisan test
```

**Workflow:** `.github/workflows/tests.yml` — PHP 8.4, `npm ci`, `npm run build`, `artisan test`.

## 10. Screenshots (~10 min)

Saved under `docs/screenshots/`:

| File | Screen |
|------|--------|
| `login.png` | `/admin/login` |
| `dashboard.png` | `/admin/dashboard` |
| `posts-list.png` | `/admin/posts` |
| `post-edit.png` | `/admin/posts/1/edit` |

Captured after `php artisan serve` with seeded data.

## 11. README

`README.md` — PHP 8.4, install steps, demo login, MCP pointer, link to this log.

---

## Feedback for tbtop

1. **PHP 8.4 requirement** — Prominent on Packagist but easy to miss when skimming https://docs.tbtop.dev; a “Requirements” callout on the install path would save time.
2. **Install doc URLs** — Several guessed paths (`/installation`, `/install`) 404; sitemap or stable `/docs/getting-started` slug would help.
3. **`admin:install --auth` ordering** — Error message is good; linking to the exact `panels` config snippet in the same CLI output would help.
4. **Page discovery cache** — `tbtop:clear-cached-pages` needed after failed partial setup; worth mentioning next to discovery in install docs.
5. **`NavGroup` namespace** — Easy to import from wrong namespace; demo source is the source of truth.
6. **Column helpers** — No `counts()` on `Column`; relationship counts need `withCount` + column name (undocumented helper assumed from Filament muscle memory).
7. **Many-to-many tags** — Documented pattern is `relation()` for belongs-to; for belongs-to-many, `select()->multiple()` + manual `sync()` works but is not spelled out in a blog-style recipe.
8. **npm peer deps** — Laravel 12 + Vite 8 + `@vitejs/plugin-react@4` needs `--legacy-peer-deps`; note in install steps for the admin entry.
9. **MCP** — Clear in `wiring.md` once found; surfacing a “copy token” artisan snippet on `docs.tbtop.dev` would complete the story.

---

## Quick command recap (clean clone)

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Login: **admin@blog.demo** / **password** → http://127.0.0.1:8000/admin
