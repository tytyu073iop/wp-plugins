# My Catalog

WordPress catalog plugin with:

- custom post types for `product` and `news`
- taxonomies for product categories, product attributes, and news categories
- custom meta boxes for product and news fields
- shortcodes `[product_table]` and `[product_filters]` with DataTables-powered filtering and sorting
- Gutenberg `News Carousel` block with Swiper-based carousel output
- Gutenberg `Product Filters` block for separate product table filters
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
- `includes/`: PHP classes for content types, block rendering, shortcodes, and REST logic
- `src/`: block source files
- `build/`: generated assets after running the build
- `.wp-env.json`: local WordPress environment config

## Frontend Embeds

```text
[product_table limit="12" category="featured" columns="image,title,price,category,sku,external"]
```

For separate filters, give the table an ID and point the filters at it:

```text
[product_filters target="featured-products"]
[product_table table_id="featured-products" limit="12"]
```

Use the `News Carousel` block in the block editor to insert the news slider.
Use the `Product Filters` block to place category and attribute filters separately from the table.

## Theme override

To override the news carousel slide template in a theme, add either:

- `my-catalog/news-slide.php`
- `my-catalog-news-slide.php`
