<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use Craft;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\types\generators\AddressType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Address
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends Element
{
    /**
     * @inheritdoc
     */
    public static string $element = \craft\elements\Address::class;

    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return AddressType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all addresses.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        AddressType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'AddressInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(
            parent::getFieldDefinitions(),

            /**
             * @TODO figure out how to move this to the base element class so it doesn't need to be repeated in every element interface
             * The issue with it currently is that `prepareFieldDefinitions` is called on every extended class and it that call it uses `self::getName()`
             * this essentially "stacks" the definitions. Ideally the base class would call `static::getName()` instead to memoize the data
             */
            self::getElementFieldDefinitions()
        ), self::getName());
    }
}
