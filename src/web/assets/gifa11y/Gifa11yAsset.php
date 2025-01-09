<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\gifa11y;

use craft\web\AssetBundle;

/**
 * Gifa11y asset bundle.
 */
class Gifa11yAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __dir__ . '/dist';

        $this->js = [
            'gifa11y.umd.js',
        ];

        parent::init();
    }
}
