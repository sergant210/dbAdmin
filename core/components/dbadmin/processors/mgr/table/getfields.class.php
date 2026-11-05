<?php
/**
 * Get table fields (to view the table data)
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\Processor;

/**
 * Class dbAdminTableFieldsGetProcessor
 */
class dbAdminTableFieldsGetProcessor extends Processor
{
    public $permission = 'table_view';

    /** @var bool If the class of the table could be loaded and belongs to the table */
    protected $classLoaded = false;

    /**
     * @return mixed
     */
    public function process()
    {
        $table = $this->getProperty('table', '');
        $forSelect = $this->getProperty('forselect', false);
        $classLoaded = false;
        if (!$this->dbadmin->database->tableExists($table)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_nf'));
        }
        $sql = 'SHOW COLUMNS FROM ' . $this->dbadmin->database->quoteIdentifier($table);
        $data = [];
        try {
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return $this->failure($this->modx->lexicon('dbadmin.sql_executed_failed'));
        }

        if ($forSelect) {
            $fields = [];
            foreach ($data as $field) {
                $fields[] = $this->dbadmin->database->quoteIdentifier($field['Field']);
            }
            $fields = implode(',', $fields);
        } else {
            // The primary key of the table and of the class, the record is saved with the class
            $classKey = trim((string)$this->getProperty('class', ''));
            $package = strtolower(trim((string)$this->getProperty('package', '')));
            $classPrimaryKeys = [];
            // The records can only be edited with a class, that belongs to the table
            $classLoaded = $this->dbadmin->database->loadTableClass($classKey, $package, (string)$table);
            if ($classLoaded) {
                $classPrimaryKeys = (array)$this->modx->getPK($classKey);
            }
            $fields = [];
            foreach ($data as $field) {
                $fields[] = [
                    'name' => $field['Field'],
                    'type' => $this->getFieldType($field['Type']),
                    'primary' => ($field['Key'] === 'PRI' || in_array($field['Field'], $classPrimaryKeys, true))
                ];
            }
            $fields[] = [
                'name' => 'actions',
                'type' => 'actions'
            ];
        }
        $this->classLoaded = $classLoaded;
        return $this->success('', $fields);

    }

    /**
     * @param $type
     * @return string
     */
    public function getFieldType($type)
    {
        if (preg_match('/(blob|text|enum|set)/i', $type)) {
            $type = 'string';
        } elseif (preg_match('/(int|float|double|decimal|dec|bool)/i', $type)) {
            $type = 'number';
        } else {
            $type = 'auto';
        }
        return $type;
    }

    /**
     * @param string $msg
     * @param null $fields
     * @return array|string
     * @internal param null $object
     */
    public function success($msg = '', $fields = null)
    {
        $output = [
            'success' => true,
            'message' => $msg,
            'fields' => $fields,
            'classLoaded' => $this->classLoaded
        ];
        return $this->modx->toJSON($output);
    }
}

return 'dbAdminTableFieldsGetProcessor';
