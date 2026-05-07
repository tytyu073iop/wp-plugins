# My Catalog — AGENTS.md

## Project structure

Single WordPress plugin in `my-catalog/`. No monorepo, no Composer, no TypeScript.
All PHP dependencies are self-contained; front-end assets (DataTables 1.13.8, Swiper 11.1.4) load from CDN.

## First setup

```sh
cd my-catalog
npm install
npm run build          # build/ is gitignored; required before block works
npm run env:start      # wp-env with WP 6.8 (branch, not tag)
```

## Available commands (run from `my-catalog/`)

| Command | What it does |
|---|---|
| `npm run build` | Webpack build via `@wordpress/scripts` (output to `build/`) |
| `npm run start` | Webpack watch mode |
| `npm run lint:js src` | ESLint (default config from wp-scripts) |
| `npm run lint:css src` | Stylelint |
| `npm run format` | Prettier via wp-scripts |
| `npm run env:start/stop/destroy` | wp-env lifecycle |

No test infrastructure exists (no PHPUnit, no Jest, no E2E).

## Architecture

- **Entrypoint:** `my-catalog.php` — defines constants, requires 4 includes, registers activation/deactivation hooks
- **Plugin coordinator:** `My_Catalog_Plugin` (singleton, `instance()`) — creates 3 modules, hooks `register_block()` on `init`, `load_textdomain()` on `plugins_loaded`
- **Modules:**
  - `My_Catalog_Core` — registers CPTs (`product` slug `products`, `news` slug `news`), taxonomies, meta boxes
  - `My_Catalog_Product_Table` — `[product_table]` shortcode, admin settings page, REST endpoint `GET my-catalog/v1/product-table`
  - `My_Catalog_News_Carousel` — Gutenberg block render callback, Swiper carousel
- **Block:** `my-catalog/news-carousel`, API v3, dynamic (server-rendered, `save()` returns `null`), uses `ServerSideRender` in editor
- **Theme override:** place `my-catalog/news-slide.php` or `my-catalog-news-slide.php` in theme

## Gotchas

- **`build/` is gitignored** — always run `npm run build` after clone or checkout
- **`product_tag` taxonomy labels say "Product Attributes"** — it's non-hierarchical, conceptually *attributes*, not tags
- **Meta keys use `_my_catalog_` prefix** — underscore = protected (hidden from custom fields UI)
- **`slidesPerView` block attribute is hardcoded to `1` in PHP** — `class-my-catalog-news-carousel.php:77` ignores the attribute value
- **snake_case in shortcodes vs camelCase in block attributes** — the `render_block()` bridge maps between them (e.g. `autoplayDelay` → `autoplay_delay`)
- **REST endpoint is public** — `GET my-catalog/v1/product-table` has `permission_callback => '__return_true'`
- **`uninstall.php` only removes one option** — posts, terms, and post meta are left behind
- **Settings at Settings > My Catalog** — configures default visible columns for product table
- **No sanitization config files** — ESLint, Stylelint, Prettier all use wp-scripts defaults

## Branches

- `main` — active, 5 commits
- `turn-news-into-block` — diverged, 2 commits ahead (not merged; significantly refactors news-carousel)
