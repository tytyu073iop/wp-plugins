# My Catalog

Hybrid WordPress plugin starter with:

- classic PHP plugin bootstrap
- example shortcode support
- Gutenberg block build tooling
- local `wp-env` development config

## Quick start

```bash
npm install
npm run env:start
npm run start
```

Then open the local WordPress site created by `wp-env` and activate the `My Catalog` plugin.

## Structure

- `my-catalog.php`: main plugin bootstrap
- `includes/`: PHP classes
- `src/`: block source files
- `build/`: generated assets after running the build
- `.wp-env.json`: local WordPress environment config
