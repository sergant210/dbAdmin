<?php

/**
 * Rename the table
 */
class dbAdminTableRenameProcessor extends modObjectProcessor {
	public $languageTopics = array('dbadmin');
	public $permission = 'table_rename';

    /**
     * @return array|string
     */
    public function process() {
        /*if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }*/

        $newName = trim($this->getProperty('new',''));
        $oldName = trim($this->getProperty('old',''));
        if (empty($oldName) || empty($newName)) {
            return $this->failure($this->modx->lexicon('dbadmin_table_err_ns'));
        } elseif ($newName==$oldName) {
            return $this->success();
        }
        $sql = "SHOW TABLES LIKE '".$newName."'";
        if ($res = $this->modx->query($sql)) {
            $result = $res->fetchAll(PDO::FETCH_ASSOC);
        } else {
           return $this->failure($this->modx->lexicon('dbadmin_table_err_rename'));
        }
        if (!empty($result)) return $this->failure($this->modx->lexicon('dbadmin_table_err_ae'));
        try {
            $newName = $this->modx->escape($newName);
            $oldName = $this->modx->escape($oldName);
            $query = "RENAME TABLE {$oldName} TO {$newName}";
            if ($stmt = $this->modx->prepare($query)) {
                if (!$stmt->execute()) throw new PDOException($this->modx->lexicon('dbadmin_table_err_rename'));
            }
        } catch (PDOException $e) {
            return $this->failure($e->getMessage());
        }

        return $this->success();
    }
}

return 'dbAdminTableRenameProcessor';
