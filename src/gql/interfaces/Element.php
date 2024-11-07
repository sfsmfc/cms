<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces;

use Craft;
use craft\attributes\GqlField;
use craft\gql\base\InterfaceType;
use craft\gql\base\SingularTypeInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;
use craft\gql\types\generators\ElementType;
use craft\helpers\Attributes;
use craft\helpers\Gql as GqlHelper;
use craft\services\Gql;
use GraphQL\Type\Definition\InterfaceType as GqlInterfaceType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * Class Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Element extends InterfaceType implements SingularTypeInterface
{
    /**
     * @var string
     * @since 5.x.x
     */
    public static string $element = \craft\base\Element::class;

    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return ElementType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new GqlInterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all elements.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        ElementType::generateTypes();

        return $type;
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @since 5.x.x
     */
    public static function getElementFieldDefinitions(): array
    {
        return Attributes::getPropertiesByAttribute(static::$element, GqlField::class)
            ->map(function(\ReflectionProperty|\ReflectionMethod $prop) {
                /** @var GqlField $attr */
                $attr = $prop->getAttributes(GqlField::class, \ReflectionAttribute::IS_INSTANCEOF)[0]->newInstance();

                return $attr->getFieldDefinition($prop);
            })
            ->keyBy('name')
            ->all();
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            Gql::GRAPHQL_COUNT_FIELD => [
                'name' => Gql::GRAPHQL_COUNT_FIELD,
                'type' => Type::int(),
                'args' => [
                    'field' => [
                        'name' => 'field',
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The handle of the field that holds the relations.',
                    ],
                ],
                'description' => 'Return a number of related elements for a field.',
                'complexity' => GqlHelper::eagerLoadComplexity(),
            ],
            'siteHandle' => [
                'name' => 'siteHandle',
                'type' => Type::string(),
                'description' => 'The handle of the site the element is associated with.',
            ],
        ]), self::getName());
    }

    /**
     * List the draft field definitions.
     *
     * @return array
     */
    public static function getDraftFieldDefinitions(): array
    {
        return [
            'isDraft' => [
                'name' => 'isDraft',
                'type' => Type::boolean(),
                'description' => 'Returns whether this is a draft.',
            ],
            'isRevision' => [
                'name' => 'isRevision',
                'type' => Type::boolean(),
                'description' => 'Returns whether this is a revision.',
            ],
            'revisionId' => [
                'name' => 'revisionId',
                'type' => Type::int(),
                'description' => 'The revision ID (from the `revisions` table).',
            ],
            'revisionNotes' => [
                'name' => 'revisionNotes',
                'type' => Type::String(),
                'description' => 'The revision notes (from the `revisions` table).',
            ],
            'draftId' => [
                'name' => 'draftId',
                'type' => Type::int(),
                'description' => 'The draft ID (from the `drafts` table).',
            ],
            'isUnpublishedDraft' => [
                'name' => 'isUnpublishedDraft',
                'type' => Type::boolean(),
                'description' => 'Returns whether this is an unpublished draft.',
            ],
            'draftName' => [
                'name' => 'draftName',
                'type' => Type::string(),
                'description' => 'The name of the draft.',
            ],
            'draftNotes' => [
                'name' => 'draftNotes',
                'type' => Type::string(),
                'description' => 'The notes for the draft.',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'ElementInterface';
    }
}
