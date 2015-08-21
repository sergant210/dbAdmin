<?php

/**
 * Export selected tables
 */
class dbAdminExportTablesProcessor extends modObjectProcessor {
	public $languageTopics = array('dbadmin');
	public $permission = 'table_export';

    /**
     * @return array|string
     */
    public function process() {
        $tables = $this->getProperty('tables','');
        if ($this->getProperty('export_db') == 'true') {
            $tables = $this->getTables();
        } elseif (!empty($tables)) {
            $tables = array_map('trim', explode(',', $tables));
            sort($tables);
        } else {
            return $this->failure($this->modx->lexicon('dbadmin_table_err_ns'));
        }
        $path = $this->modx->getOption('dbadmin_assets_path', NULL, $this->modx->getOption('assets_path') . 'components/dbadmin/').'export/';
        if (!is_dir($path) && !mkdir($path,0755)) return $this->failure($this->modx->lexicon('dbadmin_table_err_path'));
        $sql = "-- ".$this->modx->lexicon('createdon').date('j M Y, H:i')."\n\n";
        foreach ($tables as $table) {
            $sql .= $this->prepareTableCreateSql($table);
            $sql .= $this->getTableData($table);
            $sql .= "\n\n-- --------------------------------------------------------\n\n";
        }
        if (!empty($tables)) file_put_contents($path.'db_backup.sql', $sql );

        return $this->success();
    }

    /**
     * Get table list
     * @return array
     */
    protected function getTables() {
        $tables = array();
        $q = "SHOW TABLE STATUS";
        $result = $this->modx->query($q);
        if (is_object($result)) {
            while($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[]= $row[0];
            }
        }
        return $tables;
    }
    /**
     * Prepare CREATE TABLE sql
     * @param $table
     * @return string
     */
    protected function prepareTableCreateSql($table){
        $sql = "--\n-- ".$this->modx->lexicon('table_structure')." `{$table}`\n--\n\n";
        $result = $this->modx->query('SHOW CREATE TABLE '.$table);
        if (is_object($result)) {
            $tables = $result->fetch(PDO::FETCH_ASSOC);
            $sql .= "DROP TABLE IF EXISTS `".$table."`;\n";
            $sql .= $tables['Create Table'];
            $sql .= ";\n";
        }
        return $sql;
    }

    /**
     * Prepare INSERT INTO sql
     * @param $table
     * @return string
     */
    protected function getTableData($table){
        // Получаем список полей
        $fields = $this->getFields($table);
        if ($fields === false) return '';
        $_fieldList = '';
        foreach ($fields as &$field) {
            if (!empty($_fieldList)) {
                $_fieldList .= ", ";
            }
            $_fieldList .= $this->modx->escape($field['name']);
        }
        // Формируем sql
        $result = $this->modx->query('SELECT '.$_fieldList.' FROM '.$table);
        if (!is_object($result) || !$rows = $result->fetchAll(PDO::FETCH_NUM)) return '';
        $num_rows = count($rows);
        $num_cols = count($rows[0]);
        $sql = "\n--\n-- ".$this->modx->lexicon('table_dump')." `{$table}`\n--\n\n";

        $sql .= 'INSERT INTO `'. $table ."` (".$_fieldList;
        // Формируем данные для вывода
        $sql .= ") VALUES \n";
        for ($i = 0; $i < $num_rows; ++$i) {
            $sql .= "(";
            for ($j = 0; $j < $num_cols; ++$j) {
                if ( is_null($rows[$i][$j]) ) {
                    $sql .= 'NULL';
                } elseif ($fields[$j]['type'] == 'string') {
                    $val = '\''.addslashes($rows[$i][$j]).'\'';
                    $sql .= strtr(
                        $val,
                        array("\n" => '\n', "\r" => '\r', "\t" => '\t')
                    );
                } else {
                    $sql .= $rows[$i][$j];
                }
                if ($j != $num_cols-1) $sql .= ", ";
            }
            $sql .= ")";
            if ($i != ($num_rows - 1)) {
                $sql .= ",\n";
            }
        }
        $sql .= ";";
        return $sql;
    }

    /**
     * Get array of fields
     * @param $table
     * @return array|bool
     */
    protected function getFields($table){
        $query = "SHOW COLUMNS FROM ".$table;
        $result = $this->modx->query($query);
        if (!is_object($result)) return false;
        $fields = array();
        $i = 0;
        $data = $result->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as &$field) {
            $fields[$i]['name'] = $field['Field'];
            foreach (array('int','decimal','float','double','real') as $type) {
                if (stripos($field['Type'],$type) !== false){
                    $fields[$i]['type'] = 'number';
                    break;
                } else {
                    $fields[$i]['type'] = 'string';
                }
            }
            $i++;
        }
        return $fields;
    }
}

return 'dbAdminExportTablesProcessor';