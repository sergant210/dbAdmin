<?php

/**
 * Update a Table
 */
class dbAdminTableUpdateProcessor extends modObjectProcessor {
    public $objectType = 'dbadmin_table';
    public $classKey = 'dbAdminTable';
    public $primaryKeyField = 'name';
    public $languageTopics = array('dbadmin');
    public $permission = 'table_save';

    /**
     * @return array|string
     */
    public function process() {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }
        $rename = true;
        $newName = trim($this->getProperty('name'));
        $oldName = trim($this->getProperty('oldName'));
        if (empty($oldName) || empty($newName)) {
            return $this->failure($this->modx->lexicon('dbadmin_table_err_ns'));
        } elseif ($newName==$oldName) {
            $rename = false;
        }

        if ($rename) {
            if ($this->modx->getCount($this->classKey,$newName)) return $this->failure($this->modx->lexicon('dbadmin_table_err_ae'));
            //1. Rename the table
            $query = $this->modx->newQuery($this->classKey);
            $query->command('update');
            $query->set(array(
                'name'  => $newName,
                'class'  => trim($this->getProperty('class')),
                'package'  => trim($this->getProperty('package')),
            ));
            $query->where(array(
                'name'    => $oldName,
            ));
            $query->prepare();
            if (!$query->stmt->execute()) return $this->failure($this->modx->lexicon('dbadmin_table_err_save'));
            unset($query);
            //2. Rename the system table
            try {
                $newName = $this->modx->escape($newName);
                $oldName = $this->modx->escape($oldName);
                $query = "RENAME TABLE {$oldName} TO {$newName}";
                if ($stmt = $this->modx->prepare($query)) {
                    if (!$stmt->execute()) {
                        throw new PDOException($this->modx->lexicon('dbadmin_table_err_rename'));
                    }
                }
            } catch (PDOException $e) {
                return $this->failure($e->getMessage());
            }
        } else {
            $query = $this->modx->newQuery($this->classKey);
            $query->command('update');
            $query->set(array(
                'class'  => trim($this->getProperty('class')),
                'package'  => trim($this->getProperty('package')),
            ));
            $query->where(array(
                'name'    => $oldName,
            ));
            $query->prepare();
            if (!$query->stmt->execute()) return $this->failure($this->modx->lexicon('dbadmin_table_err_save'));

        }

        return $this->success();
    }
}

return 'dbAdminTableUpdateProcessor';
