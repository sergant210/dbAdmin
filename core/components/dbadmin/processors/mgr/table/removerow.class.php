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
     * The row data is only used to identify the record and is never merged
     * into the processor properties, so a table column named i.e. class or
     * package can't override the processor parameters.
     *
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize()
    {
        $row = $this->modx->fromJSON($this->getProperty('data', ''));
        if (!is_array($row) || empty($row)) {
            return $this->modx->lexicon('dbadmin.invalid_data');
        }
        $this->classKey = trim((string)$this->getProperty('class', ''));
        $package = strtolower(trim((string)$this->getProperty('package', '')));
        if (empty($this->classKey) || !$this->dbadmin->database->isValidPackageName($package)) {
            return $this->modx->lexicon('dbadmin.invalid_data');
        }
        $table = trim((string)$this->getProperty('table', ''));
        if (!$this->dbadmin->database->loadTableClass($this->classKey, $package, $table)) {
            return $this->modx->lexicon('dbadmin.class_err_table');
        }
        if (!$this->dbadmin->database->canChangeClassTable($this->classKey)) {
            return $this->modx->lexicon('dbadmin.table_err_core');
        }

        $this->primaryKeyFields = $this->modx->getPK($this->classKey);
        $primaryKeys = $this->dbadmin->database->getRowPrimaryKeys($this->classKey, $row);
        if ($primaryKeys === null) {
            return $this->modx->lexicon('dbadmin.row_err_ns');
        }
        $this->object = $this->modx->getObject($this->classKey, $primaryKeys);
        if (empty($this->object)) {
            return $this->modx->lexicon('dbadmin.row_err_nf');
        }

        return true;
    }
}

return 'dbAdminRemoveTableRowProcessor';
