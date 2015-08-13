<?php

/**
 * Synchronizes the tables list with a list of database tables
 */
class dbAdminSyncTablesProcessor extends modObjectProcessor {
	public $languageTopics = array('dbadmin');
	public $permission = 'table_list';

    /**
     * @return array|string
     */
    public function process() {
        $corePath = $this->modx->getOption('dbadmin_core_path', null, $this->modx->getOption('core_path') . 'components/dbadmin/');
        require_once $corePath . 'model/dbadmin/dbadmin.class.php';
        /** @var dbAdmin $dbAdmin */
        $dbAdmin = new dbAdmin($this->modx);
        $tablesList = $dbAdmin->getTables();
        $dbTablesList = $dbAdmin->getDbTables();
        $tables = array_keys($tablesList);
        $dbTables = array_keys($dbTablesList);
        // Удаляем лишние таблицы
        $diff = array_diff($tables,$dbTables);
        if (!empty($diff)) {
            $query = $this->modx->newQuery('dbAdminTable');
            $query->command('delete');
            $query->where(array(
                'name:IN' => $diff,
            ));
            $query->prepare();
            if (!$query->stmt->execute()) {
                return $this->failure($this->modx->lexicon('dbadmin_sync'));
            }
        }
        // Добавляем новые таблицы
        $diff = array_diff($dbTables,$tables);
        foreach ($diff as $table) {
            $obj = $this->modx->newObject('dbAdminTable');
            $obj->set('name',$table);
            $obj->save();
        }

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
}

return 'dbAdminSyncTablesProcessor';