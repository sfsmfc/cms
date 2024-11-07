<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use Craft;
use craft\gql\arguments\elements\Address as AddressArguments;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\resolvers\elements\Address as AddressResolver;
use craft\gql\types\generators\UserType;
use craft\helpers\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class User extends Element
{
    /**
     * @inheritdoc
     */
    public static string $element = \craft\elements\User::class;

    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return UserType::class;
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
            'description' => 'This is the interface implemented by all users.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        UserType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'UserInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), self::getElementFieldDefinitions(), self::getConditionalFields(), [
        ]), self::getName());
    }

    /**
     * @inheritdoc
     */
    protected static function getConditionalFields(): array
    {
        $volumeUid = Craft::$app->getProjectConfig()->get('users.photoVolumeUid');

        if (Gql::isSchemaAwareOf('volumes.' . $volumeUid)) {
            return [
                'photo' => [
                    'name' => 'photo',
                    'type' => Asset::getType(),
                    'description' => 'The user’s photo.',
                    'complexity' => Gql::eagerLoadComplexity(),
                ],
            ];
        }

        return [];
    }
}
