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

### Administration
- Added the “Show the ‘URL Suffix’ field” setting to Link fields. ([#15813](https://github.com/craftcms/cms/discussions/15813))
- Added the “Affiliated Site” native user field. ([#16174](https://github.com/craftcms/cms/pull/16174))
- Added support for setting site-specific email setting overrides. ([#16187](https://github.com/craftcms/cms/pull/16187))
- Added the “View users” user permission. ([#16206](https://github.com/craftcms/cms/pull/16206))
- Added the “GraphQL Mode” Link field setting. ([#16237](https://github.com/craftcms/cms/pull/16237))
- The Queue Manager utility now shows jobs’ class names. ([#16228](https://github.com/craftcms/cms/pull/16228))

### Development
- Added support for fallback element partial templates, e.g. `_partials/entry.twig` as opposed to `_partials/entry/typeHandle.twig`. ([#16125](https://github.com/craftcms/cms/pull/16125))
- Added the `affiliatedSite` and `affiliatedSiteId` user query and GraphQL params. ([#16174](https://github.com/craftcms/cms/pull/16174))
- Added the `affiliatedSiteHandle` and `affiliatedSiteId` user GraphQL field. ([#16174](https://github.com/craftcms/cms/pull/16174))
- It’s now possible to pass nested custom field value keys into element queries’ `orderBy` and `select` params (e.g. `myDateField.tz`). ([#16157](https://github.com/craftcms/cms/discussions/16157))

### Extensibility
- Added `craft\base\conditions\BaseElementSelectConditionRule::allowMultiple()`.
- Added `craft\base\conditions\BaseElementSelectConditionRule::getElementIds()`.
- Added `craft\base\conditions\BaseElementSelectConditionRule::setElementIds()`.
- Added `craft\elements\User::$affiliatedSiteId`.
- Added `craft\elements\User::getAffiliatedSite()`.
- Added `craft\fields\data\LinkData::$urlSuffix`.
- Added `craft\fields\data\LinkData::getUrl()`.
- Added `craft\gql\types\LinkData`.
- Added `craft\gql\types\generators\LinkDataType`.
- Added `craft\mail\Mailer::$siteId`.
- Added `craft\mail\Mailer::$siteOverrides`.
- Added `craft\models\MailSettings::$siteOverrides`.
- `craft\models\Site` now implements `craft\base\Chippable`.

### System
- Craft now keeps track of which site users registered from. When sending an email from the control panel, the current site is now set to the user’s affiliated site, if known. ([#16174](https://github.com/craftcms/cms/pull/16174))
- Updated Twig to 3.15. ([#16207](https://github.com/craftcms/cms/discussions/16207))
