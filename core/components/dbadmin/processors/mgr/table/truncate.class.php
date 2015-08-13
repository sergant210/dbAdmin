<?php

/**
 * Truncate selected tables
 */
class dbAdminTablesTruncateProcessor extends modObjectProcessor {
	public $languageTopics = array('dbadmin');
	public $permission = 'table_truncate';


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
                $table = $this->modx->escape($table);
                $sql = "TRUNCATE TABLE {$table}";
                if ($stmt = $this->modx->prepare($sql)) {
                    $stmt->execute();
                }
            } catch (PDOException $e) {
                return $this->failure($e->getMessage());
            }
		}

		return $this->success();
	}

}

return 'dbAdminTablesTruncateProcessor';