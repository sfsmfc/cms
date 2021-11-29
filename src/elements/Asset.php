<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\CopyReferenceTag;
use craft\elements\actions\CopyUrl;
use craft\elements\actions\DeleteAssets;
use craft\elements\actions\DownloadAssetFile;
use craft\elements\actions\Edit;
use craft\elements\actions\EditImage;
use craft\elements\actions\PreviewAsset;
use craft\elements\actions\RenameFile;
use craft\elements\actions\ReplaceFile;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\AssetException;
use craft\errors\FileException;
use craft\errors\ImageTransformException;
use craft\errors\VolumeException;
use craft\events\AssetEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\ImageTransforms;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\image\transforms\DeferredTransformerInterface;
use craft\models\FieldLayout;
use craft\models\ImageTransform;
use craft\models\VolumeFolder;
use craft\records\Asset as AssetRecord;
use craft\validators\AssetLocationValidator;
use craft\validators\DateTimeValidator;
use craft\validators\StringValidator;
use craft\volumes\Temp;
use DateTime;
use Throwable;
use Twig\Markup;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;

/**
 * Asset represents an asset element.
 *
 * @property int|float|null $height the image height
 * @property int|float|null $width the image width
 * @property int|null $volumeId the volume ID
 * @property string $filename the filename (with extension)
 * @property string|array|null $focalPoint the focal point represented as an array with `x` and `y` keys, or null if it's not an image
 * @property-read Markup|null $img an `<img>` tag based on this asset
 * @property-read VolumeFolder $folder the asset’s volume folder
 * @property-read VolumeInterface $volume the asset’s volume
 * @property-read bool $hasFocalPoint whether a user-defined focal point is set on the asset
 * @property-read string $extension the file extension
 * @property-read string $path the asset's path in the volume
 * @property-read string|null $mimeType the file’s MIME type, if it can be determined
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Asset extends Element
{
    // Events
    // -------------------------------------------------------------------------

    /**
     * @event AssetEvent The event that is triggered before an asset is uploaded to volume.
     */
    public const EVENT_BEFORE_HANDLE_FILE = 'beforeHandleFile';

    // Location error codes
    // -------------------------------------------------------------------------

    public const ERROR_DISALLOWED_EXTENSION = 'disallowed_extension';
    public const ERROR_FILENAME_CONFLICT = 'filename_conflict';

    // Validation scenarios
    // -------------------------------------------------------------------------

    /**
     * Validation scenario that should be used when the asset is only getting *moved*; not renamed.
     *
     * @since 3.7.1
     */
    public const SCENARIO_MOVE = 'move';
    public const SCENARIO_FILEOPS = 'fileOperations';
    public const SCENARIO_INDEX = 'index';
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_REPLACE = 'replace';

    // File kinds
    // -------------------------------------------------------------------------

    public const KIND_ACCESS = 'access';
    public const KIND_AUDIO = 'audio';
    /**
     * @since 3.6.0
     */
    public const KIND_CAPTIONS_SUBTITLES = 'captionsSubtitles';
    public const KIND_COMPRESSED = 'compressed';
    public const KIND_EXCEL = 'excel';
    public const KIND_FLASH = 'flash';
    public const KIND_HTML = 'html';
    public const KIND_ILLUSTRATOR = 'illustrator';
    public const KIND_IMAGE = 'image';
    public const KIND_JAVASCRIPT = 'javascript';
    public const KIND_JSON = 'json';
    public const KIND_PDF = 'pdf';
    public const KIND_PHOTOSHOP = 'photoshop';
    public const KIND_PHP = 'php';
    public const KIND_POWERPOINT = 'powerpoint';
    public const KIND_TEXT = 'text';
    public const KIND_VIDEO = 'video';
    public const KIND_WORD = 'word';
    public const KIND_XML = 'xml';
    public const KIND_UNKNOWN = 'unknown';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Asset');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'asset');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Assets');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'assets');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'asset';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return AssetQuery The newly created [[AssetQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new AssetQuery(static::class);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        if ($handle === 'uploader') {
            // Get the source element IDs
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select(['id as source', 'uploaderId as target'])
                ->from([Table::ASSETS])
                ->where(['and', ['id' => $sourceElementIds], ['not', ['uploaderId' => null]]])
                ->all();

            return [
                'elementType' => User::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function setEagerLoadedElements(string $handle, array $elements): void
    {
        if ($handle === 'uploader') {
            $uploader = $elements[0] ?? null;
            $this->setUploader($uploader);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext($context): string
    {
        return $context->handle . '_Asset';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext($context): array
    {
        return ['volumes.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext($context): string
    {
        /** @var VolumeInterface $context */
        return 'save_' . $context->handle . '_Asset';
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(?string $context = null): array
    {
        $volumes = Craft::$app->getVolumes();

        if ($context === 'index') {
            $sourceIds = $volumes->getViewableVolumeIds();
        } else {
            $sourceIds = $volumes->getAllVolumeIds();
        }

        $additionalCriteria = $context === 'settings' ? ['parentId' => ':empty:'] : [];

        $tree = Craft::$app->getAssets()->getFolderTreeByVolumeIds($sourceIds, $additionalCriteria);

        $sourceList = self::_assembleSourceList($tree, $context !== 'settings', Craft::$app->getUser()->getIdentity());

        // Add the Temporary Uploads location, if that's not set to a real volume
        if (
            $context !== 'settings' &&
            !Craft::$app->getRequest()->getIsConsoleRequest() &&
            !Craft::$app->getProjectConfig()->get('assets.tempVolumeUid')
        ) {
            $temporaryUploadFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();
            $temporaryUploadFolder->name = Craft::t('app', 'Temporary Uploads');
            $sourceList[] = self::_assembleSourceInfoForFolder($temporaryUploadFolder, false);
        }

        return $sourceList;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];
        if (
            preg_match('/^folder:(.+)$/', $source, $matches) &&
            ($folder = Craft::$app->getAssets()->getFolderByUid($matches[1])) &&
            $fieldLayout = $folder->getVolume()->getFieldLayout()
        ) {
            $fieldLayouts[] = $fieldLayout;
        }
        return $fieldLayouts;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        $actions = [];

        // Only match the first folder ID - ignore nested folders
        if (
            preg_match('/^folder:([a-z0-9\-]+)/', $source, $matches) &&
            $folder = Craft::$app->getAssets()->getFolderByUid($matches[1])
        ) {
            $volume = $folder->getVolume();
            $isTemp = $volume instanceof Temp;

            $actions[] = [
                'type' => PreviewAsset::class,
                'label' => Craft::t('app', 'Preview file'),
            ];

            // Download
            $actions[] = DownloadAssetFile::class;

            // Edit
            $actions[] = [
                'type' => Edit::class,
                'label' => Craft::t('app', 'Edit asset'),
            ];

            $userSession = Craft::$app->getUser();
            if ($isTemp || $userSession->checkPermission("replaceFilesInVolume:$volume->uid")) {
                // Rename/Replace File
                $actions[] = RenameFile::class;
                $actions[] = ReplaceFile::class;
            }

            // Copy URL
            if ($volume->hasUrls) {
                $actions[] = CopyUrl::class;
            }

            // Copy Reference Tag
            $actions[] = CopyReferenceTag::class;

            // Edit Image
            if ($isTemp || $userSession->checkPermission("editImagesInVolume:$volume->uid")) {
                $actions[] = EditImage::class;
            }

            // Delete
            if ($isTemp || $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid")) {
                $actions[] = DeleteAssets::class;
            }
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['filename', 'extension', 'kind'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'filename' => Craft::t('app', 'Filename'),
            'size' => Craft::t('app', 'File Size'),
            [
                'label' => Craft::t('app', 'File Modification Date'),
                'orderBy' => 'dateModified',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Uploaded'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'filename' => ['label' => Craft::t('app', 'Filename')],
            'size' => ['label' => Craft::t('app', 'File Size')],
            'kind' => ['label' => Craft::t('app', 'File Kind')],
            'imageSize' => ['label' => Craft::t('app', 'Dimensions')],
            'width' => ['label' => Craft::t('app', 'Image Width')],
            'height' => ['label' => Craft::t('app', 'Image Height')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateModified' => ['label' => Craft::t('app', 'File Modified Date')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Uploaded')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'uploader' => ['label' => Craft::t('app', 'Uploaded By')],
        ];

        // Hide Author from Craft Solo
        if (Craft::$app->getEdition() !== Craft::Pro) {
            unset($attributes['uploader']);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'filename',
            'size',
            'dateModified',
            'uploader',
            'link',
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        if ($attribute === 'uploader') {
            $elementQuery->andWith('uploader');
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /**
     * Transforms an asset folder tree into a source list.
     *
     * @param array $folders
     * @param bool $includeNestedFolders
     * @param User|null $user
     * @return array
     */
    private static function _assembleSourceList(array $folders, bool $includeNestedFolders = true, ?User $user = null): array
    {
        $sources = [];

        foreach ($folders as $folder) {
            $sources[] = self::_assembleSourceInfoForFolder($folder, $includeNestedFolders, $user);
        }

        return $sources;
    }

    /**
     * Transforms an VolumeFolderModel into a source info array.
     *
     * @param VolumeFolder $folder
     * @param bool $includeNestedFolders
     * @param User|null $user
     * @return array
     */
    private static function _assembleSourceInfoForFolder(VolumeFolder $folder, bool $includeNestedFolders = true, ?User $user = null): array
    {
        $volume = $folder->getVolume();

        if ($volume instanceof Temp) {
            $volumeHandle = 'temp';
        } else if (!$folder->parentId) {
            $volumeHandle = $volume->handle ?? false;
        } else {
            $volumeHandle = false;
        }

        $userSession = Craft::$app->getUser();
        $canUpload = $userSession->checkPermission("saveAssetInVolume:$volume->uid");
        $canMoveTo = $canUpload && $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid");
        $canMovePeerFilesTo = (
            $canMoveTo &&
            $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
            $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
        );

        $source = [
            'key' => 'folder:' . $folder->uid,
            'label' => $folder->parentId ? $folder->name : Craft::t('site', $folder->name),
            'hasThumbs' => true,
            'criteria' => ['folderId' => $folder->id],
            'defaultSort' => ['dateCreated', 'desc'],
            'data' => [
                'volume-handle' => $volumeHandle,
                'folder-id' => $folder->id,
                'can-upload' => $folder->volumeId === null || $canUpload,
                'can-move-to' => $canMoveTo,
                'can-move-peer-files-to' => $canMovePeerFilesTo,
            ],
        ];

        if ($user) {
            if (!$user->can("viewPeerFilesInVolume:$volume->uid")) {
                $source['criteria']['uploaderId'] = $user->id;
            }
        }

        if ($includeNestedFolders) {
            $source['nested'] = self::_assembleSourceList($folder->getChildren(), true, $user);
        }

        return $source;
    }

    /**
     * @var int|null Folder ID
     */
    public ?int $folderId = null;

    /**
     * @var int|null The ID of the user who first added this asset (if known)
     */
    public ?int $uploaderId = null;

    /**
     * @var string|null Folder path
     */
    public ?string $folderPath = null;

    /**
     * @var string|null Kind
     */
    public ?string $kind = null;

    /**
     * @var int|null Size
     */
    public ?int $size = null;

    /**
     * @var bool|null Whether the file was kept around when the asset was deleted
     */
    public ?bool $keptFile = null;

    /**
     * @var DateTime|null Date modified
     */
    public ?DateTime $dateModified = null;

    /**
     * @var string|null New file location
     */
    public ?string $newLocation = null;

    /**
     * @var string|null Location error code
     * @see AssetLocationValidator::validateAttribute()
     */
    public ?string $locationError = null;

    /**
     * @var string|null New filename
     */
    public ?string $newFilename = null;

    /**
     * @var int|null New folder id
     */
    public ?int $newFolderId = null;

    /**
     * @var string|null The temp file path
     */
    public ?string $tempFilePath = null;

    /**
     * @var bool Whether Asset should avoid filename conflicts when saved.
     */
    public bool $avoidFilenameConflicts = false;

    /**
     * @var string|null The suggested filename in case of a conflict.
     */
    public ?string $suggestedFilename = null;

    /**
     * @var string|null The filename that was used that caused a conflict.
     */
    public ?string $conflictingFilename = null;

    /**
     * @var bool Whether the asset was deleted along with its volume
     * @see beforeDelete()
     */
    public bool $deletedWithVolume = false;

    /**
     * @var bool Whether the associated file should be preserved if the asset record is deleted.
     * @see beforeDelete()
     * @see afterDelete()
     */
    public bool $keepFileOnDelete = false;

    /**
     * @var int|null Volume ID
     */
    private ?int $_volumeId = null;

    /**
     * @var string Filename
     */
    private string $_filename;

    /**
     * @var int|float|null Width
     */
    private $_width;

    /**
     * @var int|float|null Height
     */
    private $_height;

    /**
     * @var array|null Focal point
     */
    private ?array $_focalPoint = null;

    /**
     * @var ImageTransform|null
     */
    private ?ImageTransform $_transform = null;

    /**
     * @var string
     */
    private string $_transformSource = '';

    /**
     * @var VolumeInterface|null
     */
    private ?VolumeInterface $_volume = null;

    /**
     * @var User|null
     */
    private ?User $_uploader = null;

    /**
     * @var int|null
     */
    private ?int $_oldVolumeId = null;

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        try {
            if (isset($this->_transform) && ($url = (string)$this->getUrl())) {
                return $url;
            }
        } catch (Throwable $e) {
            ErrorHandler::convertExceptionToError($e);
        }

        return parent::__toString();
    }

    /**
     * Checks if a property is set.
     *
     * This method will check if $name is one of the following:
     * - a magic property supported by [[Element::__isset()]]
     * - an image transform handle
     *
     * @param string $name The property name
     * @return bool Whether the property is set
     */
    public function __isset($name): bool
    {
        return (
            parent::__isset($name) ||
            strncmp($name, 'transform:', 10) === 0 ||
            Craft::$app->getImageTransforms()->getTransformByHandle($name)
        );
    }

    /**
     * Returns a property value.
     *
     * This method will check if $name is one of the following:
     * - a magic property supported by [[Element::__get()]]
     * - an image transform handle
     *
     * @param string $name The property name
     * @return mixed The property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only.
     */
    public function __get($name)
    {
        if (strncmp($name, 'transform:', 10) === 0) {
            return $this->copyWithTransform(substr($name, 10));
        }

        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            // Is $name a transform handle?
            if (($transform = Craft::$app->getImageTransforms()->getTransformByHandle($name)) !== null) {
                return $this->copyWithTransform($transform);
            }

            throw $e;
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init(): void
    {
        parent::init();
        $this->_oldVolumeId = $this->_volumeId;
    }

    /**
     * Returns the volume’s ID.
     *
     * @return int|null
     */
    public function getVolumeId(): ?int
    {
        return (int)$this->_volumeId ?: null;
    }

    /**
     * Sets the volume’s ID.
     *
     * @param int|null $id
     */
    public function setVolumeId(?int $id = null): void
    {
        if ($id !== $this->getVolumeId()) {
            $this->_volumeId = $id;
            $this->_volume = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateModified';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title'], StringValidator::class, 'max' => 255, 'disallowMb4' => true, 'on' => [self::SCENARIO_CREATE]];
        $rules[] = [['volumeId', 'folderId', 'width', 'height', 'size'], 'number', 'integerOnly' => true];
        $rules[] = [['dateModified'], DateTimeValidator::class];
        $rules[] = [['filename', 'kind'], 'required'];
        $rules[] = [['kind'], 'string', 'max' => 50];
        $rules[] = [['newLocation'], 'required', 'on' => [self::SCENARIO_CREATE, self::SCENARIO_MOVE, self::SCENARIO_FILEOPS]];
        $rules[] = [['tempFilePath'], 'required', 'on' => [self::SCENARIO_CREATE, self::SCENARIO_REPLACE]];

        // Validate the extension unless all we're doing is moving the file
        $rules[] = [
            ['newLocation'],
            AssetLocationValidator::class,
            'avoidFilenameConflicts' => $this->avoidFilenameConflicts,
            'except' => [self::SCENARIO_MOVE],
        ];
        $rules[] = [
            ['newLocation'],
            AssetLocationValidator::class,
            'avoidFilenameConflicts' => $this->avoidFilenameConflicts,
            'allowedExtensions' => '*',
            'on' => [self::SCENARIO_MOVE],
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_INDEX] = [];

        return $scenarios;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getCacheTags(): array
    {
        $tags = [
            "volume:$this->_volumeId",
        ];

        // Did the volume just change?
        if ($this->_volumeId != $this->_oldVolumeId) {
            $tags[] = "volume:$this->_oldVolumeId";
        }

        return $tags;
    }

    /**
     * @inheritdoc
     */
    protected function isEditable(): bool
    {
        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        return (
            $userSession->checkPermission("saveAssetInVolume:$volume->uid") &&
            ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("editPeerFilesInVolume:$volume->uid"))
        );
    }

    /**
     * @inheritdoc
     * @since 3.5.15
     */
    protected function isDeletable(): bool
    {
        $volume = $this->getVolume();

        if ($volume instanceof Temp) {
            return true;
        }

        $userSession = Craft::$app->getUser();
        return (
            $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid") &&
            ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid"))
        );
    }

    /**
     * @inheritdoc
     * ---
     * ```php
     * $url = $asset->cpEditUrl;
     * ```
     * ```twig{2}
     * {% if asset.isEditable %}
     *   <a href="{{ asset.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     * @since 3.4.0
     */
    protected function cpEditUrl(): ?string
    {
        $volume = $this->getVolume();
        if ($volume instanceof Temp) {
            return null;
        }

        $filename = $this->getFilename(false);
        $path = "assets/edit/$this->id-$filename";

        $params = [];
        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    /**
     * Returns an `<img>` tag based on this asset.
     *
     * @param mixed $transform The transform to use when generating the html.
     * @param string[]|null $sizes The widths/x-descriptors that should be used for the `srcset` attribute
     * (see [[getSrcset()]] for example syntaxes)
     * @return Markup|null
     * @throws InvalidArgumentException
     */
    public function getImg($transform = null, ?array $sizes = null): ?Markup
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return null;
        }

        $volume = $this->getVolume();

        if (!$volume->hasUrls) {
            return null;
        }

        if ($transform) {
            $oldTransform = $this->_transform;
            $this->setTransform($transform);
        }

        $img = Html::tag('img', '', [
            'src' => $this->getUrl(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'srcset' => $sizes ? $this->getSrcset($sizes) : false,
            'alt' => $this->title,
        ]);

        if (isset($oldTransform)) {
            $this->setTransform($oldTransform);
        }

        return Template::raw($img);
    }

    /**
     * Returns a `srcset` attribute value based on the given widths or x-descriptors.
     *
     * For example, if you pass `['100w', '200w']`, you will get:
     *
     * ```
     * image-url@100w.ext 100w,
     * image-url@200w.ext 200w
     * ```
     *
     * If you pass x-descriptors, it will be assumed that the image’s current width is the indented 1x width.
     * So if you pass `['1x', '2x']` on an image with a 100px-wide transform applied, you will get:
     *
     * ```
     * image-url@100w.ext,
     * image-url@200w.ext 2x
     * ```
     *
     * @param string[] $sizes
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return string|false The `srcset` attribute value, or `false` if it can’t be determined
     * @throws InvalidArgumentException
     * @since 3.5.0
     */
    public function getSrcset(array $sizes, $transform = null)
    {
        $urls = $this->getUrlsBySize($sizes, $transform);

        if (empty($urls)) {
            return false;
        }

        $srcset = [];

        foreach ($urls as $size => $url) {
            if ($size === '1x') {
                $srcset[] = $url;
            } else {
                $srcset[] = "$url $size";
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Returns an array of image transform URLs based on the given widths or x-descriptors.
     *
     * For example, if you pass `['100w', '200w']`, you will get:
     *
     * ```php
     * [
     *     '100w' => 'image-url@100w.ext',
     *     '200w' => 'image-url@200w.ext'
     * ]
     * ```
     *
     * If you pass x-descriptors, it will be assumed that the image’s current width is the indented 1x width.
     * So if you pass `['1x', '2x']` on an image with a 100px-wide transform applied, you will get:
     *
     * ```php
     * [
     *     '1x' => 'image-url@100w.ext',
     *     '2x' => 'image-url@200w.ext'
     * ]
     * ```
     *
     * @param string[] $sizes
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return array
     * @since 3.7.16
     */
    public function getUrlsBySize(array $sizes, $transform = null): array
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return [];
        }

        $urls = [];

        if (
            ($transform !== null || $this->_transform) &&
            Image::canManipulateAsImage($this->getExtension())
        ) {
            $transform = ImageTransforms::normalizeTransform($transform ?? $this->_transform);
        } else {
            $transform = null;
        }

        [$currentWidth, $currentHeight] = $this->_dimensions($transform);

        if (!$currentWidth || !$currentHeight) {
            return [];
        }

        foreach ($sizes as $size) {
            if ($size === '1x') {
                $urls[$size] = $this->getUrl($transform);
                continue;
            }

            [$value, $unit] = Assets::parseSrcsetSize($size);

            $sizeTransform = $transform ? $transform->toArray() : [];

            // Having handle or name here will override dimensions, so we don't want that.
            unset($sizeTransform['handle'], $sizeTransform['name']);

            if ($unit === 'w') {
                $sizeTransform['width'] = (int)$value;
            } else {
                $sizeTransform['width'] = (int)ceil($currentWidth * $value);
            }

            // Only set the height if the current transform has a height set on it
            if ($transform && $transform->height) {
                if ($unit === 'w') {
                    $sizeTransform['height'] = (int)ceil($currentHeight * $sizeTransform['width'] / $currentWidth);
                } else {
                    $sizeTransform['height'] = (int)ceil($currentHeight * $value);
                }
            }

            $urls["$value$unit"] = $this->getUrl($sizeTransform);
        }

        return $urls;
    }

    /**
     * @inheritdoc
     */
    public function getIsTitleTranslatable(): bool
    {
        return ($this->getVolume()->titleTranslationMethod !== Field::TRANSLATION_METHOD_NONE);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationDescription(): ?string
    {
        return ElementHelper::translationDescription($this->getVolume()->titleTranslationMethod);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationKey(): string
    {
        $type = $this->getVolume();
        return ElementHelper::translationKey($this, $type->titleTranslationMethod, $type->titleTranslationKeyFormat);
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        if (($fieldLayout = parent::getFieldLayout()) !== null) {
            return $fieldLayout;
        }

        $volume = $this->getVolume();
        return $volume->getFieldLayout();
    }

    /**
     * Returns the asset’s volume folder.
     *
     * @return VolumeFolder
     * @throws InvalidConfigException if [[folderId]] is missing or invalid
     */
    public function getFolder(): VolumeFolder
    {
        if (!isset($this->folderId)) {
            throw new InvalidConfigException('Asset is missing its folder ID');
        }

        if (($folder = Craft::$app->getAssets()->getFolderById($this->folderId)) === null) {
            throw new InvalidConfigException('Invalid folder ID: ' . $this->folderId);
        }

        return $folder;
    }

    /**
     * Returns the asset’s volume.
     *
     * @return VolumeInterface
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     */
    public function getVolume(): VolumeInterface
    {
        if (isset($this->_volume)) {
            return $this->_volume;
        }

        if (!isset($this->_volumeId)) {
            return new Temp();
        }

        if (($volume = Craft::$app->getVolumes()->getVolumeById($this->_volumeId)) === null) {
            throw new InvalidConfigException('Invalid volume ID: ' . $this->_volumeId);
        }

        return $this->_volume = $volume;
    }

    /**
     * Returns the user that uploaded the asset, if known.
     *
     * @return User|null
     * @since 3.4.0
     */
    public function getUploader(): ?User
    {
        if (isset($this->_uploader)) {
            return $this->_uploader;
        }

        if (!isset($this->uploaderId)) {
            return null;
        }

        if (($this->_uploader = Craft::$app->getUsers()->getUserById($this->uploaderId)) === null) {
            // The uploader is probably soft-deleted. Just pretend no uploader is set
            return null;
        }

        return $this->_uploader;
    }

    /**
     * Sets the asset's uploader.
     *
     * @param User|null $uploader
     * @since 3.4.0
     */
    public function setUploader(?User $uploader = null): void
    {
        $this->_uploader = $uploader;
    }

    /**
     * Sets the transform.
     *
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return Asset
     * @throws ImageTransformException if $transform is an invalid transform handle
     */
    public function setTransform($transform): Asset
    {
        $this->_transform = ImageTransforms::normalizeTransform($transform);

        return $this;
    }

    /**
     * Returns the element’s full URL.
     *
     * @param string|array|null $transform A transform handle or configuration that should be applied to the
     * image If an array is passed, it can optionally include a `transform` key that defines a base transform
     * which the rest of the settings should be applied to.
     * @param bool|null $generateNow Whether the transformed image should be generated immediately if it doesn’t exist. If `null`, it will be left
     * up to the `generateTransformsBeforePageLoad` config setting.
     * @return string|null
     * @throws InvalidConfigException
     */
    public function getUrl($transform = null, ?bool $generateNow = null): ?string
    {
        $volume = $this->getVolume();

        if (!$volume->hasUrls || !$this->folderId) {
            return null;
        }

        $mimeType = $this->getMimeType();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (
            ($mimeType === 'image/gif' && !$generalConfig->transformGifs) ||
            ($mimeType === 'image/svg+xml' && !$generalConfig->transformSvgs)
        ) {
            return Assets::generateUrl($volume, $this);
        }

        // Normalize empty transform values
        $transform = $transform ?: null;

        $imageTransformService = Craft::$app->getImageTransforms();

        if (is_array($transform)) {
            if (isset($transform['width'])) {
                $transform['width'] = round((float)$transform['width']);
            }
            if (isset($transform['height'])) {
                $transform['height'] = round((float)$transform['height']);
            }
            $transform = ImageTransforms::normalizeTransform($transform);
        }

        if ($transform === null) {
            if (!isset($this->_transform)) {
                return AssetsHelper::generateUrl($volume, $this);
            }
            $transform = $this->_transform;
        }

        if ($generateNow === null) {
            $generateNow = Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad;
        }

        $imageTransformer = $transform->getImageTransformer();

        if ($generateNow || !$imageTransformer instanceof DeferredTransformerInterface) {
            return $imageTransformer->getTransformUrl($this, $transform);
        }

        return $imageTransformer->getDeferredTransformUrl($this, $transform);
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size): ?string
    {
        if ($this->getWidth() && $this->getHeight()) {
            [$width, $height] = Assets::scaledDimensions($this->getWidth(), $this->getHeight(), $size, $size);
        } else {
            $width = $height = $size;
        }

        return Craft::$app->getAssets()->getThumbUrl($this, $width, $height, false);
    }

    /**
     * @inheritdoc
     */
    public function getHasCheckeredThumb(): bool
    {
        return in_array(strtolower($this->getExtension()), ['png', 'gif', 'svg'], true);
    }

    /**
     * Returns preview thumb image HTML.
     *
     * @param int $width
     * @param int $height
     * @return string
     * @throws NotSupportedException if the asset can't have a thumbnail, and $fallbackToIcon is `false`
     * @since 3.4.0
     */
    public function getPreviewThumbImg(int $width, int $height): string
    {
        $assetsService = Craft::$app->getAssets();
        $srcsets = [];
        [$width, $height] = Assets::scaledDimensions($this->getWidth() ?? 0, $this->getHeight() ?? 0, $width, $height);
        $thumbSizes = [
            [$width, $height],
            [$width * 2, $height * 2],
        ];
        foreach ($thumbSizes as [$width, $height]) {
            $thumbUrl = $assetsService->getThumbUrl($this, $width, $height, false);
            $srcsets[] = $thumbUrl . ' ' . $width . 'w';
        }

        return Html::tag('img', '', [
            'sizes' => "{$thumbSizes[0][0]}px",
            'srcset' => implode(', ', $srcsets),
            'alt' => $this->title,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPreviewTargets(): array
    {
        return [];
    }

    /**
     * Returns the filename, with or without the extension.
     *
     * @param bool $withExtension
     * @return string
     * @throws InvalidConfigException if the filename isn’t set yet
     */
    public function getFilename(bool $withExtension = true): string
    {
        if (!isset($this->_filename)) {
            throw new InvalidConfigException('Asset not configured with its filename');
        }

        if ($withExtension) {
            return $this->_filename;
        }

        return pathinfo($this->_filename, PATHINFO_FILENAME);
    }

    /**
     * Sets the filename (with extension).
     *
     * @param string $filename
     * @since 4.0.0
     */
    public function setFilename(string $filename): void
    {
        $this->_filename = $filename;
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->_filename, PATHINFO_EXTENSION);
    }

    /**
     * Returns the file’s MIME type, if it can be determined.
     *
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        // todo: maybe we should be passing this off to volume types
        // so Local volumes can call FileHelper::getMimeType() (uses magic file instead of ext)
        return FileHelper::getMimeTypeByExtension($this->_filename);
    }

    /**
     * Returns the image height.
     *
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return int|float|null
     */

    public function getHeight($transform = null)
    {
        return $this->_dimensions($transform)[1];
    }

    /**
     * Sets the image height.
     *
     * @param int|float|null $height the image height
     */
    public function setHeight($height): void
    {
        $this->_height = $height;
    }

    /**
     * Returns the image width.
     *
     * @param ImageTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return int|float|null
     */
    public function getWidth($transform = null)
    {
        return $this->_dimensions($transform)[0];
    }

    /**
     * Sets the image width.
     *
     * @param int|float|null $width the image width
     */
    public function setWidth($width): void
    {
        $this->_width = $width;
    }

    /**
     * Returns the formatted file size, if known.
     *
     * @param int|null $decimals the number of digits after the decimal point
     * @param bool $short whether the size should be returned in short form (“kB” instead of “kilobytes”)
     * @return string|null
     * @since 3.4.0
     */
    public function getFormattedSize(?int $decimals = null, bool $short = true): ?string
    {
        if (!isset($this->size)) {
            return null;
        }
        if ($short) {
            return Craft::$app->getFormatter()->asShortSize($this->size, $decimals);
        }
        return Craft::$app->getFormatter()->asSize($this->size, $decimals);
    }

    /**
     * Returns the formatted file size in bytes, if known.
     *
     * @param bool $short whether the size should be returned in short form (“B” instead of “bytes”)
     * @return string|null
     * @since 3.4.0
     */
    public function getFormattedSizeInBytes(bool $short = true): ?string
    {
        $params = [
            'n' => $this->size,
            'nFormatted' => Craft::$app->getFormatter()->asDecimal($this->size),
        ];
        if ($short) {
            return Craft::t('yii', '{nFormatted} B', $params);
        }
        return Craft::t('yii', '{nFormatted} {n, plural, =1{byte} other{bytes}}', $params);
    }

    /**
     * Returns the image dimensions.
     *
     * @return string|null
     * @since 3.4.0
     */
    public function getDimensions(): ?string
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        if (!$width || !$height) {
            return null;
        }
        return $width . '×' . $height;
    }

    /**
     * Set a source to use for transforms for this Assets File.
     *
     * @param string $uri
     */
    public function setTransformSource(string $uri): void
    {
        $this->_transformSource = $uri;
    }

    /**
     * Returns the asset's path in the volume.
     *
     * @param string|null $filename Filename to use. If not specified, the asset's filename will be used.
     * @return string
     */
    public function getPath(?string $filename = null): string
    {
        return $this->folderPath . ($filename ?: $this->_filename);
    }

    /**
     * Return the path where the source for this Asset's transforms should be.
     *
     * @return string
     */
    public function getImageTransformSourcePath(): string
    {
        $volume = $this->getVolume();

        if ($volume instanceof LocalVolumeInterface) {
            return FileHelper::normalizePath($volume->getRootPath() . DIRECTORY_SEPARATOR . $this->getPath());
        }

        return Craft::$app->getPath()->getAssetSourcesPath() . DIRECTORY_SEPARATOR . $this->id . '.' . $this->getExtension();
    }

    /**
     * Get a temporary copy of the actual file.
     *
     * @return string
     * @throws VolumeException If unable to fetch file from volume.
     * @throws InvalidConfigException If no volume can be found.
     */
    public function getCopyOfFile(): string
    {
        $tempFilename = uniqid(pathinfo($this->_filename, PATHINFO_FILENAME), true) . '.' . $this->getExtension();
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
        Assets::downloadFile($this->getVolume(), $this->getPath(), $tempPath);

        return $tempPath;
    }

    /**
     * Returns a stream of the actual file.
     *
     * @return resource
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws VolumeException if a stream cannot be created
     */
    public function getStream()
    {
        return $this->getVolume()->getFileStream($this->getPath());
    }

    /**
     * Returns the file’s contents.
     *
     * @return string
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     * @since 3.0.6
     */
    public function getContents(): string
    {
        return stream_get_contents($this->getStream());
    }

    /**
     * Generates a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) for the asset.
     *
     * @return string
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     * @since 3.5.13
     */
    public function getDataUrl(): string
    {
        return Html::dataUrlFromString($this->getContents(), $this->getMimeType());
    }

    /**
     * Returns whether this asset can be edited by the image editor.
     *
     * @return bool
     */
    public function getSupportsImageEditor(): bool
    {
        $ext = $this->getExtension();
        return (strcasecmp($ext, 'svg') !== 0 && Image::canManipulateAsImage($ext));
    }

    /**
     * Returns whether a user-defined focal point is set on the asset.
     *
     * @return bool
     */
    public function getHasFocalPoint(): bool
    {
        return isset($this->_focalPoint);
    }

    /**
     * Returns the focal point represented as an array with `x` and `y` keys, or null if it's not an image.
     *
     * @param bool $asCss whether the value should be returned in CSS syntax ("50% 25%") instead
     * @return array|string|null
     */
    public function getFocalPoint(bool $asCss = false)
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return null;
        }

        $focal = $this->_focalPoint ?? ['x' => 0.5, 'y' => 0.5];

        if ($asCss) {
            return ($focal['x'] * 100) . '% ' . ($focal['y'] * 100) . '%';
        }

        return $focal;
    }

    /**
     * Sets the asset's focal point.
     *
     * @param $value string|array|null
     * @throws \InvalidArgumentException if $value is invalid
     */
    public function setFocalPoint($value): void
    {
        if (is_array($value)) {
            if (!isset($value['x'], $value['y'])) {
                throw new \InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float)$value['x'],
                'y' => (float)$value['y'],
            ];
        } else if ($value !== null) {
            $focal = explode(';', $value);
            if (count($focal) !== 2) {
                throw new \InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float)$focal[0],
                'y' => (float)$focal[1],
            ];
        }

        $this->_focalPoint = $value;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'uploader':
                $uploader = $this->getUploader();
                return $uploader ? Cp::elementHtml($uploader) : '';

            case 'filename':
                return Html::tag('span', Html::encode($this->_filename), [
                    'class' => 'break-word',
                ]);

            case 'kind':
                return Assets::getFileKindLabel($this->kind);

            case 'size':
                if (!isset($this->size)) {
                    return '';
                }
                return Html::tag('span', $this->getFormattedSize(0), [
                    'title' => $this->getFormattedSizeInBytes(false),
                ]);

            case 'imageSize':
                return $this->getDimensions() ?? '';

            case 'width':
            case 'height':
                $size = $this->$attribute;
                return ($size ? $size . 'px' : '');
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * Returns the HTML for asset previews.
     *
     * @return string
     * @throws InvalidConfigException
     */
    public function getPreviewHtml(): string
    {
        $html = '';

        // See if we can show a thumbnail
        try {
            // Is the image editable, and is the user allowed to edit?
            $userSession = Craft::$app->getUser();

            $volume = $this->getVolume();
            $previewable = Craft::$app->getAssets()->getAssetPreviewHandler($this) !== null;
            $editable = (
                $this->getSupportsImageEditor() &&
                $userSession->checkPermission("editImagesInVolume:$volume->uid") &&
                ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("editPeerImagesInVolume:$volume->uid"))
            );

            $html = Html::tag('div',
                Html::tag('div', $this->getPreviewThumbImg(350, 190), [
                    'class' => 'preview-thumb',
                ]) .
                Html::tag(
                    'div',
                    ($previewable ? Html::tag('button', Craft::t('app', 'Preview'), ['class' => 'btn preview-btn', 'id' => 'preview-btn', 'type' => 'button']) : '') .
                    ($editable ? Html::tag('button', Craft::t('app', 'Edit'), ['class' => 'btn edit-btn', 'id' => 'edit-btn', 'type' => 'button']) : ''),
                    ['class' => 'buttons']
                ),
                [
                    'class' => array_filter([
                        'preview-thumb-container button-fade',
                        $this->getHasCheckeredThumb() ? 'checkered' : null,
                    ]),
                ]
            );
        } catch (NotSupportedException $e) {
            // NBD
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getSidebarHtml(): string
    {
        return implode("\n", [
            // Omit preview button on sidebar of slideouts
            $this->getPreviewHtml(),
            parent::getSidebarHtml(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        if (!$this->fieldLayoutId) {
            $this->fieldLayoutId = Craft::$app->getRequest()->getBodyParam('defaultFieldLayoutId');
        }

        return parent::getEditorHtml();
    }

    /**
     * @inheritdoc
     */
    protected function metaFieldsHtml(): string
    {
        return implode('', [
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'Filename'),
                'id' => 'newFilename',
                'name' => 'newFilename',
                'value' => $this->_filename,
                'errors' => $this->getErrors('newLocation'),
                'first' => true,
                'required' => true,
                'class' => ['text', 'filename'],
            ]),
            parent::metaFieldsHtml(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function metadata(): array
    {
        $volume = $this->getVolume();

        return [
            Craft::t('app', 'Location') => function() use ($volume) {
                $loc = [Craft::t('site', $volume->name)];
                if ($this->folderPath) {
                    array_push($loc, ...ArrayHelper::filterEmptyStringsFromArray(explode('/', $this->folderPath)));
                }
                return implode(' → ', $loc);
            },
            Craft::t('app', 'File size') => function() {
                $size = $this->getFormattedSize(0);
                if (!$size) {
                    return false;
                }
                $inBytes = $this->getFormattedSizeInBytes(false);
                return Html::tag('div', $size, [
                    'title' => $inBytes,
                    'aria' => [
                        'label' => $inBytes,
                    ],
                ]);
            },
            Craft::t('app', 'Uploaded by') => function() {
                $uploader = $this->getUploader();
                return $uploader ? Cp::elementHtml($uploader) : false;
            },
            Craft::t('app', 'Dimensions') => $this->getDimensions() ?: false,
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getVolume());
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'extension';
        $names[] = 'filename';
        $names[] = 'focalPoint';
        $names[] = 'hasFocalPoint';
        $names[] = 'height';
        $names[] = 'mimeType';
        $names[] = 'path';
        $names[] = 'volumeId';
        $names[] = 'width';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'folder';
        $names[] = 'volume';
        return $names;
    }

    /**
     * Returns a copy of the asset with the given transform applied to it.
     *
     * @param ImageTransform|string|array|null $transform The transform handle or configuration that should be applied to the image
     * @return Asset
     * @throws ImageTransformException if $transform is an invalid transform handle
     */
    public function copyWithTransform($transform): Asset
    {
        $model = clone $this;
        $model->setFieldValues($this->getFieldValues());
        $model->setTransform($transform);

        return $model;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // newFolderId/newFilename => newLocation.
        if ($this->newFilename === '') {
            $this->newFilename = null;
        }
        if (isset($this->newFolderId) || isset($this->newFilename)) {
            $folderId = $this->newFolderId ?: $this->folderId;
            $filename = $this->newFilename ?? $this->_filename;
            $this->newLocation = "{folder:$folderId}$filename";
            $this->newFolderId = $this->newFilename = null;
        }

        // Get the (new?) folder ID
        if (isset($this->newLocation)) {
            [$folderId] = Assets::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
        }

        // Fire a 'beforeHandleFile' event if we're going to be doing any file operations in afterSave()
        if (
            (isset($this->newLocation) || isset($this->tempFilePath)) &&
            $this->hasEventHandlers(self::EVENT_BEFORE_HANDLE_FILE)
        ) {
            $this->trigger(self::EVENT_BEFORE_HANDLE_FILE, new AssetEvent([
                'asset' => $this,
                'isNew' => !$this->id,
            ]));
        }

        // Set the kind based on filename, if not set already
        if (!isset($this->kind) && isset($this->_filename)) {
            $this->kind = Assets::getFileKindByExtension($this->_filename);
        }

        // Give it a default title based on the file name, if it doesn't have a title yet
        if (!$this->id && !$this->title) {
            $this->title = Assets::filename2Title(pathinfo($this->_filename, PATHINFO_FILENAME));
        }

        // Set the field layout
        $volume = Craft::$app->getAssets()->getFolderById($folderId)->getVolume();

        if (!$volume instanceof Temp) {
            $this->fieldLayoutId = $volume->fieldLayoutId;
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if the asset isn't new but doesn't have a row in the `assets` table for some reason
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            $isCpRequest = Craft::$app->getRequest()->getIsCpRequest();
            $sanitizeCpImageUploads = Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads;

            if (
                in_array($this->getScenario(), [self::SCENARIO_REPLACE, self::SCENARIO_CREATE], true) &&
                Assets::getFileKindByExtension($this->tempFilePath) === static::KIND_IMAGE &&
                !($isCpRequest && !$sanitizeCpImageUploads)
            ) {
                Image::cleanImageByPath($this->tempFilePath);
            }

            // Relocate the file?
            if (isset($this->newLocation) || isset($this->tempFilePath)) {
                $this->_relocateFile();
            }

            // Get the asset record
            if (!$isNew) {
                $record = AssetRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid asset ID: ' . $this->id);
                }
            } else {
                $record = new AssetRecord();
                $record->id = (int)$this->id;
            }

            $record->filename = $this->_filename;
            $record->volumeId = $this->getVolumeId();
            $record->folderId = (int)$this->folderId;
            $record->uploaderId = (int)$this->uploaderId ?: null;
            $record->kind = $this->kind;
            $record->size = (int)$this->size ?: null;
            $record->width = (int)$this->_width ?: null;
            $record->height = (int)$this->_height ?: null;
            $record->dateModified = $this->dateModified;

            if ($this->getHasFocalPoint()) {
                $focal = $this->getFocalPoint();
                $record->focalPoint = number_format($focal['x'], 4) . ';' . number_format($focal['y'], 4);
            } else {
                $record->focalPoint = null;
            }

            $record->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Update the asset record
        Db::update(Table::ASSETS, [
            'deletedWithVolume' => $this->deletedWithVolume,
            'keptFile' => $this->keepFileOnDelete,
        ], [
            'id' => $this->id,
        ], [], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        if (!$this->keepFileOnDelete) {
            $this->getVolume()->deleteFile($this->getPath());
        }

        Craft::$app->getImageTransforms()->deleteAllTransformData($this);
        parent::afterDelete();
    }

    /**
     * @inheritdoc
     */
    public function beforeRestore(): bool
    {
        // Only allow the asset to be restored if the file was kept on delete
        return $this->keptFile && parent::beforeRestore();
    }

    /**
     * @inheritdoc
     */
    protected function htmlAttributes(string $context): array
    {
        $attributes = [];

        if ($this->kind === self::KIND_IMAGE) {
            $attributes['data']['image-width'] = $this->getWidth();
            $attributes['data']['image-height'] = $this->getHeight();
        }

        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        $imageEditable = $context === 'index' && $this->getSupportsImageEditor();

        if ($volume instanceof Temp || $userSession->getId() == $this->uploaderId) {
            $attributes['data']['own-file'] = true;
            $movable = $replaceable = true;
        } else {
            $attributes['data']['peer-file'] = true;
            $movable = (
                $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
                $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
            );
            $replaceable = $userSession->checkPermission("replacePeerFilesInVolume:$volume->uid");
            $imageEditable = (
                $imageEditable &&
                ($userSession->checkPermission("editPeerImagesInVolume:$volume->uid"))
            );
        }

        if ($movable) {
            $attributes['data']['movable'] = true;
        }

        if ($replaceable) {
            $attributes['data']['replaceable'] = true;
        }

        if ($imageEditable) {
            $attributes['data']['editable-image'] = true;
        }

        return $attributes;
    }

    /**
     * Returns whether the current user can move/rename the asset.
     *
     * @return bool
     */
    private function _isMovable(): bool
    {
        $userSession = Craft::$app->getUser();
        if ($userSession->getId() == $this->uploaderId) {
            return true;
        }

        $volume = $this->getVolume();
        return (
            $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
            $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
        );
    }

    /**
     * Returns the width and height of the image.
     *
     * @param ImageTransform|string|array|null $transform
     * @return array
     */
    private function _dimensions($transform = null): array
    {
        if (!in_array($this->kind, [self::KIND_IMAGE, self::KIND_VIDEO], true)) {
            return [null, null];
        }

        if (!$this->_width || !$this->_height) {
            if ($this->getScenario() !== self::SCENARIO_CREATE) {
                Craft::warning("Asset $this->id is missing its width or height", __METHOD__);
            }

            return [null, null];
        }

        $transform = $transform ?? $this->_transform;

        if ($transform === null || !Image::canManipulateAsImage($this->getExtension())) {
            return [$this->_width, $this->_height];
        }

        $transform = ImageTransforms::normalizeTransform($transform);

        if ($this->_width < $transform->width && $this->_height < $transform->height && !Craft::$app->getConfig()->getGeneral()->upscaleImages) {
            $transformRatio = $transform->width / $transform->height;
            $imageRatio = $this->_width / $this->_height;

            if ($transform->mode !== 'crop' || $imageRatio === $transformRatio) {
                return [$this->_width, $this->_height];
            }

            return $transformRatio > 1 ? [$this->_width, round($this->_height / $transformRatio)] : [round($this->_width * $transformRatio), $this->_height];
        }

        [$width, $height] = Image::calculateMissingDimension($transform->width, $transform->height, $this->_width, $this->_height);

        // Special case for 'fit' since that's the only one whose dimensions vary from the transform dimensions
        if ($transform->mode === 'fit') {
            $factor = max($this->_width / $width, $this->_height / $height);
            $width = (int)round($this->_width / $factor);
            $height = (int)round($this->_height / $factor);
        }

        return [$width, $height];
    }

    /**
     * Relocates the file after the element has been saved.
     *
     * @throws VolumeException if a file operation errored
     * @throws Exception if something else goes wrong
     */
    private function _relocateFile(): void
    {
        $assetsService = Craft::$app->getAssets();

        // Get the (new?) folder ID & filename
        if (isset($this->newLocation)) {
            [$folderId, $filename] = Assets::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
            $filename = $this->_filename;
        }

        $hasNewFolder = $folderId != $this->folderId;

        $tempPath = null;

        $oldFolder = $this->folderId ? $assetsService->getFolderById($this->folderId) : null;
        $oldVolume = $oldFolder ? $oldFolder->getVolume() : null;

        $newFolder = $hasNewFolder ? $assetsService->getFolderById($folderId) : $oldFolder;
        $newVolume = $hasNewFolder ? $newFolder->getVolume() : $oldVolume;

        $oldPath = $this->folderId ? $this->getPath() : null;
        $newPath = ($newFolder->path ? rtrim($newFolder->path, '/') . '/' : '') . $filename;

        // Is this just a simple move/rename within the same volume?
        if (!isset($this->tempFilePath) && $oldFolder !== null && $oldFolder->volumeId == $newFolder->volumeId) {
            $oldVolume->renameFile($oldPath, $newPath);
        } else {
            if (!$this->_validateTempFilePath()) {
                Craft::warning("Prevented saving $this->tempFilePath as an asset. It must be located within a temp directory or the project root (excluding system directories).");
                $this->tempFilePath = null;
            }

            // Get the temp path
            if (isset($this->tempFilePath)) {
                $tempPath = $this->tempFilePath;
            } else {
                $tempFilename = uniqid(pathinfo($filename, PATHINFO_FILENAME), true) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
                Assets::downloadFile($oldVolume, $oldPath, $tempPath);
            }

            // Try to open a file stream
            if (($stream = fopen($tempPath, 'rb')) === false) {
                if (file_exists($tempPath)) {
                    FileHelper::unlink($tempPath);
                }
                throw new FileException(Craft::t('app', 'Could not open file for streaming at {path}', ['path' => $tempPath]));
            }

            if ($this->folderId) {
                // Delete the old file
                $oldVolume->deleteFile($oldPath);
            }

            $exception = null;

            // Upload the file to the new location
            try {
                $newVolume->writeFileFromStream($newPath, $stream, [
                    Volume::CONFIG_MIMETYPE => FileHelper::getMimeType($tempPath),
                ]);
            } catch (VolumeException $exception) {
                Craft::$app->getErrorHandler()->logException($exception);
            } finally {
                // If the volume has not already disconnected the stream, clean it up.
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            // Re-throw it, after we've made sure that the stream is disconnected.
            if ($exception !== null) {
                throw $exception;
            }
        }

        if ($this->folderId) {
            // Nuke the transforms
            Craft::$app->getImageTransforms()->deleteAllTransformData($this);
        }

        // Update file properties
        $this->setVolumeId($newFolder->volumeId);
        $this->folderId = $folderId;
        $this->folderPath = $newFolder->path;
        $this->_filename = $filename;
        $this->_volume = $newVolume;

        // If there was a new file involved, update file data.
        if ($tempPath && file_exists($tempPath)) {
            $this->kind = Assets::getFileKindByExtension($filename);

            if ($this->kind === self::KIND_IMAGE) {
                [$this->_width, $this->_height] = Image::imageSize($tempPath);
            } else {
                $this->_width = null;
                $this->_height = null;
            }

            $this->size = filesize($tempPath);
            $mtime = filemtime($tempPath);
            $this->dateModified = $mtime ? new DateTime('@' . $mtime) : null;

            // Delete the temp file
            FileHelper::unlink($tempPath);
        }

        // Clear out the temp location properties
        $this->newLocation = null;
        $this->tempFilePath = null;
    }

    /**
     * Validates that the temp file path exists and is someplace safe.
     *
     * @return bool
     */
    private function _validateTempFilePath(): bool
    {
        $tempFilePath = realpath($this->tempFilePath);

        if ($tempFilePath === false || !is_file($tempFilePath)) {
            return false;
        }

        $tempFilePath = FileHelper::normalizePath($tempFilePath);

        // Is it within one of our temp directories?
        $pathService = Craft::$app->getPath();
        $tempDirs = [
            $this->_normalizeTempPath($pathService->getTempPath()),
            $this->_normalizeTempPath(sys_get_temp_dir()),
        ];

        $tempDirs = array_filter($tempDirs, function($value) {
            return ($value !== false);
        });

        foreach ($tempDirs as $allowedFolder) {
            if (StringHelper::startsWith($tempFilePath, $allowedFolder)) {
                return true;
            }
        }

        // Make sure it's within the project root somewhere
        $projectRoot = $this->_normalizeTempPath(Craft::getAlias('@root'));
        if (!StringHelper::startsWith($tempFilePath, $projectRoot)) {
            return false;
        }

        // Make sure it's not within a system directory
        $systemDirs = $pathService->getSystemPaths();

        $systemDirs = array_map([$this, '_normalizeTempPath'], $systemDirs);
        $systemDirs = array_filter($systemDirs, function($value) {
            return ($value !== false);
        });

        foreach ($systemDirs as $dir) {
            if (StringHelper::startsWith($tempFilePath, $dir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a normalized temp path or false, if realpath fails.
     *
     * @param string $path
     * @return false|string
     */
    private function _normalizeTempPath(string $path)
    {
        $path = realpath($path);
        if (!$path) {
            return false;
        }

        return FileHelper::normalizePath($path) . DIRECTORY_SEPARATOR;
    }
}
