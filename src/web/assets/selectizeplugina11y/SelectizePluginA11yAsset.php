<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\selectizeplugina11y;

use craft\web\AssetBundle;
use craft\web\assets\selectize\SelectizeAsset;

/**
 * Selectize-plugin-a11y asset bundle.
 */
class SelectizePluginA11yAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __dir__ . '/dist';

        $this->depends = [
            SelectizeAsset::class,
        ];

        $this->js = [
            'selectize-plugin-a11y.js',
        ];

        parent::init();
    }
}
