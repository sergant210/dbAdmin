<?php
/**
 * Update a table
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\Processor;

/**
 * Class dbAdminTableUpdateProcessor
 */
class dbAdminTableUpdateProcessor extends Processor
{
    public $objectType = 'dbadmin.table';
    public $classKey = 'dbAdminTable';
    public $primaryKeyField = 'name';
    public $permission = 'table_save';

    /**
     * @return array|string
     */
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }
        $rename = true;
        $newName = trim($this->getProperty('name'));
        $oldName = trim($this->getProperty('oldName'));
        if (empty($oldName) || empty($newName)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_ns'));
        } elseif ($newName == $oldName) {
            $rename = false;
        }

        if ($rename) {
            if ($this->modx->getCount($this->classKey, $newName)) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_ae'));
            }
            // Rename the dbAdmin table
            /** @var dbAdminTable $table */
            $table = $this->modx->getObject($this->classKey, [
                'name' => $oldName
            ]);
            $table->fromArray([
                'name' => $newName,
                'class' => trim($this->getProperty('class')),
                'package' => trim($this->getProperty('package'))
            ]);
            if (!$table->save()) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_save'));
            }
            // Rename the system table
            $query = new xPDOCriteria($this->modx, 'RENAME TABLE ' . $this->modx->escape($oldName) . ' TO ' . $this->modx->escape($newName));
            $stmt = $query->prepare();
            if ($stmt && !$stmt->execute()) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_rename'));
            }
        } else {
            /** @var dbAdminTable $table */
            $table = $this->modx->getObject($this->classKey, [
                'name' => $oldName
            ]);
            $table->fromArray([
                'class' => trim($this->getProperty('class')),
                'package' => trim($this->getProperty('package'))
            ]);
            if (!$table->save()) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_save'));
            }
        }

        return $this->success();
    }
}

return 'dbAdminTableUpdateProcessor';
