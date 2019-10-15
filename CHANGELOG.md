# Changelog

## [1.2.0](https://github.com/getformwork/formwork/releases/tag/1.2.0) (2019-10-16)

**Enhancements**

- **Restore response caching instead of `Page` object to save large amounts of disk space**
- Add `Response` class
- Add support for options dropdown list to `tags` field
- Add options list to `languages.available` field
- Add `View` class
- Add `Field::render()`
- Add `Fields::render()`
- Refactor admin controllers and views
- Add `fields.label` view
- Send `E_USER_DEPRECATED` errors to standard PHP error handler

**Bug fixes**

- Fix template schemes not specifying `default` property
- Fix children pages list not collapsing on reorder

**Deprecations**
- Deprecate `Site::lang()`
- Deprecate `pages`, `reverse-` and `sortable-children` scheme properties

## [1.1.1](https://github.com/getformwork/formwork/releases/tag/1.1.1) (2019-10-12)

**Bug fixes**

- Fix page views being tracked from administration panel

## [1.1.0](https://github.com/getformwork/formwork/releases/tag/1.1.0) (2019-10-12)

**Enhancements**

- **Improve page reordering on desktop and mobile devices**
- **Make backup before installing updates**
- Use `getenv()` to get IP address avoiding any `$_SERVER` manipulation
- Add `HTTPRequest::isLocalhost()`
- Allow admin registration only on localhost
- Add reorder button to `pages.list` view
- Prepend host to backup file names
- Use current language instead of `lang` in site layout
- Generate `tags` fields from JavaScript

**Bug fixes**

- Fix template schemes not specifying `default` property
- Fix children pages list not collapsing on reorder

## [1.0.0](https://github.com/getformwork/formwork/releases/tag/1.0.0) (2019-10-09)

**Enhancements**

- **Support PHP >= 7.1.3**
- **Add improved Markdown editor based on CodeMirror**
- **Add closure-based template Renderer**
- **Add the possibility to extend schemes**
- **Avoid saving an empty page when creating a new one**
- **Avoid tracking users based on their DNT preference**
- **Improve file uploads**
- **Add the possibility to redefine admin route**
- Store loaded languages in `Site` instead of `Formwork`
- Rename `Metadatum::namespace()` to `Metadatum::prefix()`
- Rename `LocalizedException` to `TranslatedException`
- Rename `Language` to `Translation`
- Make `FileSystem::write()` operate atomically
- Reduce file writes on disk in `Registry`
- Update `Visitor::$bots` list
- Use `https://` in all URI
- Increase CSRF tokens strength
- Regenerate CSRF token on authentication
- Cache `Page` objects directly
- Reset template layout just before rendering
- Return default MIME type `application/octet-stream` if unknown
- Use default field values from template schemes
- Render fields only if visible
- Track page views in `Statistics`
- Add `statistics.enabled` system option

**Bug fixes**

- Fix `HTTPResponse::headers()` mistakenly caching result
- Fix login modal displayed twice on too many attempts
- Fix wrong MIME type for SVG images without XML declaration
- Fix error on clearing cache if not enabled
- Fix `FilesCache::has()` not checking for validity

## [0.12.1](https://github.com/getformwork/formwork/releases/tag/0.12.1) (2019-06-05)

**Bug fixes**

- Fix Composer lock file
- Fix npm vulnerabilities

## [0.12.0](https://github.com/getformwork/formwork/releases/tag/0.12.0) (2019-05-13)

**Enhancements**

- **Add metadata support**
- **Add support for helpers to** `Template`
- **Add the possibility to set HTTP headers in pages frontmatter**
- **Add the possibility to set a canonical route for pages**
- **Add the possibility to redirect to browser preferred language**
- **Add Metadata and Aliases fields to site options**
- Add `Metadatum` class
- Add `Metadata` class
- Move template-related classes to `Formwork\Template` namespace
- Add `TemplateHelpers` class
- Add `Site::template()`
- Use `Template::path()` to get path from `Site::template()`
- Add `Arr` class
- Support dot notation in data getters and pages
- Extract `AssociativeCollection` class
- Add `Field::formName()`
- Use `Field::formName()` in field views
- Avoid using raw POST data from HTTP requests
- Remove `HTTPRequest::postDataFromRaw()`
- Add placeholder support for text-based fields
- Add placeholder support for `tags` field
- Use underscores in frontmatter keys for consistency
- Use site defaults when updating options
- Add `array` field type
- Add `robots` meta tag to Admin views
- Add `Router::rewriteRoute()`
- Add `Router::rewrite()` to rewrite current route with new params
- Add canonical route to index page
- Rename `languages` option to `languages.available`
- Add `HTTPNegotiation` class

**Bug fixes**

- Fix `Layout::scheme()` throwing a `RuntimeException`
- Fix `Validator::validateTags()` not resetting keys after filtering
- Fix `Field::isEmpty()` evaluating fields with `false` value empty
- Revert instance check on `$resource` in `Formwork::run()` from [7c63eba](https://github.com/getformwork/formwork/commit/7c63eba)

## [0.11.2](https://github.com/getformwork/formwork/releases/tag/0.11.2) (2019-05-04)

**Enhancements**

- Revert visibility for non-routable pages from [94fd949](https://github.com/getformwork/formwork/commit/94fd949)
- Extract `Layout` class from `Template`
- Move `Page::absoluteUri()` to `AbstractPage::absoluteUri()`
- Load templates on each template rendering, not only for the current page

**Bug fixes**

- Fix `Page::absoluteUri()` always returning http scheme
- Fix `menu` partial template overwriting `$page` variable

## [0.11.1](https://github.com/getformwork/formwork/releases/tag/0.11.1) (2019-04-26)

**Bug fixes**

- Fix potential memory leak on controller load in `Template` constructor

## [0.11.0](https://github.com/getformwork/formwork/releases/tag/0.11.0) (2019-04-26)

**Enhancements**

- **Add multiple languages support** ([#31](https://github.com/getformwork/formwork/pull/31))
- **Add the possibility to create and edit pages in different languages**
- **Create default language version with** `Pages@create`
- **Add the possibility to delete only a given language version of a page**
- **Accept string language code in** `AbstractPage::uri()`
- Use fallback language when a label is not available in current language
- Move page retrieval from storage to `Site::retrievePage()`
- Add `$default` parameter to `Formwork::option()`
- Update default templates monospace font stack
- Add `type` attribute to all buttons
- Add `$default` parameter to `AbstractController::option()`
- Add dropdowns component
- Add the possibility to set HTTP response status in pages frontmatter
- Move to `Site` the selection of current page
- Pass page to `Template` constructor and load controller in advance
- Add `LanguageCodes::hasCode()`
- Add `Output` class
- Cache and fetch output by using an `Output` object
- Remove deprecated jQuery event shorthands
- Remove deprecated jQuery positional selectors
- Make vendor.min.js from node modules

**Bug fixes**

- Fix `.component` inner spacing
- Fix horizontal scrollbar in `<pre>` blocks in default templates

## [0.10.5](https://github.com/getformwork/formwork/releases/tag/0.10.5) (2019-04-12)

**Enhancements**

- **Add** `Assets` **class**
- **Use** `Template::assets()` **in site layout**
- Add `Str` class
- Use new methods from `Str` class where possible
- Use `assets()` method in Admin views
- Make `Template::$rendering` non static

**Bug fixes**

- **Fix** `Updater::getHeaders()` **not working on PHP < 7.1.0**
- **Fix unquoted dates converted to timestamp**

## [0.10.4](https://github.com/getformwork/formwork/releases/tag/0.10.4) (2019-04-08)

**Bug fixes**

- Fix Symfony Yaml version to ensure PHP 5.6.0 compatibility

## [0.10.3](https://github.com/getformwork/formwork/releases/tag/0.10.3) (2019-04-08)

**Enhancements**

- Use fileinfo to get MIME types if the extension is available
- Add `rel` attribute to pagination links
- Add Read more link to blog template
- Use Symfony Yaml instead of Spyc

**Bug fixes**

- Fix unpublished pages status being overridden by publish dates
- Fix broken page routes on Windows systems

## [0.10.2](https://github.com/getformwork/formwork/releases/tag/0.10.2) (2019-03-26)

**Enhancements**

- **Add tags to blog posts** (closes [#27](https://github.com/getformwork/formwork/issues/27))
- **Add templates inheritance with** `Template::layout()`
- **Update default templates with new layout feature**
- Improve `Template::insert()` providing filename checks
- Make index page files available at `/` route
- Add `visible` checkbox field to template schemes
- Use `Page::content()` to get also the summary
- Update base URI logic in `Pagination` class
- Better French translation, thanks to @MiFrance
- Allow `tagName` and `paginationPage` route params only for listing pages
- Remove unused language strings

**Bug fixes**

- Fix `Page::processData()` directly overwriting data
- Fix `Site::errorPage()` not setting `Site::currentPage()` when rendering
- Fix empty array not considered as such in Pages controller
- Fix `Validator::validateTags()` not filtering empty tags
- Fix New User modal auto-completing username and password

## [0.10.1](https://github.com/getformwork/formwork/releases/tag/0.10.1) (2019-03-18)

**Enhancements**

- **Add French language strings**, thanks to @MiFrance
- Add input reset button to `image` field
- Move strings from template schemes to language files (closes [#15](https://github.com/getformwork/formwork/issues/15))

**Bug fixes**

- Fix cover image issues (closes [#21](https://github.com/getformwork/formwork/issues/21), closes [#18](https://github.com/getformwork/formwork/issues/18) again)
- Fix `cover-image` and `post` templates (closes [#18](https://github.com/getformwork/formwork/issues/18))

## [0.10.0](https://github.com/getformwork/formwork/releases/tag/0.10.0) (2019-03-17)

**Enhancements**

- **Add modal to Pages editor to change page slugs**
- **Keep only a maximum number of backup files**
- **Validate referer before redirecting**
- **Delete invalid cached resources when fetching with** `FilesCache::fetch()`
- Validate page slug, parent page and template in Pages controller
- Redirect to referer in Pages controller when possible
- Add keyboard shortcut for link command <kbd>CTRL/Cmd + K</kbd>

**Bug fixes**

- **Fix error in Register controller which prevented language setting**
- **Fix translated fields not switching to fallback language** (closes [#14](https://github.com/getformwork/formwork/issues/14))
- Fix `Pages@create` not correctly checking if page already exists
- Fix redirect in `Pages@delete`

## [0.9.6](https://github.com/getformwork/formwork/releases/tag/0.9.6) (2019-03-13)

**Enhancements**

- Hide unavailable page actions based on user permissions
- Refactor and cleanup styles

**Bug fixes**

- **Fix error when page id is changed**
- **Fix error when page template is changed**
- **Fix error when `published` attribute is changed in blog pages**
- Fix visits not being logged if cache is enabled (closes [#12](https://github.com/getformwork/formwork/issues/12))

## [0.9.5](https://github.com/getformwork/formwork/releases/tag/0.9.5) (2019-03-04)

**Enhancements**

- **Add Session timeout**
- **Enforce CSRF token regeneration when login page is reloaded**
- **Add legend to Dashboard chart**
- Add headers to pages list and users list

**Bug fixes**

- Fix fields with numeric `0` value considered empty
- Fix filename labels in image picker

## [0.9.4](https://github.com/getformwork/formwork/releases/tag/0.9.4) (2019-02-13)

**Bug fixes**

- Fix Parsedown extension link processing (closes [#11](https://github.com/getformwork/formwork/issues/11))
- Fix numeric template names not being accepted
- Fix `Set-Cookie` header being sent when resuming session

## [0.9.3](https://github.com/getformwork/formwork/releases/tag/0.9.3) (2018-10-18)

**Bug fixes**

- Fix missing `Page::$frontmatter` that prevented page updating from Admin panel

## [0.9.2](https://github.com/getformwork/formwork/releases/tag/0.9.2) (2018-10-18)

**Enhancements**

- **Add PHPDoc**
- Cleanup code

**Bug fixes**

- Fix typos in `Image` class
- Fix Authentication controller
- Add missing properties to `Page` class
- Remove duplicate keys in `MimeType` class

## [0.9.1](https://github.com/getformwork/formwork/releases/tag/0.9.1) (2018-10-16)

**Enhancements**

- **Use image picker when inserting an image from editor toolbar**
- Confirm image picker selection on double click on thumbnail
- Add late callback instantiation in `Router` with `Class@method` syntax
- Update Admin routes with new `Class@method` syntax to save memory
- Avoid passing modals each time a view is rendered

**Bug fixes**

- Fix persistent tooltip when a button is disabled after click
- Fix missing `updates.*` permissions

## [0.9.0](https://github.com/getformwork/formwork/releases/tag/0.9.0) (2018-10-13)

**Enhancements**

- **Add user roles and permissions** ([#9](https://github.com/getformwork/formwork/pull/9))
- **Limit access after a certain amount of failed login attempts** ([#10](https://github.com/getformwork/formwork/pull/10))
- Use HTTP status 400 instead of 403 when CSRF token is not valid
- Show a `no-drop` cursor and light red background when page reordering is not possible
- Improve users list appearance especially for small screen sizes
- Slightly reduce sidebar width to have more room for content
- Improve pages list columns sizing
- Use user language when provided instead of `admin.lang` option
- Display file size next to uploaded file names

**Bug fixes**

- **Fix notification spacing issues when page is scrolled**
- Fix missing error notification when editing users is forbidden
- Check uploaded avatar existence before user update
- Fix language strings
- Fix wrong exception type in `Uploader` class

## [0.8.1](https://github.com/getformwork/formwork/releases/tag/0.8.1) (2018-10-08)

**Enhancements**

- Improve exception messages
- Remove unneeded loader.php

**Bug fixes**

- **Fix wrong platform requirements in composer.json**

## [0.8.0](https://github.com/getformwork/formwork/releases/tag/0.8.0) (2018-10-06)

**Enhancements**

- **Add backup feature to export site to a .zip archive**
- **Add image processing feature, by now used to resize avatars to square**
- **Improve session cookie security and consistency across supported PHP versions**
- Use monospace font for values in Options > Info
- Allow language strings in schemes

**Bug fixes**

- Fix redirect loop on Formwork Admin registration
- Fix an issue which made possible changing another user's password
- Fix error handler ignoring `@` operator and `error_reporting` directive
- Fix unrecognized percent-encoded URIs

## [0.7.2](https://github.com/getformwork/formwork/releases/tag/0.7.2) (2018-09-15)

**Enhancements**

- Change Updater check frequency from 1 hour to 15 minutes

**Bug fixes**

- Fix `Session::start()` not applying session options in PHP < 7.0
- Fix Options > Updates parse error triggered in PHP < 7.0
- Fix incorrect icons in Pages Editor files list

## [0.7.1](https://github.com/getformwork/formwork/releases/tag/0.7.1) (2018-09-13)

**Enhancements**

- Add Check Updates button to Dashboard quick actions
- Change Logout notification type from `success` to `info`
- Add new notification types `info` and `warning`
- Update icon font
- Redirect to the Panel URI requested before login

**Bug fixes**

- Fix Page Editor textarea always being focused after saving/reloading

## [0.7.0](https://github.com/getformwork/formwork/releases/tag/0.7.0) (2018-09-12)

**Enhancements**

- **Add Updater feature to automatically download new releases from GitHub repository** ([#4](https://github.com/getformwork/formwork/pull/4))
- **Add Attributes component to Pages Editor to change Page Template or Page Parent** ([#6](https://github.com/getformwork/formwork/pull/6))
- **Add error and exception handlers displaying a Formwork-styled error page**
- Add Typography page to show template styles
- Improve default template menus
- Retain cursor position in Pages Editor after saving/reloading
- Add new entries to Options > Info tab
- Add error notification when POST request size is greater than allowed
- Display Session Strict Mode information in Options > Info tab
- Add `admin.logout_redirect` option to decide where to redirect after logout (Login or Site Home Page)
- Display loaded php.ini filename in Options > Info tab
- Add `range` field type
- Add keyboard shortcut <kbd>CTRL/Cmd + S</kbd> to all views with Save command
- Add nginx.conf file with NGINX rewrite rules

**Bug fixes**

- Fix Tabs component wrapping
- Fix session temporary file persistence after logout
- Fix long notification text overflow
- Fix vertical scrollbar always visible in IE/Edge

## [0.6.12](https://github.com/getformwork/formwork/releases/tag/0.6.12) (2018-08-27)

**Enhancements**

- Add logout notification
- Add box shadow to pages list items when sorting pages
- Make buttons in Pages list look the same as in Page editor

**Bug fixes**

- Fix method names which broke PHP ^5.5.0 compatibility

## [0.6.11](https://github.com/getformwork/formwork/releases/tag/0.6.11) (2018-07-24)

**Enhancements**

- Make notifications disappear by clicking on them (closes [#3](https://github.com/getformwork/formwork/issues/3))
- Add Delete Page button to Pages editor
- Add Pages editor keyboard shortcuts for bold, italic and save commands
- Add Preview button to Pages editor

**Bug fixes**

- Fix unintended non-static methods which triggered a PHP warning

## [0.6.10](https://github.com/getformwork/formwork/releases/tag/0.6.10) (2018-07-18)

**Enhancements**

- Add support for PHP Yaml extension
- Add `parsers.use_php_yaml` option to decide PHP Yaml extension behavior
- Add current location to Admin Panel title
- Use new `togglegroup` fields instead of checkboxes in Admin > Options

**Bug fixes**

- Fix mixed content blocked over HTTPS connections
- Fix incorrect position of chart tooltips in Firefox
- Fix first level page creation when parent is an instance of `Site` class
- Fix options defaults not available before `system.yml` loading

## [0.6.9](https://github.com/getformwork/formwork/releases/tag/0.6.9) (2018-07-10)

- Initial release
