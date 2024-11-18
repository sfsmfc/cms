# Release notes for Craft CMS 5.6 (WIP)

### Accessibility
- Improved the accessibility of Checkboxes and Radio Buttons fields that allow custom options. ([#16080](https://github.com/craftcms/cms/pull/16080))

### Administration
- Added the “Show the ‘URL Suffix’ field” setting to Link fields. ([#15813](https://github.com/craftcms/cms/discussions/15813))

### Development
- Added support for fallback element partial templates, e.g. `_partials/entry.twig` as opposed to `_partials/entry/typeHandle.twig`. ([#16125](https://github.com/craftcms/cms/pull/16125))

### Extensibility
- Added `craft\fields\data\LinkData::$urlSuffix`.
- Added `craft\fields\data\LinkData::getUrl()`.
