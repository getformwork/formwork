# Changelog

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
