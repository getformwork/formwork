# Changelog

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
