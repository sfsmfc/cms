<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\web\RedirectRule;

/**
 * Redirect Rule event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class RedirectRuleEvent extends Event
{
    public RedirectRule $redirectRule;
}
