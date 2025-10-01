# Archiveless

[![Testing Suite](https://github.com/alleyinteractive/archiveless/actions/workflows/all-pr-tests.yml/badge.svg?branch=develop)](https://github.com/alleyinteractive/create-wordpress-plugin/actions/workflows/all-pr-tests.yml)

Excludes specific WordPress posts from archives (homepage, search,
date/author/term archives).

Adds `<meta name='robots' content='noindex,nofollow' />` meta to the head to
restrict inclusion in web searches.

## Background

This plugin provides a way for content to live inside WordPress and still be
accessible by a direct URL but appear hidden everywhere else. Useful for culling
older content that shouldn't appear in search results because it is untimely.

## Usage

By default, the plugin will prevent archiveless posts from appearing on the page. This is limited to the [main
query](https://developer.wordpress.org/reference/functions/is_main_query/) of
the page. It will not affect other queries by default.

Archiveless posts can be excluded from normal queries by passing
`exclude_archiveless`:

```php
$query = new WP_Query(
  [
    'exclude_archiveless' => true,
    // ...
  ]
);

// Via 'pre_get_posts'.
add_action(
  'pre_get_posts',
  function ( $query ) {
    if ( special_condition() ) {
      $query->set( 'exclude_archiveless', true );
    }
  }
);
```

### Handling archiveless posts with `get_posts()` calls

Queries made with `get_posts()` will always exclude archiveless posts by default
since `get_posts()` sets a default `post_status` of `publish`. To include
archiveless posts, you can specify the `post_status` of `[ 'publish',
'archiveless' ]` or pass `include_archiveless` set to true:

```php
// $post_ids will include archiveless posts.
$post_ids = get_posts(
  [
    'fields'              => 'ids',
    'include_archiveless' => true,
    'suppress_filters'    => false,
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

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed
recently.

## Maintainers

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.com/careers/).

![Alley logo](https://avatars.githubusercontent.com/u/1733454?s=200&v=4)

## License

This software is released under the terms of the GNU General Public License
version 2 or any later version.
