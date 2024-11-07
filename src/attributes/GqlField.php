<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\attributes;

use Attribute;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;

/**
 * Class GqlField
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.x.x
 */
#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_METHOD)]
class GqlField
{
    public function __construct(
        public string|array $type,
        public ?string $name = null,
        public ?string $description = null,
        public ?array $args = null,
        public ?array $complexity = null,
        public ?string $resolve = null) {}

    /**
     * @return Type
     * @throws InvalidConfigException
     */
    public function getType(): Type
    {
        if (is_string($this->type)) {
            if (in_array($this->type, array_keys(Type::getStandardTypes()))) {
                return Type::getStandardTypes()[$this->type];
            }

            if (!method_exists($this->type, 'getType')) {
                throw new InvalidConfigException('Invalid type provided');
            }

            return $this->type::getType();
        }

        $returnType = null;
        foreach (array_reverse($this->type) as $type) {
            if (is_array($type)) {
                // If required pass the return type as the argument
                $returnType = $returnType == null ? $type() : $type($returnType);

                continue;
            }

            if (in_array($type, array_keys(Type::getStandardTypes()))) {
                $returnType = Type::getStandardTypes()[$type];
                continue;
            }

            if (!method_exists($type, 'getType')) {
                throw new InvalidConfigException('Invalid type provided');
            }

            $returnType = $type::getType();
        }

        return $returnType;
    }
}