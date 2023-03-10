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
     * {@inheritdoc}
     */
    public function afterRemove()
    {
        $table = $this->modx->escape($this->object->get('name'));
        $sql = 'DROP TABLE ' . $table;
        try {
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
            }
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdminTableRemoveProcessor');
        }
        return parent::afterRemove();
    }
}

return 'dbAdminTableRemoveProcessor';
