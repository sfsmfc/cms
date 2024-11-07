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

    /**
     * @param \ReflectionProperty|\ReflectionMethod $prop
     * @return array
     * @throws InvalidConfigException
     */
    public function getFieldDefinition(\ReflectionProperty|\ReflectionMethod $prop): array
    {
        // Use to get a nice version of the property description
        $propertyInfo = new PropertyInfoExtractor(
            descriptionExtractors: [new PhpDocExtractor()]
        );

        // When dealing with a method as a property, normalise the name if appplicable
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