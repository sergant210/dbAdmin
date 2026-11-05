<?php
/**
 * Update a record from the table
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\ObjectUpdateProcessor;

/**
 * Class dbAdminUpdateFromGridProcessor
 */
class dbAdminUpdateFromGridProcessor extends ObjectUpdateProcessor
{
    public $objectType = 'dbadmin.table';
    public $classKey = '';
    public $primaryKeyFields = '';
    public $permission = 'table_save';

    /** @var array|null The current primary key and the new value, if a primary key field is changed */
    protected $primaryKeyChange = null;

    /**
     * Only the edited field is saved. The row data is used to identify the
     * record and is never merged into the processor properties, so the other
     * fields of the record and the processor parameters stay untouched.
     *
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize()
    {
        $row = $this->modx->fromJSON($this->getProperty('data', ''));
        $field = (string)$this->getProperty('field', '');
        if (!is_array($row) || $field === '' || !array_key_exists($field, $row)) {
            return $this->modx->lexicon('dbadmin.invalid_data');
        }
        $value = $this->getProperty('value');
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

        if (!array_key_exists($field, $this->modx->getFields($this->classKey))) {
            return $this->modx->lexicon('dbadmin.invalid_data');
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

        if (in_array($field, (array)$this->primaryKeyFields, true)) {
            if ($value === null || $value === '') {
                return $this->modx->lexicon('dbadmin.row_err_pk_empty');
            }
            $this->primaryKeyChange = [
                'criteria' => $primaryKeys,
                'field' => $field,
                'value' => $value,
            ];
        }

        // Replace (not merge) the properties, so only the edited field is set in process()
        $this->properties = [$field => $value];

        return true;
    }

    /**
     * {@inheritDoc}
     * @return array|string
     */
    public function process()
    {
        if ($this->primaryKeyChange === null) {
            return parent::process();
        }
        return $this->changePrimaryKey();
    }

    /**
     * Change a primary key field with an UPDATE query. xPDOObject::save() identifies the
     * record by its current primary key, so it can't change the primary key itself.
     * The references to the record in other tables are not changed.
     * @return array|string
     */
    protected function changePrimaryKey()
    {
        $criteria = $this->primaryKeyChange['criteria'];
        $field = $this->primaryKeyChange['field'];
        $value = $this->primaryKeyChange['value'];
        if ((string)$criteria[$field] === (string)$value) {
            return $this->success('');
        }
        $newKeys = array_merge($criteria, [$field => $value]);
        if ($this->modx->getCount($this->classKey, $newKeys)) {
            return $this->failure($this->modx->lexicon('dbadmin.row_err_pk_ae'));
        }
        try {
            $result = $this->modx->updateCollection($this->classKey, [$field => $value], $criteria);
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdminUpdateFromGridProcessor');
            $result = false;
        }
        if ($result === false) {
            return $this->failure($this->modx->lexicon('dbadmin.row_err_pk_save'));
        }
        $this->logManagerAction();
        return $this->success('');
    }
}

return 'dbAdminUpdateFromGridProcessor';
