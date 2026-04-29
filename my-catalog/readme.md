# My Catalog

WordPress catalog plugin with:

- custom post types for `product` and `news`
- taxonomies for product categories, product attributes, and news categories
- custom meta boxes for product and news fields
- shortcode `[product_table]` with DataTables-powered filtering and sorting
- shortcode `[news_carousel]` with Swiper-based carousel output
- Gutenberg block build tooling and local `wp-env` config

## Quick start

```bash
npm install
npm run env:start
npm run start
```

Then open the local WordPress site created by `wp-env` and activate the `My Catalog` plugin.

## Structure

- `my-catalog.php`: main plugin bootstrap
- `includes/`: PHP classes for content types, shortcodes, and REST logic
- `src/`: block source files
- `build/`: generated assets after running the build
- `.wp-env.json`: local WordPress environment config

## Shortcodes

```text
[product_table limit="12" category="featured" columns="image,title,price,category,sku,external"]
[news_carousel limit="6" category="promotions" slides_per_view="3" autoplay="true"]
```

## Theme override

To override the news carousel slide template in a theme, add either:

- `my-catalog/news-slide.php`
- `my-catalog-news-slide.php`
