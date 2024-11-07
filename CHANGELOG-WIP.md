# Release Notes for Craft CMS 5.5 (WIP)

### Content Management
- When saving a nested element within a Matrix/Addresses field in card view, the changes are now saved to a draft of the owner element, rather than published immediately. ([#16002](https://github.com/craftcms/cms/pull/16002))
- Nested element cards now show status indicators if they are new or contain unpublished changes. ([#16002](https://github.com/craftcms/cms/pull/16002))
- Improved the styling of element cards with thumbnails. ([#15692](https://github.com/craftcms/cms/pull/15692), [#15673](https://github.com/craftcms/cms/issues/15673))
- Elements within element selection inputs now have “Replace” actions.
- Entry types listed within entry indexes now show their icon and color. ([#15922](https://github.com/craftcms/cms/discussions/15922))
- Address index tables can now include “Country” columns.
- Action button cells within editable tables are now center-aligned vertically.
- Dropdown cells within editable tables are no longer center-aligned. ([#15742](https://github.com/craftcms/cms/issues/15742))
- Link fields marked as translatable now swap the selected element with the localized version when their value is getting propagated to a new site for a freshly-created element. ([#15821](https://github.com/craftcms/cms/issues/15821))
- Pressing <kbd>Return</kbd> when an inline-editable field is focused now submits the inline form. (Previously <kbd>Ctrl</kbd>/<kbd>Command</kbd> had to be pressed as well.) ([#15841](https://github.com/craftcms/cms/issues/15841))
- Improved the styling of element edit page headers, for elements with long titles. ([#16001](https://github.com/craftcms/cms/pull/16001))

### Accessibility
- Improved the control panel for screen readers. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved keyboard control. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved the color contrast of required field indicators. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved the accessibility of text inputs for screen readers.
- It’s now possible to move an image’s focal point without dragging it. ([#15904](https://github.com/craftcms/cms/pull/15904))

### Administration
- Added the “Range” field type. ([#15972](https://github.com/craftcms/cms/pull/15972))
- Added the “Allow custom options” setting to Checkboxes and Radio Buttons fields.
- Added the “Show the ‘Label’ field” and “Show the ‘Open in a new tab’ field” settings to Link fields. ([#15983](https://github.com/craftcms/cms/pull/15983))
- Link fields’ Allowed Link Types settings are now sortable. ([#15963](https://github.com/craftcms/cms/pull/15963))
- All relation fields can now be selected as field layouts’ thumbnail providers. ([#15651](https://github.com/craftcms/cms/discussions/15651))
- It’s now possible to include element attributes in card views, alongside custom fields, via new “Card Attributes” configurators. ([#15283](https://github.com/craftcms/cms/pull/15283)) 
- Added the “Markdown” field layout UI element type. ([#15674](https://github.com/craftcms/cms/pull/15674), [#15664](https://github.com/craftcms/cms/discussions/15664))
- Added the “Language” element condition rule. ([#15952](https://github.com/craftcms/cms/discussions/15952))
- The Sections index table can now be sorted by Name, Handle, and Type. ([#15936](https://github.com/craftcms/cms/pull/15936))
- Sections are no longer required to have unique names. ([#9829](https://github.com/craftcms/cms/discussions/9829))
- Customize Sources modals now display native sources’ handles, when known.
- Removed the “Show the Title field” entry type setting. The “Title” element can now be removed from the field layout instead. ([#15942](https://github.com/craftcms/cms/pull/15942))
- Entry types can now specify a Default Title Format, which will be used even if the Title field is included in the field layout, to generate a default Title value if the field is blank. ([#15942](https://github.com/craftcms/cms/pull/15942))
- It’s now possible to control whether entry types’ Title fields are required. ([#15942](https://github.com/craftcms/cms/pull/15942))
- Added the “Step Size” Number field setting.
- Added the “Default View Mode” element source setting. ([#15824](https://github.com/craftcms/cms/pull/15824))
- Added several new icons.
- Added `pc/*` commands as an alias of `project-config/*`.
- Added the `resave/all` command.
- Added the `--except`, `--minor-only`, and `--patch-only` options to the `update` command. ([#15829](https://github.com/craftcms/cms/pull/15829))
- Added the `--with-fields` option to all native `resave/*` commands.
- The `fields/merge` and `fields/auto-merge` commands now prompt to resave elements that include relational fields before merging them, and provide a CLI command that should be run on other environments before the changes are deployed to them. ([#15869](https://github.com/craftcms/cms/issues/15869))

### Development
- Added the `encodeUrl()` Twig function. ([#15838](https://github.com/craftcms/cms/issues/15838))
- `{% cache %}` tags now support setting the duration number to an expression. ([#15970](https://github.com/craftcms/cms/discussions/15970))
- Added support for passing aliased field handles into element queries’ `select()`/`addSelect()` methods. ([#15827](https://github.com/craftcms/cms/issues/15827))
- Added support for appending subpaths to environment variable names in environmental settings (e.g. `$PRIMARY_SITE_URL/uploads`).

### Extensibility
- Added `craft\base\Element::EVENT_REGISTER_CARD_ATTRIBUTES`.
- Added `craft\base\Element::defineCardAttributes()`.
- Added `craft\base\ElementInterface::attributePreviewHtml()`.
- Added `craft\base\ElementInterface::cardAttributes()`.
- Added `craft\base\ElementInterface::indexViewModes()`.
- Added `craft\base\NestedElementTrait::saveOwnership()`. ([#15894](https://github.com/craftcms/cms/pull/15894))
- Added `craft\base\PreviewableFieldInterface::previewPlaceholderHtml()`.
- Added `craft\base\RequestTrait::getIsWebRequest()`. ([#15690](https://github.com/craftcms/cms/pull/15690))
- Added `craft\console\Controller::output()`. 
- Added `craft\console\controllers\ResaveController::hasTheFields()`.
- Added `craft\elements\db\NestedElementQueryTrait`. ([#15894](https://github.com/craftcms/cms/pull/15894))
- Added `craft\events\ApplyFieldSaveEvent`. ([#15872](https://github.com/craftcms/cms/discussions/15872))
- Added `craft\events\DefineAddressCountriesEvent`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\events\RegisterElementCardAttributesEvent`.
- Added `craft\fieldlayoutelements\Template::$templateMode`. ([#15932](https://github.com/craftcms/cms/pull/15932))
- Added `craft\fields\data\LinkData::$target`.
- Added `craft\fields\data\LinkData::setLabel()`.
- Added `craft\filters\BasicHttpAuthLogin`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\BasicHttpAuthStatic`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\ConditionalFilterTrait`. ([#15948](https://github.com/craftcms/cms/pull/15948))
- Added `craft\filters\UtilityAccess`.
- Added `craft\helpers\Console::$outputCount`.
- Added `craft\helpers\Console::$prependNewline`.
- Added `craft\helpers\Console::indent()`.
- Added `craft\helpers\Console::indentStr()`.
- Added `craft\helpers\Console::outdent()`.
- Added `craft\helpers\Cp::cardPreviewHtml()`.
- Added `craft\helpers\Cp::cardViewDesignerHtml()`.
- Added `craft\helpers\Cp::rangeFieldHtml()`. ([#15972](https://github.com/craftcms/cms/pull/15972))
- Added `craft\helpers\Cp::rangeHtml()`. ([#15972](https://github.com/craftcms/cms/pull/15972))
- Added `craft\helpers\ElementHelper::linkAttributeHtml()`.
- Added `craft\helpers\ElementHelper::uriAttributeHtml()`.
- Added `craft\helpers\Session::addFlash()`.
- Added `craft\helpers\Session::getAllFlashes()`.
- Added `craft\helpers\Session::getFlash()`.
- Added `craft\helpers\Session::hasFlash()`.
- Added `craft\helpers\Session::removeAllFlashes()`.
- Added `craft\helpers\Session::removeFlash()`.
- Added `craft\helpers\StringHelper::firstLine()`.
- Added `craft\helpers\UrlHelper::encodeUrl()`. ([#15838](https://github.com/craftcms/cms/issues/15838))
- Added `craft\log\MonologTarget::getAllowLineBreaks()`.
- Added `craft\log\MonologTarget::getFormatter()`.
- Added `craft\log\MonologTarget::getLevel()`.
- Added `craft\log\MonologTarget::getMaxFiles()`.
- Added `craft\log\MonologTarget::getName()`.
- Added `craft\log\MonologTarget::getProcessor()`.
- Added `craft\log\MonologTarget::getUseMicrosecondTimestamps()`.
- Added `craft\models\FieldLayout::getCardBodyAttributes()`.
- Added `craft\models\FieldLayout::getCardBodyElements()`.
- Added `craft\models\FieldLayout::getCardView()`.
- Added `craft\models\FieldLayout::prependElements()`.
- Added `craft\models\FieldLayout::setCardView()`.
- Added `craft\services\Addresses::EVENT_DEFINE_ADDRESS_COUNTRIES`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\services\Addresses::getCountryList()`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\services\Fields::EVENT_BEFORE_APPLY_FIELD_SAVE`. ([#15872](https://github.com/craftcms/cms/discussions/15872))
- Added `craft\services\Users::getMaxUsers()`.
- Added `craft\web\View::registerCpTwigExtension()`.
- Added `craft\web\View::registerSiteTwigExtension()`.
- `craft\fields\data\LinkData::getLabel()` now has a `$custom` argument.
- `craft\helpers\Console::output()` now prepends an indent to each line of the passed-in string, if `indent()` had been called prior.
- Added the `elements/save-nested-element-for-draft` action. ([#16002](https://github.com/craftcms/cms/pull/16002))
- Improved support for creating log targets for third party logging services. ([#14974](https://github.com/craftcms/cms/pull/14974))
- Deprecated the `enableBasicHttpAuth` config setting. `craft\filters\BasicHttpAuthLogin` should be used instead. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added the `serializeForm` event to `Craft.ElementEditor`. ([#15794](https://github.com/craftcms/cms/discussions/15794))
- Added the `range()` and `rangeField()` macros to `_includes/forms.twig`. ([#15972](https://github.com/craftcms/cms/pull/15972))
- Added the `fieldLayoutDesigner()` and `cardViewDesigner()` global Twig functions for control panel templates.
- Field layout designers can now be instantiated with a `withCardViewDesigner` param. ([#15283](https://github.com/craftcms/cms/pull/15283)
- Checkbox selects can now be passed a `sortable` option. ([#15963](https://github.com/craftcms/cms/pull/15963))
- Deprecated the `craft.cp.fieldLayoutDesigner()` function. The global `fieldLayoutDesigner()` function should be used instead.

### System
- `Location` headers added via `craft\web\Response::redirect()` are now set to encoded URLs. ([#15838](https://github.com/craftcms/cms/issues/15838))
- Fixed a bug where the Recovery Codes slideout content overflowed its container on small screens. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Fixed a bug where entries that were soft-deleted along with their section weren’t getting restored if the section was restored. 
- Fixed a bug where field types weren’t getting a chance to normalize their values when propagated to a new site for a freshly-created element, if they were marked as translatable.
