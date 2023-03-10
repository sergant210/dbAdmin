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

    /**
     * @return mixed
     */
    public function process()
    {
        $table = $this->getProperty('table', '');
        $forSelect = $this->getProperty('forselect', false);
        if (empty($table)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_nf'));
        }
        $table = $this->modx->escape($table);
        $sql = 'SHOW COLUMNS FROM ' . $table;
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
                $fields[] = $this->modx->escape($field['Field']);
            }
            $fields = implode(',', $fields);
        } else {
            $fields = [];
            foreach ($data as $field) {
                $fields[] = [
                    'name' => $field['Field'],
                    'type' => $this->getFieldType($field['Type'])
                ];
            }
            $fields[] = [
                'name' => 'actions',
                'type' => 'actions'
            ];
        }
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
            'fields' => $fields
        ];
        return $this->modx->toJSON($output);
    }
}

return 'dbAdminTableFieldsGetProcessor';
