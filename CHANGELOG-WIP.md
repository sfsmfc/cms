# Release notes for Craft CMS 5.6 (WIP)

### Content Management
- “Related To”, “Not Related To”, “Author”, and relational field condition rules now allow multiple elements to be specified. ([#16121](https://github.com/craftcms/cms/discussions/16121))
- Improved the styling of inline code fragments. ([#16141](https://github.com/craftcms/cms/pull/16141))
- Added the “Affiliated Site” user condition rule. ([#16174](https://github.com/craftcms/cms/pull/16174))
- The global sidebar no longer shows “Failed” for queue jobs, for users that don’t have access to the Queue Manager. ([#16184](https://github.com/craftcms/cms/issues/16184))

### Accessibility
- Improved the accessibility of Checkboxes and Radio Buttons fields that allow custom options. ([#16080](https://github.com/craftcms/cms/pull/16080))
- Improved the accessibility of control panel icons. ([#16128](https://github.com/craftcms/cms/pull/16128))
- Improved the accessibility of Selectize inputs. ([#16110](https://github.com/craftcms/cms/pull/16110))
- Improved the accessibility of the image rotation control within the Image Editor. ([#16218](https://github.com/craftcms/cms/pull/16218))
- Improved the accessibility of action menus on the Plugins index page.
- Improved the accessibility of “More” and “Advanced” toggle triggers. ([#16293]](https://github.com/craftcms/cms/pull/16293))
- Improved the accessibility of the Craft Support widget. ([#16293]](https://github.com/craftcms/cms/pull/16293))

### Administration
- Added the “Affiliated Site” native user field. ([#16174](https://github.com/craftcms/cms/pull/16174))
- Added support for setting site-specific email setting overrides. ([#16187](https://github.com/craftcms/cms/pull/16187))
- Added the “View users” user permission. ([#16206](https://github.com/craftcms/cms/pull/16206))
- Added the “Advanced Fields” setting to Link fields, with “Target”, “URL Suffix”, “Title Text”, “ARIA Label”, “Class Name”, “ID”, and “Relation (rel)” options. ([#15813](https://github.com/craftcms/cms/discussions/15813))
- Added the “GraphQL Mode” Link field setting. ([#16237](https://github.com/craftcms/cms/pull/16237))
- Added the “Field” entry condition rule, which replaces “Matrix field”, includes a “has a value” operator. ([#16270](https://github.com/craftcms/cms/discussions/16270))
- Section condition rules now have a “has a value” operator. ([#16270](https://github.com/craftcms/cms/discussions/16270))
- Added “Copy plugin handle” and “Copy package name” options to plugins’ action menus on the Plugins index page. ([#16281](https://github.com/craftcms/cms/discussions/16281))
- The Updates utility now shows an action menu for each plugin, with “Copy plugin handle” and “Copy package name” options. ([#16281](https://github.com/craftcms/cms/discussions/16281))
- The Queue Manager utility now shows jobs’ class names. ([#16228](https://github.com/craftcms/cms/pull/16228))
- Improved the wording of field instance action labels. ([#16261](https://github.com/craftcms/cms/discussions/16261))
- Templates rendered for “Template” field layout UI elements can now call control panel template functions like `elementChip()` and `elementCard()`. ([#16267](https://github.com/craftcms/cms/issues/16267))
- Improved the error output for nested elements when they can’t be resaved via `resave` commands.
- `resave` commands’ `--drafts`, `--provisional-drafts`, and `--revisions` options can now be set to `null`, causing elements to be resaved regardless of whether they’re drafts/provisional drafts/revisions.

### Development
- Added support for fallback element partial templates, e.g. `_partials/entry.twig` as opposed to `_partials/entry/typeHandle.twig`. ([#16125](https://github.com/craftcms/cms/pull/16125))
- Added the `affiliatedSite` and `affiliatedSiteId` user query and GraphQL params. ([#16174](https://github.com/craftcms/cms/pull/16174))
- Added the `affiliatedSiteHandle` and `affiliatedSiteId` user GraphQL field. ([#16174](https://github.com/craftcms/cms/pull/16174))
- Added the `PHP_INT_MAX` global Twig variable.
- It’s now possible to pass nested custom field value keys into element queries’ `orderBy` and `select` params (e.g. `myDateField.tz`). ([#16157](https://github.com/craftcms/cms/discussions/16157))
- It’s now possible to set Link field values to arrays with `value` keys set to element instances or IDs. ([#16255](https://github.com/craftcms/cms/pull/16255))
- The `indexOf` Twig filter now has a `default` argument, which can be any integer or `null`. (`-1` by default for backwards compatibility.)

### Extensibility
- Added `craft\base\conditions\BaseElementSelectConditionRule::allowMultiple()`.
- Added `craft\base\conditions\BaseElementSelectConditionRule::getElementIds()`.
- Added `craft\base\conditions\BaseElementSelectConditionRule::setElementIds()`.
- Added `craft\elements\User::$affiliatedSiteId`.
- Added `craft\elements\User::getAffiliatedSite()`.
- Added `craft\elements\conditions\entries\FieldConditionRule`.
- Added `craft\fields\data\LinkData::$ariaLabel`.
- Added `craft\fields\data\LinkData::$class`.
- Added `craft\fields\data\LinkData::$id`.
- Added `craft\fields\data\LinkData::$rel`.
- Added `craft\fields\data\LinkData::$title`.
- Added `craft\fields\data\LinkData::$urlSuffix`.
- Added `craft\fields\data\LinkData::getUrl()`.
- Added `craft\gql\types\LinkData`.
- Added `craft\gql\types\generators\LinkDataType`.
- Added `craft\mail\Mailer::$siteId`.
- Added `craft\mail\Mailer::$siteOverrides`.
- Added `craft\models\MailSettings::$siteOverrides`.
- Added `craft\web\View::setTwig()`.
- `craft\elements\NestedElementManager::getIndexHtml()` now supports passing `defaultSort` in the `$config` array. ([#16236](https://github.com/craftcms/cms/discussions/16236))
- `craft\elements\conditions\entries\MatrixFieldConditionRule` is now an alias of `FieldConditionRule`.
- `craft\helpers\Cp::elementIndexHtml()` now supports passing `defaultSort` in the `$config` array, when `sources` is `null`. ([#16236](https://github.com/craftcms/cms/discussions/16236))
- `craft\models\Site` now implements `craft\base\Chippable`.
- `craft\services\Revisions::createRevision()` no longer creates the revision if an `EVENT_BEFORE_CREATE_REVISION` event handler sets `$event->handled` to `true` and at least one revision already exists for the element. ([#16260](https://github.com/craftcms/cms/discussions/16260))
- Deprecated `craft\fields\Link::$showTargetField`.
- Sortable checkbox selects now always display the selected options first on initial render. 

### System
- Craft now keeps track of which site users registered from. When sending an email from the control panel, the current site is now set to the user’s affiliated site, if known. ([#16174](https://github.com/craftcms/cms/pull/16174))
- Database rows with foreign keys referencing nonexistent rows are now deleted via garbage collection.
- Updated Twig to 3.15. ([#16207](https://github.com/craftcms/cms/discussions/16207))
