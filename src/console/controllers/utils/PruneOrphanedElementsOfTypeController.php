<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
use craft\helpers\Db;
use yii\console\ExitCode;

/**
 * Prunes orphaned elements of a given type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.13.10
 */
class PruneOrphanedElementsOfTypeController extends Controller
{
    /**
     * @var string|null The type of element to prune the elements for
     */
    public ?string $type = null;

    /**
     * @var bool Whether to only do a dry run of the prune elements of type process.
     */
    public bool $dryRun = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'type';
        $options[] = 'dryRun';
        return $options;
    }

    /**
     * Delete elements that belong a type that no longer exists.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        if ($this->type === null) {
            // get all types that aren't native to Craft
            $types = (new Query())
                ->select(['type'])
                ->distinct()
                ->from(Table::ELEMENTS)
                ->where(['not like', 'type', 'craft\\elements\\'])
                ->column();
        } else {
            // check if the type isn't native to Craft - if it is, show message and exit
            if (str_starts_with($this->type, 'craft\\elements\\')) {
                $this->stdout("Provided element type cannot be a native Craft type.\n", Console::FG_YELLOW);
                return ExitCode::OK;
            }

            $types = [$this->type];
        }

        // for each type, check if the class still exists
        foreach ($types as $type) {
            // if the type's class doesn't exist (e.g. uninstalled via composer)
            if (!class_exists($type)) {
                // get the elements of that type
                $elements = (new Query())
                    ->select(['id', 'type'])
                    ->from(Table::ELEMENTS)
                    ->where(['type' => $type])
                    ->all();

                if (empty($elements)) {
                    $this->stdout("No `$type` elements found\n", Console::FG_GREEN);
                } else {
                    $this->stdout(sprintf("%s `$type` elements found\n", count($elements)), Console::FG_GREEN);
                }

                foreach ($elements as $element) {
                    $this->stdout("Deleting {$element['id']} ...", Console::FG_GREEN);
                    if (!$this->dryRun) {
                        Db::delete(Table::ELEMENTS, [
                            'id' => $element['id'],
                        ]);
                        Db::delete(Table::SEARCHINDEX, [
                            'elementId' => $element['id'],
                        ]);
                    }
                    $this->stdout(" done\n", Console::FG_RED);
                }
            }
        }

        $this->stdout("\nFinished pruning orphaned elements of type.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
