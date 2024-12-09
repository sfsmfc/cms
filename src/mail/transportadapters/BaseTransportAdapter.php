<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use craft\base\ConfigurableComponent;
use yii\base\Model;

/**
 * Php implements a PHP Mail transport adapter into Craft’s mailer.
 *
 * @mixin Model
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class BaseTransportAdapter extends ConfigurableComponent implements TransportAdapterInterface
{
    /**
     * Returns whether the transport adapter's settings are ready to show its fields in a disabled mode.
     *
     * @return bool
     * @since 5.6.0
     */
    public function readOnlySettingsReady(): bool
    {
        return false;
    }
}
