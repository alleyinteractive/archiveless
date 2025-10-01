# Changelog

All notable changes to this plugin will be documented in this file.

## v1.2.0

- Bump PHP requirement to 8.2.
- Refactor to use TypeScript and the latest standards from Alley's
  `create-wordpress-plugin` package. Admin entry was renamed but the
  functionality remains the same.

## v1.1.1

- Fix unit tests and ensure that tests pass with twentytwentythree.
- Change visibility of post stati in query.

## v1.1.0

- Ensure that when `any` is passed as the `post_status` that archiveless does
  not modify the query. The query should return archiveless posts normally then
  with an unmodified query.
- Call out that `get_posts()` calls will not have archiveless posts returned by
  default. This logic in the plugin broke backward compatibility with some
  existing plugin use.

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
