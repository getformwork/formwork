# Changelog

# [2.3.1](https://github.com/getformwork/formwork/releases/tag/2.3.1)

**Enhancements**
- **Update image picker on modal open**
- Improve togglegroup styles for dark color scheme

**Bug fixes**
- Fix default HTTPS port in `Http\Request`
- Fix ProseMirror caret disappearing in dark color scheme
- Fix image picker styles
- Serve actual index.php script with PHP built-in server

**Security**
- Restrict media MIME types to determine the result of `File::type()` based on supported formats

# [2.3.0](https://github.com/getformwork/formwork/releases/tag/2.3.0)

**Enhancements**
- **Add Events functionality**
- **Add Plugins functionality**
- **Add support for AVIF images**
- **Expose error messages in the panel for invalid fields after submission**
- **Add Hungarian and Swedish translations** (🤖 AI generated, reviews are welcome)
- **Improve dark color scheme styles in the panel**
- **Add new `Authenticator` service to decouple authentication from panel**
- **Implement PSR-3 compatible `Logger`**
- **Implement PSR-11 `ContainerInterface`**
- **Implement PSR-14 `EventDispatcherInterface`**
- Store validated data with `Model::set()` if there is a corresponding field
- Correctly set `ReadonlyModelProperty` attribute to avoid unexpected changes to model properties
- Move fields initialization and validation to `Page::load()`
- Add the possibility to create pages from the constructor by retrieving `App` instance if not provided
- Sync page data with fields in `Page::load()`
- Add `Config::hasMultiple()`
- Add `Config::getMultiple()`
- Add `Config::set()` and `Config::setMultiple()`
- Add `Schemes::getMultiple()` and `Schemes::getAll()`
- Add `Translations::getMultiple()` and `Translations::getAll()`
- Allow Scheme extension with `Scheme::extend()` and `Scheme::extendWith()`
- Set validated status even if validation fails
- Add context to `InvalidValueException`
- Add the possibility to get translated validation error
- Add styles for invalid fields
- Add the possibility to pass route actions parameters
- Add view namespaces
- Make Assets a service resolving namespaced resources
- Add the possibility to set `NavigationItem` visibility in the panel
- Add the possibility to specify config prefix with `Config::loadFile()` and `Config::loadFromPath()`
- Add `Arr::extend()`, `Arr::override()` and `Arr::exclude()` to handle data with more consistency
- Add default values support to field config files
- Add `Form` class to streamline incoming data process and validation
- Add Plugins views to panel
- Add specific exception classes for `Image`
- Add `User::save()`, `User::delete()` and `User::deleteImage()`
- Add custom session handler
- Add the possibility to specify the number of retry attempts (by default 10) of the `serve` command to bind to an available port
- Improve panel navigation spacing
- Improve panel header styles
- Use consistent border-radius and box-shadow values in panel styles
- Improve array input spacing and sortable styles in panel
- Use click event instead of mousedown to avoid unexpected actions in the panel
- Allow `Container::buildArguments()` to use default values for unspecified classes, interfaces and support variadic parameters
- Add the possibility to set auto color scheme to the panel login
- Avoid processing HTTP ranges for responses requiring empty content

**Bug fixes**
- Allow and return an appropriate value for empty non-required fields
- Correctly merge dotted keys with `Config::loadFile()`
- Ignore empty values passed to `Html::classes()`
- Iterate all fields to ensure all validations are run in `FieldCollection::isValid()`
- Validate unique slug with unspecified root
- Fix HTTP Range handling for file responses (RFC 7233–compliance and Safari compatibility)
- Define default section for layoutless schemes
- Clear image transforms after processing images in `Image::saveAs()`
- Fix `serve` command failing with log messages containing brackets
- Fix config service loading with missing cache/config folder
- Fix translations service loading with missing site/translations folder
- Normalize and ensure `Panel::route()` is inside the panel root
- Fix no tab selection from invalid local storage value in the panel interface
- Fix backtrace frame dumping for unreflectable functions
- Handle potential exceptions when initializing contentFile in Page class
- Correctly add tooltips to panel editor toolbar commands
- Check if site/files is a directory before getting files
- Fix metadata prefix set even if a colon is not present in the name
- Fix `DomSanitizer::sanitizeUri()` throwing exception for valid empty values
- Remove leading and trailing whitespace with `Text::normalizeWhitespace()`
- Return empty array if input is empty with `Text::splitWords()`
- Fix discarded stdout/stderr data while serving pages with the `serve` command
- Always close connections and restore error handlers in the `Client` class
- Always close streams in `FileResponse::send()`
- Avoid errors for undefined data keys in `Str::interpolate()`
- Set correct 403 Forbidden status when CSRF token is invalid on XHR requests
- Ensure `MimeType::fromFile()` is given a readable file
- Check if file was actually uploaded in `FileUploader::upload()`

**Security**
- Normalize assets paths to avoid directory traversal
- Avoid potentially broken "deflate" responses and throw on unsupported content encodings

**Deprecations**
- Poorly-named `$dateField->toDuration()` deprecated in favor of `$dateField->toTimeDistance()`
- `Log` class deprecated in favor of the new PSR-3 compatible `Logger`
- Page setter methods deprecated in favor of using `$page->set()`
- `Page::isSlugEditable()` and `Page::isSlugReadonly()`
- `Panel::assets()` in favor of the `Assets` service
- `system.panel.loginAttempts` and `system.panel.loginResetTime` options in favor of `system.authentication.limits.maxAttempts` and `system.authentication.limits.resetTime`
- `system.panel.sessionDuration` option in favor of `system.session.duration`

# [2.2.2](https://github.com/getformwork/formwork/releases/tag/2.2.2)

**Enhancements**
- Generate a unique slug by checking for existing copies in `Page::duplicate()`
- Add browser previews for audio and pdf files
- Hide panel file thumbnails on image load error

**Bug fixes**
- Fix file item template for uploaded files
- Fix file(s) and image(s) field options not updated on file deletion
- Fix unsupported video thumbnails added to file(s) input on upload
- Avoid changing uploaded file extension if correctly associated to the MIME type
- Fix missing file-audio icon preventing file items rendering in panel views
- Handle upload errors in `FilesController::upload()`
- Correctly update drop target label when uploading multiple files

**Security**
- Restrict media MIME types to determine the result of `File::type()` based on supported formats

# [2.2.1](https://github.com/getformwork/formwork/releases/tag/2.2.1)

**Enhancements**
- **Add `PageCollection::routable()` to filter routable pages**
- **Add `Page::delete()`**
- Preview pages as published regardless of their actual status
- Allow preview of non-routable pages
- Store tabs state in localStorage to persist between panel views loads

**Bug fixes**
- Fix query and fragment being removed by `Uri::resolveRelative()`
- Fix duplicate page button in the panel remaining disabled even if all children pages were removed

# [2.2.0](https://github.com/getformwork/formwork/releases/tag/2.2.0)

**Enhancements**
- **Add support for taxonomies**
- **Add the possibility to define item field options in the array fields**
- **Add the possibility to define field layout tabs**
- **Add Greek and Turkish translations (🤖 AI generated, reviews are welcome)**
- Add the possibility to specify field layout section order in page schemes
- Add the possibility to set markdown editor placeholder and disabled state
- Add the possibility to pass asset metadata
- Normalize data structure by removing dots when merging defaults and frontmatter
- Improve performance traversing the page ancestors instead of the entire page subtree
- Improve sorting based on a second array with `Arr::sort()`
- Add the possibility to delete pages in place from the panel tree
- Add the possibility to save and create a new page in the panel
- Add the possibility to duplicate pages from the panel
- Add the possibility to select route alias destinations with page field in the panel
- Add the possibility to define route params constraints with `Route::where()`
- Add `PageCollection::havingTaxonomy()` to filter pages by taxonomy
- Add `AbstractCollection::keyBy()`
- Add `AbstractCollection::each()`
- Add icons to panel navigation
- Build panel app as modules and use async imports for chunk splitting
- Allow loading scripts as module in the panel with asset meta `module`
- Remove markdown editor layout shift and add loading animation
- Add loading animations to statistics charts
- Display canonical route in the page editor as in other views

**Security**
- Use `innerHTML` only if needed and on escaped input

**Bug fixes**
- Fix page handling of fields with dot notation in the frontmatter
- Fix page setters and default values handling
- Fix URL-encoded strings in request input keys
- Remove propagation of site description metadata to all pages
- Fix color input initalization without default value
- Avoid PHP 8.5 deprecations

**Deprecations**
- Deprecate `allowTags` option in page schemes

# [2.1.5](https://github.com/getformwork/formwork/releases/tag/2.1.5)

**Bug fixes**
- Update panel dependencies to fix vulnerabilities
- Add missing files icon

# [2.1.4](https://github.com/getformwork/formwork/releases/tag/2.1.4)

**Enhancements**
- Display version info of all required packages in the panel info view
- Add the possibility to remove field icons with `icon: null`
- Display page info by hovering page icon in the editor
- Add default icons to email and password fields
- Add the possibility to initialize modals as open
- Add the possibility to set modal forms target

**Bug fixes**
- Move symfony/process to non-dev requires to ensure it's available for the `bin/serve` command
- Fix slug input not being initialized with source input default value

# [2.1.3](https://github.com/getformwork/formwork/releases/tag/2.1.3)

**Enhancements**
- Add descriptions to advanced site fields
- Add `--hostname` option to the `bin/formwork backup` command
- Reduce backup size by ignoring vendor files by default

**Bug fixes**
- Fix route aliases not available in panel site options

# [2.1.2](https://github.com/getformwork/formwork/releases/tag/2.1.2)

**Bug fixes**
- Fix file permissions ignored on updates extraction

# [2.1.1](https://github.com/getformwork/formwork/releases/tag/2.1.1)

**Bug fixes**
- Fix missing absolute path preventing backup creation in some environments
- Move league/climate to non-dev requires to ensure it's available for the `bin/formwork` command

# [2.1.0](https://github.com/getformwork/formwork/releases/tag/2.1.0)

**Enhancements**
- **Add the possibility to navigate the pages tree**
- **Add the `bin/formwork` command to manage cache, backups and updates from cli**
- **Add support for custom page cache time with `cache.time` option**
- **Add `Image::width()` and `Image::height()` methods to get computed image dimensions**
- **Add Dutch and Romanian translations (🤖 AI generated, reviews are welcome)**
- Check if default config has changed before loading from cache
- Avoid caching config on cli
- Extract app loading from `App::run() ` to allow bootstrapping without running the app
- Set default `updates.force` option to false
- Improve cacheability check and compute ETag on actual content
- Improve panel styles and page tree UX
- Improve panel dropdown items accessibility
- Improve styling of disabled state of panel buttons and inputs

**Bug fixes**
- Fix error code dump for dark color scheme

# [2.0.1](https://github.com/getformwork/formwork/releases/tag/2.0.1)

**Bug fixes**
- Fix `Image::resize()` default parameter values to match the corresponding transform
- Fix updater etag check
- Fix updater skipping first archive file
- Fix minor updater UI details

# [2.0.0](https://github.com/getformwork/formwork/releases/tag/2.0.0)

**Breaking Changes**

See [2.0.0-beta.1](#200-beta1) to [2.0.0-rc.1](#200-rc1) changelogs for breaking changes in each pre-release

**Enhancements**

- **Add support for basic filters to `AbstractCollection::filterBy()`**
- **Allow `Image::resize()` to accept only width or height to maintain aspect ratio**
- Add image preview functionality to panel page tree (@srgirard84)
- Add inequality constraints to `Constraint` class
- Add role to new user modal
- Define a generic model variable for dynamic field values if applicable
- Clear cache even if not enabled
- Use pnpm to install panel dependencies
- Set model values in the corresponding field if exists

**Bug fixes**
- Use requested route as cache key to inlude parameters
- Correctly handle bootstrap from cli

## [2.0.0-rc.1](https://github.com/getformwork/formwork/releases/tag/2.0.0-rc.1)

**Breaking Changes**

- **Add `panel.` prefix to permissions names**
- **Remove permissions from user role (to allow future use for frontend users)**
- Remove the possibility for administators to change the password of other users
- Rename `AbstractCollection::pluck()` to `extract()`
- Rename `system.pages.content.safeMode` to `system.page.content.allowHtml`

**Enhancements**
- **Refactor login and allow using e-mail to authenticate**
- **Update starter site content**
- Improve editor response to state change and remember selected mode
- Decouple config from defaults
- Allow php format config
- Cache resolved config
- Fallback to default error handler on failure
- Allow extending fields to share methods
- Allow default config for base fields
- Generate user image from full name initials if missing
- Add `Arr::dot()` and `Arr::undot()`
- Improve readability of encoded YAML
- Add `FileSystem::LIST_EXCLUDE_EMPTY_DIRECTORIES()` flag
- Exclude empty directories from page retrieval
- Ensure email addresses are not used by different accounts
- Add non-capturing group to avoid issues with alternation in route patterns
- Add `$entireMatch` param to `Constraint::matcheRegex()`

**Bug fixes**

- Fix redirect after file deletion
- Fix unsaved registry without file existence
- Fix custom config options removed by `OptionsController::updateOptions()`
- Fix scheme assignment to pages created from the panel
- Fix page num assignment
- Fix `Arr::remove()` creating undefined keys while traversing

## [2.0.0-beta.6](https://github.com/getformwork/formwork/releases/tag/2.0.0-beta.6)

**Bug fixes**

- Fix page creation
- Fix unresolved site service altering panel language

## [2.0.0-beta.5](https://github.com/getformwork/formwork/releases/tag/2.0.0-beta.5)

**Breaking Changes**

- **Move languages from system to site options**
- Use fine-grained `allowPagination` and `allowTags` scheme options instead of `type: listing`

**Enhancements**

- **Add Files view to the panel**
- Add fields `width` attribute (@RWDevelopment)
- Add color field type
- Improve upload field file lists
- Update editor links insertion, removal and tooltip
- Allow uploads from options views
- Add options-related methods to upload field (`isMultiple()`, `destination()`, `overwrite()`, `filename()`)
- Prefer using `rawurlencode()` and `rawurldecode()`
- Add `Uri::encode()`
- Add `uri()` method to views
- Add access to site files from `/files` route
- Add `Languages::hasMultiple()`
- Avoid multilang behavior without multiple languages
- Add `Panel::path()`
- Move app config from `AbstractController` to separate file
- Move panel navigation from `AbstractController` to separate file

**Bug fixes**

- Fix broken uri generation with numeric prefixes
- Fix possible altered UTF-8 characters when parsing URI data
- Fix tooltips showing after modal opening

**Security**
- Restrict uploaded files destinations

## [2.0.0-beta.4](https://github.com/getformwork/formwork/releases/tag/2.0.0-beta.4)

**Breaking Changes**

- **Changed Assets handling**

**Enhancements**

- **Use XHR to perform file actions (upload, delete, rename, replace) without updating the page**
- **Improved file(s) and image(s) fields**
- **Improved Modals handling**
- Add `Visitor::getDeviceType()`
- Track sources and devices in statistics
- Limit consecutive tracked visits to one every 15 seconds
- Add support for site translations
- Add icon support to duration, select, date, image, page, template, email, number, password, slug and text fields
- Make tags fields reorderable
- Add limit option to tags fields
- Add throwable message to JSON error responses
- Add the possibility to have date-only fields with `time: false`
- Default to YYY-MM-DD format when converting date input value to string to be comparable when sorting and filtering
- Add method `toDateTimeString()` to date fields to have a consistent behavior with JavaScript `Date`
- Trigger editor changes immediately and debounce after
- Avoid forced trailing slash with Uri::normalize()
- Avoid meta tags if possible
- Rename `FileInput` to `UploadInput` for consistency
- Rename helpers to methods to avoid confusion in naming

**Bug fixes**
- Fix panel errors not being sent to the error log
- Fix new page template filtering

**Security**
- Properly validate select fields
- Escape site title

## [2.0.0-beta.3](https://github.com/getformwork/formwork/releases/tag/2.0.0-beta.3)

**Enhancements**

- **Require PHP >= 8.3**
- **Add Polish and Ukrainian translation** (🤖 AI generated, reviews are welcome)
- Add default Cache-Control header
- Prevent session_start() from setting cache headers
- Handle conditional requests
- Add cache headers to assets
- By default make page requests conditional if cache is enabled
- Add `autoEtag` and `autoLastModified` params to `FileResponse` constructor
- Save response time by making errors controller lazy
- Lazily-load dynamic field vars
- Avoid tracking visit to maintenance, unpublished and not routable pages
- Update .htaccess and server script to allow access to .well-known
- Improve route patterns and order
- Replace `mimeTypes.extensionTypes` with closure to increase response speed
- Move some strings out from panel translations
- Refine serve command output
- Decouple classes and traits from `App::instance()`
- Avoid defining global `$formwork` variable
- Remove unused `DataGetter` and `DataSetter` classes
- Finalize several classes and privatize methods and properties
- Limit search and filtering to word boundaries
- Avoid reporting gd warnings
- Use content folder last modified time to determine cached response
- Touch content folder when clearing pages cache
- Update page last modified time after changes to files

**Bug fixes**

- Fix dropdowns scrolling by keyboard
- Avoid setting unnecessary alpha flag to VP8X chunks
- Copy original image resampled to avoid GIF images trasparency issues
- Avoid artifacts on images with alpha channel
- Avoid transforms propagation to avoid unnecessary image creation
- Fix relative URI used instead of absolute in `Request::validateReferer()`
- Convert palette images to truecolor before outputting WebP

## [2.0.0-beta.2](https://github.com/getformwork/formwork/releases/tag/2.0.0-beta.2)

**Breaking Changes**

- **Users, roles and statistics folders moved to sites/**

**Enhancements**

- **Add content history to panel**
- **Add live preview to panel**
- **Implement new Markdown editor**
- **Translate scheme and templates titles**
- **Allow theme switching based on `prefers-color-scheme` change**
- **Implement file metadata**
- **Add page info cards by hovering on page icons**
- **Add descriptions to publish and visibility-related fields**
- **Send `FileResponse` splitted chunkwise and according to the Range request header to improve performance with large files**
- **Allow `HEAD` requests**
- **Add slug field type**
- Add `Role` class
- Move Info to Tools section
- Add `csrfToken` service alias
- Allow and filter POST requests to site pages
- Avoid using special fields for page parent and template
- Improve file upload field
- Add `AbstractCollection::flatten()`, `AbstractCollection::union()`, `AbstractCollection::intersection()`, `AbstractCollection::difference()` and `AbstractCollection::find()`
- Allow index-only call to `AbstractCollection::slice()`
- Add utility methods to `PageCollection`
- Add `site.path` to config
- Fix `Debug::dump()` dumping before sendig headers
- Check panel assets presence on boot
- Add the possibility to delete user image
- Use attribute `ReadonlyModelProperty` to control Model::set() write access
- Add `Page::videos()` and `Page::media()`
- Allow defining icon in page schemes options
- Change default session durations to 2 
- Load only video metadata in thumbnails
- Add preview size to dimensionless images
- Add `AbstractController::forward()` to forward requests to other controllers
- Move authentication logic to `User`
- Add `Page::save()` method
- Add `Field::isReadonly()`
- Add `InvalidValueException` to handle exceptions in model setters

**Security**

- **Add `Sanitizer` class to sanitize Markdown and SVG output**

## [2.0.0-beta.1](https://github.com/getformwork/formwork/releases/tag/2.0.0-beta.1)
As the upcoming version 2.0.0 is a major release and the code has been extensively rewritten (~ 900 commits), here are listed only the most notable changes (the list may not be exhaustive and could change):

**Breaking Changes**

- **PHP version requirement raised to >= 8.2**
- **Application architecture rewritten for version 2.0, `Formwork` class has been replaced with `App` class, which is the app container**
- **Config, content and templates folder moved to sites/**
- **admin folder, route and even `Admin/*` classes renamed to panel or `Panel/*`**
- Classes from admin/ moved to formwork/src/Panel
- Rewritten logic between schemes, fields and pages
- Rewritten `Page`, `Site` and related classes
- camelCase is now enforced in all keys and PascalCase in class name now is consistent
- HTTP related classes moved to `Formwork\Http` namespace and now are services handled by the container
- Rewritten `Router` class

**Enhancements**

- **Improved Administration Panel with a better page editing experience**
- **Added file info views and thumbnails options to display files in the panel**
- **New Statistics and Backup views**
- **Improved Panel UI on mobile devices**
- **Added debug option to get stack traces during developement**
- **Added `serve` command to test Formwork even without a webserver**
- **Added informative errors during bootstrap**
- Fields now have their own methods defined in formwork/fields
- Fields now support dynamic variables by suffixing properties with `@`
- Added `AbstractCollection` and `Collection` classes to better handle data
- Added `Constraint` class to check data
- Added `Interpolator` class
- Added improved image-related class in the namespace `Formwork\Image` with a better image transformation API and support for reading color profiles and EXIF metadata
- Transformed images are now cached
- Added `Debug` and `CodeDumper` classes

**Security**

- **Added `content.safeMode` system option** (enabled by default) to escape HTML in Markdown content
- **Fields in the Panel are now accurately escaped**
- Escaped page titles and tags in default templates

## [1.13.2](https://github.com/getformwork/formwork/releases/tag/1.13.2) - [0.6.9](https://github.com/getformwork/formwork/releases/tag/0.6.9)
➡️ Read previous [CHANGELOG.md](https://github.com/getformwork/formwork/blob/1.x/CHANGELOG.md) on the `1.x` branch.