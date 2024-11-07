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
        // Use to get a nice version of the property description
        $propertyInfo = new PropertyInfoExtractor(
            descriptionExtractors: [new PhpDocExtractor()]
        );

        return Attributes::getPropertiesByAttribute(static::$element, GqlField::class)
            ->map(function(\ReflectionProperty|\ReflectionMethod $prop) use ($propertyInfo) {
                /** @var GqlField $attr */
                $attr = $prop->getAttributes(GqlField::class, \ReflectionAttribute::IS_INSTANCEOF)[0]->newInstance();

                // When dealing with a method as a property, normalise the name if appplicable
                $name = $prop->getName();
                if ($prop instanceof \ReflectionMethod && str_starts_with($name, 'get')) {
                    $name = str_replace('get', '', $name);
                    $name = lcfirst($name);
                }

                $definition = [
                    'name' => $attr->name ?? $name,
                    'type' => $attr->getType(),
                    'description' => $attr->description ?? $propertyInfo->getShortDescription($prop->class, $name) ?? $prop->getDocComment(),
                ];

                if ($attr->complexity) {
                    $definition['complexity'] = ($attr->complexity)();
                }

                if ($attr->args) {
                    $definition['args'] = ($attr->args)();
                }

                if ($attr->resolve) {
                    $definition['resolve'] = $attr->resolve;
                }

                return $definition;
            })
            ->keyBy('name')
            ->all();
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        $parentFields = parent::getFieldDefinitions();
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge($parentFields, [
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
            'title' => [
                'name' => 'title',
                'type' => Type::string(),
                'description' => 'The element’s title.',
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string(),
                'description' => 'The element’s slug.',
            ],
            'uri' => [
                'name' => 'uri',
                'type' => Type::string(),
                'description' => 'The element’s URI.',
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'Whether the element is enabled.',
            ],
            'archived' => [
                'name' => 'archived',
                'type' => Type::boolean(),
                'description' => 'Whether the element is archived.',
            ],
            'siteHandle' => [
                'name' => 'siteHandle',
                'type' => Type::string(),
                'description' => 'The handle of the site the element is associated with.',
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'The ID of the site the element is associated with.',
            ],
            'siteSettingsId' => [
                'name' => 'siteSettingsId',
                'type' => Type::id(),
                'description' => 'The unique identifier for an element-site relation.',
            ],
            'language' => [
                'name' => 'language',
                'type' => Type::string(),
                'description' => 'The language of the site element is associated with.',
            ],
            'searchScore' => [
                'name' => 'searchScore',
                'type' => Type::int(),
                'description' => 'The element’s search score, if the `search` parameter was used when querying for the element.',
            ],
            'trashed' => [
                'name' => 'trashed',
                'type' => Type::boolean(),
                'description' => 'Whether the element has been soft-deleted.',
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'The element’s status.',
            ],
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => DateTime::getType(),
                'description' => 'The date the element was created.',
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => DateTime::getType(),
                'description' => 'The date the element was last updated.',
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
