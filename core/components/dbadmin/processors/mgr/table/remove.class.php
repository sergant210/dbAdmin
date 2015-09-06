<?php

/**
 * Remove a table
 */
class dbAdminTableRemoveProcessor extends modObjectRemoveProcessor {
    public $objectType = 'dbadmin_table';
    public $classKey = 'dbAdminTable';
	public $languageTopics = array('dbadmin');
    public $primaryKeyField = 'name';
	public $permission = 'table_remove';

    /**
     * {@inheritdoc}
     */
    public function afterRemove() {
        try {
            // Удаляем из БД
            $table = $this->modx->escape($this->object->get('name'));
            $sql = "DROP TABLE ".$table;
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
            }

        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[dbAdmin] '.$e->getMessage());
        }
        return parent::afterRemove();
    }
}

return 'dbAdminTableRemoveProcessor';