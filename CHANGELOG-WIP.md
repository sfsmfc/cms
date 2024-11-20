# Release notes for Craft CMS 5.6 (WIP)

### Content Management
- “Related To”, “Not Related To”, “Author”, and relational field condition rules now allow multiple elements to be specified. ([#16121](https://github.com/craftcms/cms/discussions/16121))
- Improved the styling of inline code fragments. ([#16141](https://github.com/craftcms/cms/pull/16141))

### Accessibility
- Improved the accessibility of Checkboxes and Radio Buttons fields that allow custom options. ([#16080](https://github.com/craftcms/cms/pull/16080))
- Improved the accessibility of control panel icons. ([#16128](https://github.com/craftcms/cms/pull/16128))
- Improved the accessibility of Selectize inputs. ([#16110](https://github.com/craftcms/cms/pull/16110))

### Administration
- Added the “Show the ‘URL Suffix’ field” setting to Link fields. ([#15813](https://github.com/craftcms/cms/discussions/15813))

### Development
- Added support for fallback element partial templates, e.g. `_partials/entry.twig` as opposed to `_partials/entry/typeHandle.twig`. ([#16125](https://github.com/craftcms/cms/pull/16125))

### Extensibility
- Added `craft\base\conditions\BaseElementSelectConditionRule::allowMultiple()`.
- Added `craft\base\conditions\BaseElementSelectConditionRule::getElementIds()`.
- Added `craft\base\conditions\BaseElementSelectConditionRule::setElementIds()`.
- Added `craft\fields\data\LinkData::$urlSuffix`.
- Added `craft\fields\data\LinkData::getUrl()`.
- `craft\models\Site` now implements `craft\base\Chippable`.
