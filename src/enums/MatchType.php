<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

enum MatchType: string
{
    case Exact = 'exact';
    case Regex = 'regex';
}
