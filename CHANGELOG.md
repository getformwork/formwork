# Changelog

## [0.9.3](https://github.com/giuscris/formwork/releases/tag/0.9.3) (2018-10-18)

**Bug fixes**

 * Fix missing `Page::$frontmatter` that prevented page updating from Admin panel

## [0.9.2](https://github.com/giuscris/formwork/releases/tag/0.9.2) (2018-10-18)

**Enhancements**

 * **Add PHPDoc**
 * Cleanup code

**Bug fixes**

 * Fix typos in `Image` class
 * Fix Authentication controller
 * Add missing properties to `Page` class
 * Remove duplicate keys in `MimeType` class

## [0.9.1](https://github.com/giuscris/formwork/releases/tag/0.9.1) (2018-10-16)

**Enhancements**

 * **Use image picker when inserting an image from editor toolbar**
 * Confirm image picker selection on double click on thumbnail
 * Add late callback instantiation in `Router` with `Class@method` syntax
 * Update Admin routes with new `Class@method` syntax to save memory
 * Avoid passing modals each time a view is rendered

**Bug fixes**

 * Fix persistent tooltip when a button is disabled after click
 * Fix missing `updates.*` permissions

## [0.9.0](https://github.com/giuscris/formwork/releases/tag/0.9.0) (2018-10-13)

**Enhancements**

 * **Add user roles and permissions** ([#9](https://github.com/giuscris/formwork/pull/9))
 * **Limit access after a certain amount of failed login attempts** ([#10](https://github.com/giuscris/formwork/pull/10))
 * Use HTTP status 400 instead of 403 when CSRF token is not valid
 * Show a `no-drop` cursor and light red background when page reordering is not possible
 * Improve users list appearance especially for small screen sizes
 * Slightly reduce sidebar width to have more room for content
 * Improve pages list columns sizing
 * Use user language when provided instead of `admin.lang` option
 * Display file size next to uploaded file names

**Bug fixes**

 * **Fix notification spacing issues when page is scrolled**
 * Fix missing error notification when editing users is forbidden
 * Check uploaded avatar existence before user update
 * Fix language strings
 * Fix wrong exception type in `Uploader` class

## [0.8.1](https://github.com/giuscris/formwork/releases/tag/0.8.1) (2018-10-08)

**Enhancements**

 * Improve exception messages
 * Remove unneeded loader.php

**Bug fixes**

 * **Fix wrong platform requirements in composer.json**

## [0.8.0](https://github.com/giuscris/formwork/releases/tag/0.8.0) (2018-10-06)

**Enhancements**

 * **Add backup feature to export site to a .zip archive**
 * **Add image processing feature, by now used to resize avatars to square**
 * **Improve session cookie security and consistency across supported PHP versions**
 * Use monospace font for values in Options > Info
 * Allow language strings in schemes

**Bug fixes**

 * Fix redirect loop on Formwork Admin registration
 * Fix an issue which made possible changing another user's password
 * Fix error handler ignoring `@` operator and `error_reporting` directive
 * Fix unrecognized percent-encoded URIs

## [0.7.2](https://github.com/giuscris/formwork/releases/tag/0.7.2) (2018-09-15)

**Enhancements**

 * Change Updater check frequency from 1 hour to 15 minutes

**Bug fixes**

 * Fix `Session::start()` not applying session options in PHP < 7.0
 * Fix Options > Updates parse error triggered in PHP < 7.0
 * Fix incorrect icons in Pages Editor files list

## [0.7.1](https://github.com/giuscris/formwork/releases/tag/0.7.1) (2018-09-13)

**Enhancements**

 * Add Check Updates button to Dashboard quick actions
 * Change Logout notification type from `success` to `info`
 * Add new notification types `info` and `warning`
 * Update icon font
 * Redirect to the Panel URI requested before login

**Bug fixes**

 * Fix Page Editor textarea always being focused after saving/reloading

## [0.7.0](https://github.com/giuscris/formwork/releases/tag/0.7.0) (2018-09-12)

**Enhancements**

 * **Add Updater feature to automatically download new releases from GitHub repository** ([#4](https://github.com/giuscris/formwork/pull/4))
 * **Add Attributes component to Pages Editor to change Page Template or Page Parent** ([#6](https://github.com/giuscris/formwork/pull/6))
 * **Add error and exception handlers displaying a Formwork-styled error page**
 * Add Typography page to show template styles
 * Improve default template menus
 * Retain cursor position in Pages Editor after saving/reloading
 * Add new entries to Options > Info tab
 * Add error notification when POST request size is greater than allowed
 * Display Session Strict Mode information in Options > Info tab
 * Add `admin.logout_redirect` option to decide where to redirect after logout (Login or Site Home Page)
 * Display loaded php.ini filename in Options > Info tab
 * Add `range` field type
 * Add keyboard shortcut (`CTRL/Cmd + S`) to all views with Save command
 * Add nginx.conf file with NGINX rewrite rules

**Bug fixes**

 * Fix Tabs component wrapping
 * Fix session temporary file persistence after logout
 * Fix long notification text overflow
 * Fix vertical scrollbar always visible in IE/Edge

## [0.6.12](https://github.com/giuscris/formwork/releases/tag/0.6.12) (2018-08-27)

**Enhancements**

 * Add logout notification
 * Add box shadow to pages list items when sorting pages
 * Make buttons in Pages list look the same as in Page editor

**Bug fixes**

 * Fix method names which broke PHP ^5.5.0 compatibility

## [0.6.11](https://github.com/giuscris/formwork/releases/tag/0.6.11) (2018-07-24)

**Enhancements**

 * Make notifications disappear by clicking on them (closes [#3](https://github.com/giuscris/formwork/issues/3))
 * Add Delete Page button to Pages editor
 * Add Pages editor keyboard shortcuts for bold, italic and save commands
 * Add Preview button to Pages editor

**Bug fixes**

 * Fix unintended non-static methods which triggered a PHP warning

## [0.6.10](https://github.com/giuscris/formwork/releases/tag/0.6.10) (2018-07-18)

**Enhancements**

 * Add support for PHP Yaml extension
 * Add `parsers.use_php_yaml` option to decide PHP Yaml extension behavior
 * Add current location to Admin Panel title
 * Use new `togglegroup` fields instead of checkboxes in Admin > Options

**Bug fixes**

 * Fix mixed content blocked over HTTPS connections
 * Fix incorrect position of chart tooltips in Firefox
 * Fix first level page creation when parent is an instance of `Site` class
 * Fix options defaults not available before `system.yml` loading

## [0.6.9](https://github.com/giuscris/formwork/releases/tag/0.6.9) (2018-07-10)

 * Initial release
