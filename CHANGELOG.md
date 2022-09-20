# Changelog

All notable changes to this plugin will be documented in this file.

## 1.0.1

- Bug fix from previous use of the archiveless plugin that broke backwards
  compatibility: only include archiveless posts when no `post_status` is
  specified for the query. For `get_posts()` calls, this means that archiveless
  posts will never be included by default since `get_posts()` sets a default
  post status. To include archiveless posts with `get_posts()`, you can include
  `archiveless` in the `post_status` or pass `include_archiveless` set to `true.
- Introduces `include_archiveless` query paramater.

## 1.0.0

- Initial release
