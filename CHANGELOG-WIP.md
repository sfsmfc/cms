# Release notes for Craft CMS 4.14 (WIP)

## Content Management
- The global sidebar no longer shows “Failed” for queue jobs, for users that don’t have access to the Queue Manager. ([#16184](https://github.com/craftcms/cms/issues/16184))

## Administration
- The Queue Manager utility now shows jobs’ class names. ([#16228](https://github.com/craftcms/cms/pull/16228)) 

## System
- Database rows with foreign keys referencing nonexistent rows are now deleted via garbage collection.
- Pages which contain image transform generation URLs now set no-cache headers. ([#16195](https://github.com/craftcms/cms/discussions/16195))
- Updated Twig to 3.15. ([#16207](https://github.com/craftcms/cms/discussions/16207))
