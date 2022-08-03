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

### Install

The plugin includes uncompiled Javascript. You can install the plugin by
tracking the `main-built` branch or by using a `*-built` tag. Otherwise, you can
download the plugin and compile the assets manually:

```bash
npm install
npm run build
```

## Maintainers

![Alley logo](https://avatars.githubusercontent.com/u/1733454?s=200&v=4)

## License

Licensed under GPL v2.