<?php

/**
 * Get a table fields (to view the table data)
 */
class dbAdminTableFieldsGetProcessor extends modObjectProcessor {
    public $languageTopics = array('dbadmin');
    public $permission = 'table_view';
    /**
     * @return mixed
     */
    public function process() {
        $table =trim($this->getProperty('table',''));
        if (empty($table)) return $this->failure($this->modx->lexicon('dbadmin_table_err_nf'));
        $query = "SHOW COLUMNS FROM ".$table;
        $result = $this->modx->query($query);
        if (!is_object($result)) return $this->failure($this->modx->lexicon('dbadmin_sql_executed_failed'));
        $fields = array();
        $data = $result->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as &$field) {
            $fields['name'][] = $field['Field'];
            $fields['type'][] = $this->getFieldType($field['Type']);
        }
        return $this->success('',$fields);

    }

    /**
     * @param $type
     * @return string
     */
    public function getFieldType($type){
        if (preg_match('/(blob|text|enum|set)/i',$type)) {
            $type = 'text';
        } elseif (preg_match('/(int|float|double|decimal|dec|bool)/i',$type)) {
            $type = 'num';
        } else {
            $type = 'string';
        }
        return $type;
    }
    /**
     * @param string $msg
     * @param null $fields
     * @return array|string
     * @internal param null $object
     */
    public function success($msg = '',$fields = null) {
        $output  = array(
            'success' => true,
            'message' => $msg,
            'fields'  => $fields
        );
        return $this->modx->toJSON($output);
    }
}

return 'dbAdminTableFieldsGetProcessor';