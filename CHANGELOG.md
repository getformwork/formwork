# Changelog

## [1.12.0](https://github.com/getformwork/formwork/releases/tag/1.12.0) (2022-02-11)

**Bug fixes**

- Ensure compatibility with PHP 8.1

## [1.11.1](https://github.com/getformwork/formwork/releases/tag/1.11.1) (2021-07-06)

**Bug fixes**

- Fix `PHP::encodeData()` failing to check if `__set_state()` method exists when `__call()` is implemented


## [1.11.0](https://github.com/getformwork/formwork/releases/tag/1.11.0) (2020-12-16)

**Enhancements**

- **Support PHP >= 7.3.5**
- **Display Markdown only on active CodeMirror lines**
- **Merge pull request #56 from getformwork/feature/dark-color-scheme**
- **Merge pull request #58 from getformwork/feature/json-parser**
- **Merge pull request #59 from getformwork/feature/updater-semver-parsing**
- **Merge pull request #60 from getformwork/feature/path-class**
- **Merge pull request #61 from getformwork/feature/stricter-date-parsing**
- **Merge pull request #62 from getformwork/feature/improved-date-formats**
- **Merge pull request #63 from getformwork/feature/improved-cache-serialization**
- **Merge pull request #65 from getformwork/feature/duration-input**
- Move from deprecated node-sass to Dart Sass
- Replace most of `LogicException` with more appropriate exceptions
- Add `Arr::random()` and `Arr::shuffle()`
- Add `Collection::random()`
- Add `PageCollection::shuffle()`
- Add OPcache status to Options > Info
- Ad Max Path Length to Options > Info
- Add Formwork icon in SVG format
- Use a default avatar image in SVG format
- Use system monospace font stack
- Update fonts
- Move fonts.scss and icons.scss to components folder
- Update `MimeType::MIME_TYPES`
- Recognize archive file type and display appropriate icon
- Add new options to tooltip component
- Update .eslintrc
- Build minified CSS only
- Add dark color scheme CSS
- Add color scheme support to panel
- Add color scheme options to system and user schemes
- Add support for system preferred color scheme
- Avoid wrapping strings more than once with `Str::wrap()`
- Add `Str::contains()`, `Str::before()`, `Str::beforeLast()`, `Str::after()` and `Str::afterLast()`
- Use new utilities from `Str` class
- Rewrite `Uri::make()` and `Uri::normalize()` moving slashes normalization in the first method
- Rewrite `FileSystem::name()` and `FileSystem::extension()` using `pathinfo()`
- Simplify `YAML` class
- Extract `FileSystem::getLastStreamErrorMessage()` from `FileSystem::fetch()`
- Add stream error details to `FileSystem::write()`
- Rename `FileSystem::assert()` to `FileSystem::assertExists()`
- Add `$assertExists` argument to `FileSystem::isReadable()`, `FileSystem::isWritable()`, `FileSystem::isFile()`, `FileSystem::isDirectory()` and `FileSystem::isEmptyDirectory()`
- Use `$assertExists` argument to remove `FileSystem::exists()` checks
- Use `substr_compare()` to make `Str::startsWith()` and `Str::endsWith()` more efficient
- Add `Str::append()` and `Str::prepend()`
- Return `User::lastAccess()` as integer
- Normalize path in `Site::__construct()`
- Avoid treating page relative path as URI
- Remove unneeded slashes normalization since `Uri::normalize()` does that
- Throw an exception when an URI is invalid
- Handle default ports scheme-dependently in `Uri` class
- Handle scheme and host always as lowercase in `Uri` class
- Avoid concatenating twice in `Uri::make()`
- Add `AbstractEncoder` class
- Make `YAML` class extend `AbstractEncoder`
- Use `YAML::encodeToFile()`
- Add `JSON` class
- Replace `json_encode()` and `json_decode()` with methods from `JSON`
- Make `Formwork` and `Admin` classes final
- Add `SemVer` class
- Add supplementary version checks to `Updater::checkUpdates()`
- Add `Path` class
- Use methods from `Path` class in `FileSystem` and `Uri`
- Remove superfluous argument
- Simplify conditional returns
- Simplify and combine conditionals when possible
- Simplify `Arr::appendMissing()`
- Simplify `Cookie::send()`
- Remove superfluous checks before foreach
- Use `random_bytes()` in `FileSystem::randomName()`
- Add `$prefix` argument to `FileSystem::randomName()`
- Make `FileSystem::createFile()` check file existence atomically and avoid call to `FileSystem::write()`
- Make `FileSystem::createDirectory()` error handling similar to `FileSystem::createFile()`
- Add `FileSystem::createTemporaryFile()`
- Use `FileSystem::createTemporaryFile()` in `FileSystem::write()`
- Explicitly handle the mode of created files and directories
- Add File Creation Mask to Options > Info
- Use SHA-256 hashes instead of SHA-1
- Avoid retaining in memory unmodified copies of images
- Update default avatar
- Move `<script>` tags before `</body>`
- Load scripts in `login` and `register` views
- Move JSON encoding to controllers
- Add `Date` class
- Improve validation in `Validator::validateDate()`
- Replace `strtotime()` with `Date::toTimestamp()`
- Add `Str::escapeAttr()`
- Always escape attributes with `HTML::attribute()`
- Add `escapeAttr` helper
- Use `escapeAttr` helper in admin views
- Add missing type declarations
- Move DatePicker `dayLabels`, `monthLabels` and `todayLabel` to `labels` property
- Rewrite DatePicker formatting function
- Add `date.weekdays.long` to translations
- Add `formatToPattern()`, `patternToFormat()`, `formatDateTime()` and `formatTimestamp()` to `DateFormats` class
- Update `AbstractController::defaults()`
- Simplify `Statistics::getChartData()`
- Move view helpers to `View::helpers()`
- Add `date()` and `datetime()` helpers to views
- Add `Arr::isAssociative()`
- Match backslash in `Page::__construct()`
- Merge branch 'master' into feature/path-class
- Remove superfluous argument
- Add `PHP` parser class
- Use PHP parser instead of `serialize()` in `FilesCache`
- Add `Response::__set_state()`
- Encode empty arrays inline in `PHP::encodeData()`
- Add duration field
- Use duration fields in system options
- Make sure duration input value is always a safe integer
- Add duration strings to French translation
- Add `intervalNames` argument to `secondsToInterval()`
- Rename `display` option to `intervals` in DurationInput
- Remove leading zeros in DurationInput
- Use `blur` event intead of `change`
- Allow leading zeros for decimal numbers in DurationInput
- Add focus styles to editor
- Use `Str::append()` in `Uri::normalize()`
- Rename `Uri::resolveRelativeUri()` to `Uri::resolveRelative()`
- Throw an exception in `MimeType::fromFile()` when the extension `fileinfo` is not enabled
- Update exception message in `Image::initialize()`
- Add parentheses after `__METHOD__` for consistency
- Export helpers to separate file
- Export admin view helpers to separate file
- Prevent direct access to .php files
- Rename `Page::id()` to `Page::name()`
- Add `AbstractPage::uid()`
- Add `File::hash()`
- Create browsing context for preview links to avoid always opening a new tab
- Add details to `@deprecated` tags
- Add `Text` class
- Add `countWords`, `truncate`, `truncateWords`, `readingTime`, `markdown`, `date` and `datetime` helpers

**Bug fixes**

- Fix spinner component due to Dart Sass breaking changes
- Fix argument validation in `Image` class
- Fix return type in `File::type()`
- Fix for-in loops
- Fix `.page-slug-change` icon
- Fix `Str::endsWith()` returning `false` with empty `$needle`
- Fix `FileSystem::write()` potentially writing files without write permissions
- Fix timestamp format to avoid locale-dependent issues in `Log::log()`
- Fix method visibility
- Fix `FileSystem::isVisible()` failing for empty paths
- Fix `FileSystem::moveDirectory()` ignoring `$overwrite` argument on recursion
- Fix js error in pages without sidebar
- Fix tooltips.js altering title attributes outside document body
- Fix `SemVer::fromString()` for PHP < 7.4
- Fix normalization after trimming separators in `Uri::make()`
- Fix `Validator::parse()` precision issues in casting to numeric types
- Fix `Utils.toSafeInteger()` not converting to integer values
- Fix return type
- Fix terminology of deprecation messages
- Fix `PageCollection::sort()` using `id` as default property, returning as adjacent pages in completely different places within hierarchy
- Fix `@deprecated` tag details

**Deprecations**

- Deprecate string and invalid `$direction` argument in `PageCollection::sort()`
- Deprecate `FileSystem::temporaryName()`
- Deprecate the possibility to return an array or a string with `MimeType::toExtension()`
- Deprecate `Uri::relativePath()`

## [1.10.3](https://github.com/getformwork/formwork/releases/tag/1.10.3) (2020-11-14)

**Enhancements**

- Move custom styles to avoid modifying vendor stylesheets
- Unquote `Header::HTTP_STATUS` keys
- Rewrite `$methods`, `$types` and `$shortcuts` as constants in `Router`
- Make styles in `.user-summary` component more consistent

**Bug fixes**

- Fix loose comparison in `JSONResponse::send()`
- Fix types in `JSONResponse` class
- Fix CodeMirror cursor, scrollbar and selection colors

## [1.10.2](https://github.com/getformwork/formwork/releases/tag/1.10.2) (2020-11-11)

**Enhancements**

- Extract _functions.scss from admin.scss
- Add `tint()` and `shade()` to _functions.scss
- Move base colors logic to _colors.scss and remove more inconsistencies
- Add `urlencode-color()` to _functions.scss
- Extract constant `SESSION_NAME` in `Session` class
- Extract constant `SESSION_KEY` in `Notification` class
- Extract constants `SESSION_KEY` and `INPUT_NAME` in `CSRFToken` class
- Rename constant `IGNORED_FIELDS` to `IGNORED_PROPERTIES` in `Translator`
- Use `Str::wrap()` in `Pages` controller

**Bug fixes**

- **Fix `PageCollection::search()` not ignoring HTML tags**
- Fix colors and shadows inconsistencies
- Fix fill color in embedded SVGs
- Fix non-matching input placeholder color
- Fix highlighting color of HTML attributes
- Fix codemirror editor padding
- Fix z-index conflict with codemirror scrollbar
- Fix background color when `.toggle-navigation` is focused

## [1.10.1](https://github.com/getformwork/formwork/releases/tag/1.10.1) (2020-11-09)

**Bug fixes**

- Fix pages list toggle expanding all children lists
- Fix value disappearing from template select in New Page modal

## [1.10.0](https://github.com/getformwork/formwork/releases/tag/1.10.0) (2020-11-08)

**Enhancements**

- **Rewrite data fields as properties in `User` class**
- **Add `ValidationException`**
- **Rewrite `Validator` class and add several methods to `Field`**
- **Add missing attributes to fields**
- Add return types
- Add `DataGetter::hasMultiple()`
- Use `DataGetter::hasMultiple()` in admin controllers
- Enforce string keys in `Arr::get()` and `Arr::has()`
- Remove incompatible parent from `Avatar` class
- Remove superfluous PHPDoc
- Add missing type declarations
- Add `Str::wrap()`
- Normalize duplicated slashes with `FileSystem::Uri()`
- Add `FileSystem::normalizePath()` variant which does not force trailing slashes
- Add `FileSystem::joinPaths()`
- Use `FileSystem::normalizePath()` and `FileSystem::joinPaths()` whenpossible
- Use `form.requestSubmit()` instead of `form.submit()` to properly trigger events
- Add `HTMLFormElement.requestSubmit()` polyfill
- Display a spinner when files are being uploaded
- Prevent click and drop on file inputs when their forms are submitted
- Remove .eslintignore
- Update lint scripts in package.json
- Add `FileSystem::MAX_PATH_LENGTH` and `FileSystem::MAX_NAME_LENGTH`
- Check filename and path lengths in `Uploader::move()`
- Throw an exception when an uploaded file cannot be moved
- Use `random_int()` instead of `rand()` and `mt_rand()`
- Always load cache
- Add explicit `Admin::users()` and `Admin::translation()`
- Add `content` property to `Page` to restore access from `get()`
- Make `LanguageCodes::$codes` a constant
- Extract `SingletonTrait` from `Admin` and `Formwork` classes
- Recognize `Field` instances in `Fields::__construct()` and handle them properly
- Rename variables for consistency in `Fields::find()` and `Fields::toArray()`
- Use `Field::isDisabled()` and `Field::isRequired()` in field views
- Update `Options::updateOptions()`
- Add `DataSetter::remove()`
- Update `Users::profile()` and `Users::updateUser()`
- Update user scheme
- Avoid adding languages to `data` property of `Site` class

**Bug fixes**

- Fix data access in cover image partial template
- Fix `Page::reload()` not setting some properties to null
- Fix return types
- Fix password hash being always unset in `User::__construct()`
- Fix `Users` controller triggering deprecation warnings
- Fix spinner wrongly displayed on submit in empty files input

**Deprecations**

- Deprecate `DataGetter::has()` use with an array as key
- Deprecate non-string used as key in getters and setters
- Deprecate `FileSystem::normalize()`
- Deprecate `User::get()` and `User::has()`
- Deprecate `FileSystem::create()`

## [1.9.1](https://github.com/getformwork/formwork/releases/tag/1.9.1) (2020-10-31)

**Enhancements**

- Invalidate cache also when system options are changed

**Bug fixes**

- Fix undefined variable in `Pages::delete()`

## [1.9.0](https://github.com/getformwork/formwork/releases/tag/1.9.0) (2020-10-29)

**Enhancements**

- Revert non-cacheable error page from [b8018e9](https://github.com/getformwork/formwork/commit/b8018e9)
- Defer Markdown processing in pages
- Clear cache when current page has been published or unpublished
- Add `Str::slug()`
- Add `slug` template helper
- Use `Str::slug()` to support tags with special characters

**Bug fixes**

- Fix `PageCollection::filter()` coercing `$value` parameter to bool

## [1.8.0](https://github.com/getformwork/formwork/releases/tag/1.8.0) (2020-10-27)

**Enhancements**

- Add generator-based `FileSystem::listContents()` and `FileSystem::listRecursive()`
- Add `FileSystem::isEmptyDirectory()`
- Avoid using `FileSystem::scan()` and `FileSystem::scanRecursive()`
- Avoid using `hasOwnProperty()` directly on instances

**Bug fixes**

- Fix wrong `ecmaVersion` in .eslintrc

**Deprecations**

- Deprecate `FileSystem::scan()` and `FileSystem::scanRecursive()`

## [1.7.1](https://github.com/getformwork/formwork/releases/tag/1.7.1) (2020-06-25)

**Enhancements**

- Restore Markdown list continuation

**Bug fixes**

- Fix errors occurring when Change slug command is not available

## [1.7.0](https://github.com/getformwork/formwork/releases/tag/1.7.0) (2020-06-11)

**Enhancements**

- **Improve focus state on form inputs, buttons and links**
- Make shades of gray more consistent
- Improve accent and text color contrast
- Move `.title-bar` down so that sidebar comes first in tab order
- Replace floats with flexbox

**Bug fixes**

- Fix grid in page editor form
- Fix non-unique id in `TagInput`
- Fix errors occurring with image inputs
- Fix wrongly detected changes in forms

## [1.6.1](https://github.com/getformwork/formwork/releases/tag/1.6.1) (2020-05-27)

**Bug fixes**

- **Fix potential security issues with .htaccess**

## [1.6.0](https://github.com/getformwork/formwork/releases/tag/1.6.0) (2020-05-07)

**Enhancements**

- Add `aria-hidden` attributes
- Make field views more readable with new `attr` helper
- Avoid non user-scalable `<meta name="viewport">` values
- Rewrite panel app as JavaScript modules
- Remove Gulp and use npm scripts instead
- Make error handling consistent in `FileSystem` class

## [1.5.2](https://github.com/getformwork/formwork/releases/tag/1.5.2) (2020-03-02)

**Bug fixes**

- Fix `Formwork.ArrayInput()` not properly binding and handling events

## [1.5.1](https://github.com/getformwork/formwork/releases/tag/1.5.1) (2020-02-29)

**Bug fixes**

- Fix type declarations for PHP < 7.2

## [1.5.0](https://github.com/getformwork/formwork/releases/tag/1.5.0) (2020-02-27)

**Enhancements**

- **Rewrite admin panel app without jQuery**
- **Add diacritic-insensitive page search and highlight results in admin panel**
- Improve calendar positioning
- Improve notification positioning
- Check notification type in `Notification::send()`
- Add pre-filled data to GitHub issue link on internal server errors
- Add Formwork logo to sidebar

**Bug fixes**

- Fix error when deleting users without avatar

## [1.4.7](https://github.com/getformwork/formwork/releases/tag/1.4.7) (2020-02-22)

**Bug fixes**

- Fix error in Pages controller which prevented page creation

## [1.4.6](https://github.com/getformwork/formwork/releases/tag/1.4.6) (2020-02-15)

**Enhancements**

- Validate URIs in `FileSystem::fetch()`
- Improve error messages in `FileSystem::fetch()`
- Simplify `FileSystem::download()`
- Extract constant `API_RELEASE_URI` in `Updater` class
- Catch exceptions on checking updates
- Properly display errors on checking updates

## [1.4.5](https://github.com/getformwork/formwork/releases/tag/1.4.5) (2019-12-31)

**Enhancements**

- Revert temporary error suppression in `ParsedownExtra` from [c53fbbd](https://github.com/getformwork/formwork/commit/c53fbbd)

## [1.4.4](https://github.com/getformwork/formwork/releases/tag/1.4.4) (2019-12-21)

**Enhancements**

- Make all pagination URIs relative
- Update French translation, thanks to @MiFrance

**Bug fixes**

- Fix `Avatar::__construct()` (closes [#47](https://github.com/getformwork/formwork/issues/47))

## [1.4.3](https://github.com/getformwork/formwork/releases/tag/1.4.3) (2019-12-15)

**Enhancements**

- Add type declarations
- Remove superfluous PHPDoc

**Bug fixes**

- Fix pagination URIs generated sometimes with missing slashes

# Changelog

## [1.4.2](https://github.com/getformwork/formwork/releases/tag/1.4.2) (2019-12-13)

**Bug fixes**

- Fix paginated pages cached incorrectly

## [1.4.1](https://github.com/getformwork/formwork/releases/tag/1.4.1) (2019-12-11)

**Enhancements**

- Optimize `YAML::parse()`
- Load defaults before parsing YAML in `Formwork::loadOptions()`

**Bug fixes**

- Fix errors when using PHP Yaml extension

## [1.4.0](https://github.com/getformwork/formwork/releases/tag/1.4.0) (2019-12-9)

**Enhancements**

- Move `Formwork\Admin\Image` to `Formwork\Files\Image`
- Add the possibility to save JPEG images as progressive
- Add the option to process (optimize) uploaded images
- Add lazy initalization to `Image` class
- Throw an exception when an uploaded file already exists
- Add option to prefer dist assets to `Updater` class
- Prefer dist assets when updating
- Cleanup files after installing updates
- Move `FileSystem::mimeType()` logic to `MimeType::fromFile()`
- Add `$limit` parameter to `Statistics::getChartData()`
- Simplify `Page::lastModifiedTime()`
- Add `Image::saveOptimized()`
- Use `=== null` instead of `is_null()`
- Use short array syntax
- Use `**` operator instead of `pow()`
- Use late static binding in `PageCollection::search()`
- Add WebP images support
- Add WebP quality slider to Options > System
- Allow to upload WebP images as user avatar
- Avoid re-throwing exception in `Errors::exceptionHandler()`
- Revert type check in `Pages::changePageParent()` from [4ae2b29](https://github.com/getformwork/formwork/commit/4ae2b29)
- Minify app.js with uglify-js@3.7

**Bug fixes**

- Fix `Image::destroy()` error if the image is not modified
- Fix transparency not handled when loading images
- Fix `Image::destroy()` error if the image was already destroyed
- Fix `Image::save()` not destroying image after saving
- Fix array access on null in `AccessLimiter::__construct()`
- Fix `FileSystem::shorthandToBytes()` treating M and G the same
- Fix extraneous npm packages
- Temporarily suppress `ParsedownExtra` PHP 7.4 errors (array access on null)

**Deprecations**

- Deprecate `Page::file()`

## [1.3.1](https://github.com/getformwork/formwork/releases/tag/1.3.1) (2019-11-13)

**Enhancements**

- Add `Metadatum::__toString()`
- Add missing status codes to `Header::HTTP_STATUS`

**Bug fixes**

- Fix `metadata.set_generator` ignored if a custom value is given

## [1.3.0](https://github.com/getformwork/formwork/releases/tag/1.3.0) (2019-11-05)

**Enhancements**

- **Add Russian translation**, thanks to @aukc1970
- **Declare properties for all getter methods in** `AbstractPage` **and** `Page`
- **Cache getter values in** `AbstractPage` **and** `Page` **when possible**
- Always load routes
- Make error page cacheable
- Add Discord badge to README.md
- Add CONTRIBUTING.md
- Move admin panel constants to `Admin` class
- Fix line endings with `Pages::updatePage()`
- Add .editorconfig
- Extract `AbstractParser` class from `YAML`
- Accept `usePHPYAML` option in `YAML::parse()` and `YAML::encode()`
- Add Markdown parser
- Update .htaccess and nginx.conf

**Bug fixes**

- Fix line endings and quotes in content files

**Deprecations**

- Deprecate accessing non-getter methods from `AbstractPage::get()`

## [1.2.1](https://github.com/getformwork/formwork/releases/tag/1.2.1) (2019-10-22)

**Bug fixes**

- Fix file permissions

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
