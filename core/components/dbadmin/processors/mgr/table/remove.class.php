<?php

/**
 * Remove selected tables
 */
class dbAdminTableRemoveProcessor extends modObjectProcessor {
    public $objectType = 'dbadmin_table';
    public $classKey = 'dbAdminTable';
	public $languageTopics = array('dbadmin');
	public $permission = 'table_remove';


	/**
	 * @return array|string
	 */
	public function process() {
		if (!$this->checkPermissions()) {
			return $this->failure($this->modx->lexicon('access_denied'));
		}

        $tables = $this->getProperty('tables','');
        if (!empty($tables)) {
            $tables = array_map('trim',explode(',',$tables));
        } else {
            return $this->failure($this->modx->lexicon('dbadmin_table_err_ns'));
        }
		foreach ($tables as $table) {
            try {
                // Удаляем из таблицы карт
                /** @var dbAdminTable $object */
                if (!$object = $this->modx->getObject($this->classKey, $table)) {
                    return $this->failure($this->modx->lexicon('dbadmin_table_err_nf'));
                }
                $object->remove();
                // Удаляем из БД
                $table = $this->modx->escape($table);
                $sql = "DROP TABLE {$table}";
                if ($stmt = $this->modx->prepare($sql)) {
                    $stmt->execute();
                }

            } catch (Exception $e) {
                return $this->failure($e->getMessage());
            }
		}

		return $this->success();
	}

}

return 'dbAdminTableRemoveProcessor';