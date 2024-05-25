# Changelog

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

## [1.13.0](https://github.com/getformwork/formwork/releases/tag/1.13.0) - [0.6.9](https://github.com/getformwork/formwork/releases/tag/0.6.9)
➡️ Read previous [CHANGELOG.md](https://github.com/getformwork/formwork/blob/1.x/CHANGELOG.md) on the `1.x` branch.