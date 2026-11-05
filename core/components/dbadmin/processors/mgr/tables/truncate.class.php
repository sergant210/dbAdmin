<?php
/**
 * Truncate selected tables
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\Processor;

/**
 * Class dbAdminTablesTruncateProcessor
 */
class dbAdminTablesTruncateProcessor extends Processor
{
    public $objectType = 'dbadmin.table';
    public $permission = 'table_truncate';


    /**
     * @return array|string
     */
    public function process()
    {
        $tables = $this->getProperty('tables', '');
        if (empty($tables)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_ns'));
        }
        $tables = array_map('trim', explode(',', $tables));
        foreach ($tables as $table) {
            if (!$this->dbadmin->database->tableExists($table)) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_nf'));
            }
            if (!$this->dbadmin->database->canChangeTable($table)) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_core'));
            }
            if ($this->dbadmin->database->isView($table)) {
                return $this->failure($this->modx->lexicon('dbadmin.table_err_truncate_view', ['table' => $table]));
            }
        }
        // The MODX connection doesn't throw exceptions, a failed TRUNCATE would be reported as success
        $errMode = $this->modx->pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $truncated = [];
        try {
            foreach ($tables as $table) {
                $this->modx->pdo->exec('TRUNCATE TABLE ' . $this->dbadmin->database->quoteIdentifier($table));
                $truncated[] = $table;
            }
        } catch (PDOException $e) {
            $message = (is_array($e->errorInfo) && !empty($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage();
            $this->modx->log(modX::LOG_LEVEL_ERROR, $message, '', 'dbAdminTablesTruncateProcessor');
            $error = $this->modx->lexicon('dbadmin.table_err_truncate', ['table' => $table, 'message' => $message]);
            if ($truncated) {
                $error .= ' ' . $this->modx->lexicon('dbadmin.table_truncated', ['tables' => implode(', ', $truncated)]);
            }
            return $this->failure($error);
        } finally {
            $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, $errMode);
        }

        return $this->success();
    }
}

return 'dbAdminTablesTruncateProcessor';
