<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterCpSettingsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class RegisterCpSettingsEvent extends Event
{
    /**
     * @var bool
     * Whether the settings adjusted via this event are ready to be displayed when allowAdminChanges is disabled.
     * @since 5.6.0
     */
    public bool $readOnlyModeReady = false;

    /**
     * @var array The registered control panel settings
     */
    public array $settings = [];
}
