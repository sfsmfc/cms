<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\attributes;

use Attribute;
use GraphQL\Type\Definition\Type;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use yii\base\InvalidConfigException;

/**
 * Class GqlField
 *
 * @param string|array $type The type of the field. Can be a standard type, a custom type, or a callable that returns a type.
 * @param string|null $name The name of the field. If not provided, the property name will be used.
 * @param string|null $description The description of the field. If not provided, the property's PHPDoc comment will be used.
 * @param array|null $args Arguments that the field accepts. If provided, should be a callable.
 * @param array|null $complexity The complexity of the field. If provided, should be a callable.
 * @param string|null $resolve The method to call to resolve the field. If not provided, the property's value will be used.
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
        public ?string $resolve = null,
        public ?array $when = null) {}

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

    /**
     * @param \ReflectionProperty|\ReflectionMethod $prop
     * @return array|null
     * @throws InvalidConfigException
     */
    public function getFieldDefinition(\ReflectionProperty|\ReflectionMethod $prop): ?array
    {
        if ($this->when && !($this->when)()) {
            return null;
        }

        // Use to get a nice version of the property description
        $propertyInfo = new PropertyInfoExtractor(
            descriptionExtractors: [new PhpDocExtractor()]
        );

        // When dealing with a method as a property, normalise the name if applicable
        $name = $prop->getName();
        if ($prop instanceof \ReflectionMethod && str_starts_with($name, 'get')) {
            $name = str_replace('get', '', $name);
            $name = lcfirst($name);
        }

        $definition = [
            'name' => $this->name ?? $name,
            'type' => $this->getType(),
            'description' => $this->description ?? $propertyInfo->getShortDescription($prop->class, $name) ?? $prop->getDocComment(),
        ];

        if ($this->complexity) {
            $definition['complexity'] = ($this->complexity)();
        }

        if ($this->args) {
            $definition['args'] = ($this->args)();
        }

        if ($this->resolve) {
            $definition['resolve'] = $this->resolve;
        }

        return $definition;
    }
}