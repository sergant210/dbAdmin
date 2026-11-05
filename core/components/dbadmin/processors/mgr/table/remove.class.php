<?php
/**
 * Remove table
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\ObjectRemoveProcessor;

/**
 * Class dbAdminTableRemoveProcessor
 */
class dbAdminTableRemoveProcessor extends ObjectRemoveProcessor
{
    public $objectType = 'dbadmin.table';
    public $classKey = 'dbAdminTable';
    public $primaryKeyField = 'name';
    public $permission = 'table_remove';

    /**
     * Drop the database table before the dbAdmin record is removed, so that
     * the record is kept and an error is shown, if the table can't be dropped.
     *
     * {@inheritdoc}
     * @return bool|string
     */
    public function beforeRemove()
    {
        if (!$this->dbadmin->database->canChangeTable($this->object->get('name'))) {
            return $this->modx->lexicon('dbadmin.table_err_core');
        }
        $name = (string)$this->object->get('name');
        // DROP TABLE only warns for a view and keeps it
        $type = $this->dbadmin->database->isView($name) ? 'VIEW' : 'TABLE';
        $sql = 'DROP ' . $type . ' IF EXISTS ' . $this->dbadmin->database->quoteIdentifier($name);
        $errMode = $this->modx->pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $this->modx->pdo->exec($sql);
        } catch (PDOException $e) {
            $message = (is_array($e->errorInfo) && !empty($e->errorInfo[2])) ? $e->errorInfo[2] : $e->getMessage();
            $this->modx->log(modX::LOG_LEVEL_ERROR, $message, '', 'dbAdminTableRemoveProcessor');
            return $this->modx->lexicon('dbadmin.table_err_remove', ['message' => $message]);
        } finally {
            $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, $errMode);
        }
        return parent::beforeRemove();
    }
}

return 'dbAdminTableRemoveProcessor';
