# Release notes for Craft CMS 4.14 (WIP)

## Content Management
- The global sidebar no longer shows “Failed” for queue jobs, for users that don’t have access to the Queue Manager. ([#16184](https://github.com/craftcms/cms/issues/16184))

## Administration
- The Queue Manager utility now shows jobs’ class names. ([#16228](https://github.com/craftcms/cms/pull/16228)) 

## Development
- The `duration` Twig filter now has a `language` argument. ([#16332](https://github.com/craftcms/cms/pull/16332))
- Deprecated the `ucfirst` Twig filter. `capitalize` should be used instead.

## Extensibility
- `GuzzleHttp\Client` is now instantiated via `Craft::createObject()`. ([#16366](https://github.com/craftcms/cms/pull/16366))
- `craft\helpers\DateTimeHelper::humanDuration()` now has a `$language` argument. ([#16332](https://github.com/craftcms/cms/pull/16332))

## System
- Database rows with foreign keys referencing nonexistent rows are now deleted via garbage collection.
- Pages which contain image transform generation URLs now set no-cache headers. ([#16195](https://github.com/craftcms/cms/discussions/16195))
- Action requests (such as `actions/app/health-check`) now send no-cache headers by default. ([#16364](https://github.com/craftcms/cms/pull/16364))
- Fixed a bug where `craft\config\GeneralConfig::safeMode()` set Safe Mode to `false` by default.
- Updated Twig to 3.15. ([#16207](https://github.com/craftcms/cms/discussions/16207))
