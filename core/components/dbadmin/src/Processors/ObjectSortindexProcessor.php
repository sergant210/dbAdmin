<?php
/**
 * Abstract sortindex processor
 *
 * @package dbadmin
 * @subpackage processors
 */

namespace Sergant210\dbAdmin\Processors;

use Sergant210\dbAdmin\dbAdmin;
use modObjectProcessor;
use modX;
use xPDOObject;

/**
 * Class ObjectSortindexProcessor
 */
class ObjectSortindexProcessor extends modObjectProcessor
{
    public $languageTopics = ['dbadmin:default'];
    public $indexKey = 'sortindex';

    /** @var dbAdmin $dbadmin */
    public $dbadmin;

    /**
     * {@inheritDoc}
     * @param modX $modx A reference to the modX instance
     * @param array $properties An array of properties
     */
    public function __construct(modX &$modx, array $properties = [])
    {
        parent::__construct($modx, $properties);

        $corePath = $this->modx->getOption('dbadmin.core_path', null, $this->modx->getOption('core_path') . 'components/dbadmin/');
        $this->dbadmin = $this->modx->getService('dbadmin', dbAdmin::class, $corePath . 'model/dbadmin/');
    }

    /**
     * {@inheritDoc}
     * @return array|mixed|string
     */
    public function process()
    {
        if (!$this->cleanSorting()) {
            return $this->failure();
        }

        $targetId = $this->getProperty('targetId');
        $targetIndex = $this->modx->getObject($this->classKey, $targetId)->get($this->indexKey);

        // Prepare the moving ids
        $movingIds = explode(',', $this->getProperty('movingIds', 0));
        $c = $this->modx->newQuery($this->classKey);
        $c->where([
            'id:IN' => $movingIds
        ]);
        $c->sortby($this->indexKey);
        $c->sortby('id');
        /** @var xPDOObject[] $movingObjects */
        $movingObjects = $this->modx->getIterator($this->classKey, $c);
        foreach ($movingObjects as $movingObject) {
            $c = $this->modx->newQuery($this->classKey);
            $movingIndex = $movingObject->get($this->indexKey);
            if ($movingIndex < $targetIndex) {
                $c->where([
                    $this->indexKey . ':>' => $movingIndex,
                    $this->indexKey . ':<=' => $targetIndex,
                ]);
            } else {
                $c->where([
                    $this->indexKey . ':<' => $movingIndex,
                    $this->indexKey . ':>=' => $targetIndex,
                ]);
            }
            $c->sortby($this->indexKey);
            $c->sortby('id');
            /** @var xPDOObject[] $affectedObjects */
            $affectedObjects = $this->modx->getIterator($this->classKey, $c);
            foreach ($affectedObjects as $affectedObject) {
                $affectedIndex = $affectedObject->get($this->indexKey);
                if ($movingIndex < $targetIndex) {
                    $newIndex = $affectedIndex - 1;
                } else {
                    $newIndex = $affectedIndex + 1;
                }
                $affectedObject->set($this->indexKey, $newIndex);
                $affectedObject->save();
            }
            $movingObject->set($this->indexKey, $targetIndex);
            $movingObject->save();
        }

        return $this->success();
    }

    /**
     * Get a boolean property.
     * @param string $k
     * @param mixed $default
     * @return bool
     */
    public function getBooleanProperty($k, $default = null)
    {
        return ($this->getProperty($k, $default) === 'true' || $this->getProperty($k, $default) === true || $this->getProperty($k, $default) === '1' || $this->getProperty($k, $default) === 1);
    }

    /**
     * Clean the current sorting.
     *
     * @return bool
     */
    private function cleanSorting()
    {
        $c = $this->modx->newQuery($this->classKey);
        $c->sortby($this->indexKey);
        $c->sortby('id');

        /** @var xPDOObject[] $objects */
        $objects = $this->modx->getIterator($this->classKey, $c);
        if (!$objects) {
            return false;
        }

        $i = 0;
        foreach ($objects as $object) {
            $object->set($this->indexKey, $i);
            $object->save();
            $i++;
        }
        return true;
    }
}

return 'dbAdminCategorySortindexProcessor';
