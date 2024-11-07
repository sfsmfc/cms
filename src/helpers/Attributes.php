<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\db\Query;
use Exception;
use Illuminate\Support\Collection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use yii\db\Expression;

/**
 * Attributes helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.x.x
 */
class Attributes
{
    /**
     * @param object|string $class The class name or object to get the attributes from
     * @param string $name The name of the attribute
     * @param int $flags The flags to pass to ReflectionAttribute::getAttributes()
     * @return Collection
     * @throws ReflectionException
     */
    public static function getPropertiesByAttribute(object|string $class, string $name, int $flags = ReflectionAttribute::IS_INSTANCEOF): Collection
    {
        $reflector = new ReflectionClass($class);
        $properties = new Collection([...$reflector->getProperties(), ...$reflector->getMethods(\ReflectionMethod::IS_PUBLIC)]);

        return  $properties->filter(fn(\ReflectionProperty|\ReflectionMethod $prop) => !empty($prop->getAttributes($name, $flags)));
    }
}
