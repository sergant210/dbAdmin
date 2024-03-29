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
        foreach (explode(',', $tables) as $table) {
            try {
                $table = $this->modx->escape($table);
                $sql = 'TRUNCATE TABLE ' . $table;
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
