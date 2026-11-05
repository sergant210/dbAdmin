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
        $newName = trim((string)$this->getProperty('name'));
        $oldName = trim((string)$this->getProperty('oldName'));
        $data = [
            'class' => trim((string)$this->getProperty('class')),
            'package' => trim((string)$this->getProperty('package')),
        ];
        if ($oldName === '' || $newName === '') {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_ns'));
        } elseif (!$this->dbadmin->database->tableExists($oldName)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_nf'));
        } elseif ($data['package'] !== '' && !$this->dbadmin->database->isValidPackageName($data['package'])) {
            return $this->failure($this->modx->lexicon('dbadmin.package_err_name'));
        }
        $rename = ($newName !== $oldName);
        if ($data['class'] !== '' && !$this->dbadmin->database->loadTableClass($data['class'], strtolower($data['package']), $newName)) {
            if ($rename && $this->dbadmin->database->loadTableClass($data['class'], strtolower($data['package']), $oldName)) {
                // The class belongs to the old table name and can't be used for the renamed table
                $data['class'] = '';
                $data['package'] = '';
            } else {
                return $this->failure($this->modx->lexicon('dbadmin.class_err_invalid'));
            }
        }
        if ($rename) {
            if (!$this->dbadmin->database->canChangeTable($oldName)) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_core'));
            }
            if (!$this->dbadmin->database->isValidTableName($newName)) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_name'));
            }
            if ($this->dbadmin->database->tableExists($newName)) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_ae'));
            }
            // Rename the database table first, the dbAdmin record is only changed after a successful rename
            $error = $this->renameTable($oldName, $newName);
            if ($error !== '') {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_rename', ['message' => $error]));
            }
            // The name is the primary key of the record, fromArray/save can't change it
            $this->modx->removeCollection($this->classKey, ['name' => $newName]);
            if ($this->modx->getCount($this->classKey, ['name' => $oldName])) {
                $saved = $this->modx->updateCollection($this->classKey, array_merge(['name' => $newName], $data), ['name' => $oldName]) !== false;
            } else {
                $saved = $this->createRecord($newName, $data);
            }
            if (!$saved) {
                // Restore the old name, so that the database and the dbAdmin record stay consistent
                $this->renameTable($newName, $oldName);
                return $this->failure($this->modx->lexicon('dbadmin.table_err_save'));
            }
        } else {
            /** @var dbAdminTable $table */
            $table = $this->modx->getObject($this->classKey, ['name' => $oldName]);
            if ($table) {
                $table->fromArray($data);
                $saved = $table->save();
            } else {
                $saved = $this->createRecord($oldName, $data);
            }
            if (!$saved) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_save'));
            }
        }

        return $this->success();
    }

    /**
     * Rename a database table.
     * @param string $from
     * @param string $to
     * @return string The MySQL error message or an empty string on success
     */
    protected function renameTable(string $from, string $to): string
    {
        $database = $this->dbadmin->database;
        $sql = 'RENAME TABLE ' . $database->quoteIdentifier($from) . ' TO ' . $database->quoteIdentifier($to);
        $errMode = $this->modx->pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $this->modx->pdo->exec($sql);
        } catch (PDOException $e) {
            $message = (is_array($e->errorInfo) && !empty($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage();
            $this->modx->log(modX::LOG_LEVEL_ERROR, $message, '', 'dbAdminTableUpdateProcessor');
            return $message;
        } finally {
            $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, $errMode);
        }
        return '';
    }

    /**
     * Create a missing dbAdmin record for a table.
     * @param string $name
     * @param array $data
     * @return bool
     */
    protected function createRecord(string $name, array $data): bool
    {
        /** @var dbAdminTable $table */
        $table = $this->modx->newObject($this->classKey);
        $table->fromArray(array_merge(['name' => $name], $data), '', true);
        return $table->save();
    }
}

return 'dbAdminTableUpdateProcessor';
