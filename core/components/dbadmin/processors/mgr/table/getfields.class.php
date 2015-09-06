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
        $table = trim($this->getProperty('table',''));
        $forSelect = $this->getProperty('forselect',false);
        if (empty($table)) return $this->failure($this->modx->lexicon('dbadmin_table_err_nf'));
        $query = "SHOW COLUMNS FROM ".$table;
        $result = $this->modx->query($query);
        if (!is_object($result)) return $this->failure($this->modx->lexicon('dbadmin_sql_executed_failed'));
        $data = $result->fetchAll(PDO::FETCH_ASSOC);
        if ($forSelect) {
            $fields = '';
            foreach ($data as &$field) {
                if (!empty($fields)) $fields .= ',';
                $fields .= $this->modx->escape($field['Field']);
            }
        } else {
            $fields = array();
            foreach ($data as &$field) {
                $fields[] = array('name' => $field['Field'], 'type' => $this->getFieldType($field['Type']));
            }
            $fields[] = array('name'=>'actions','type'=>'actions');
        }
        return $this->success('',$fields);

    }

    /**
     * @param $type
     * @return string
     */
    public function getFieldType($type){
        if (preg_match('/(blob|text|enum|set)/i',$type)) {
            $type = 'string';
        } elseif (preg_match('/(int|float|double|decimal|dec|bool)/i',$type)) {
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