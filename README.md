# Archiveless

[![Testing Suite](https://github.com/alleyinteractive/archiveless/actions/workflows/unit-test.yml/badge.svg)](https://github.com/alleyinteractive/archiveless/actions/workflows/unit-test.yml)

Hide WordPress posts from archives, which includes the index page, search
results, date archives, author archives, and term lists.

Adds `<meta name='robots' content='noindex,nofollow' />` meta to the head to
restrict inclusion in web searches.

## Background

This plugin provides a way for content to live inside WordPress and still be
accessible by a direct URL but appear hidden everywhere else. Useful for culling
older content that shouldn't appear in search results because it is untimely.

## Usage

By default, the plugin will prevent archiveless posts from appearing on the page
in the main query only (outside of singular requests). It won't any query
outside the 'main' query on the page by default.

Archiveless posts can be excluded from normal queries by passing `exclude_archiveless`:

```php
$posts = get_posts(
  [
    'exclude_archiveless' => true,
    'suppress_filters'    => false,
    // ...
  ]
);
```

### Install

The plugin includes uncompiled Javascript. You can install the plugin by
tracking the `main-built` branch or by using a `*-built` tag. Otherwise, you can
download the plugin and compile the assets manually:

```bash
npm install
npm run build
```

## Maintainers

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.co/careers/).

![Alley logo](https://avatars.githubusercontent.com/u/1733454?s=200&v=4)

## License

Licensed under GPL v2.