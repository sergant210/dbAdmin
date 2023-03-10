<?php
/**
 * Remove a record from the table
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\ObjectRemoveProcessor;

/**
 * Class dbAdminRemoveTableRowProcessor
 */
class dbAdminRemoveTableRowProcessor extends ObjectRemoveProcessor
{
    public $objectType = 'dbadmin.table';
    public $classKey = '';
    public $primaryKeyFields = '';
    public $permission = 'table_save';

    /**
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize()
    {
        $data = $this->getProperty('data');
        if (empty($data)) {
            return $this->modx->lexicon('dbadmin.invalid_data');
        }
        $properties = $this->modx->fromJSON($data);
        $this->setProperties($properties);
        $this->unsetProperty('data');
        $this->classKey = trim($this->getProperty('class'));
        $package = strtolower(trim($this->getProperty('package', '')));
        if (empty($this->classKey) || empty($package)) {
            return $this->modx->lexicon('dbadmin.invalid_data');
        }

        $path = MODX_CORE_PATH . 'components/' . $package . '/model/';

        if (!preg_match('/^modx/', $package)) {
            if (is_dir($path)) {
                if (!$this->modx->addPackage($package, $path)) {
                    $this->classKey = '';
                    $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('dbadmin.err_path'));
                }
            } else {
                $this->classKey = '';
            }
        }
        if (!$this->classKey) {
            return false;
        }

        $this->primaryKeyFields = $this->modx->getPK($this->classKey);
        if (is_array($this->primaryKeyFields)) {
            $primaryKeys = [];
            foreach ($this->primaryKeyFields as $key) {
                $primaryKeys[$key] = $this->getProperty($key);
            }
        } else {
            $primaryKeys = $this->getProperty($this->primaryKeyFields, false);
        }
        if (empty($primaryKeys)) {
            return $this->modx->lexicon($this->objectType . '_err_ns');
        }
        $this->object = $this->modx->getObject($this->classKey, $primaryKeys);
        if (empty($this->object)) {
            return $this->modx->lexicon($this->objectType . '_err_nf');
        }

        return true;
    }
}

return 'dbAdminRemoveTableRowProcessor';
