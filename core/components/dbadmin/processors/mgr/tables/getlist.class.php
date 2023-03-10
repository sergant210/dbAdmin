<?php
/**
 * List tables
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\ObjectGetListProcessor;

/**
 * Class dbAdminTableGetListProcessor
 */
class dbAdminTableGetListProcessor extends ObjectGetListProcessor
{
    public $objectType = 'dbadmin.table';
    public $classKey = 'dbAdminTable';
    public $defaultSortField = 'name';
    public $defaultSortDirection = 'ASC';
    public $permission = 'tables_list';
    public $tables = [];
    public $total = 0;

    protected $search = ['name', 'class'];

    /**
     * We're doing special check of permission because our object is not an
     * instances of modAccessibleObject
     *
     * @return boolean|string
     */
    public function beforeQuery()
    {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }
        $this->setDefaultProperties(['mustUpdate' => false]);
        return true;
    }

    /**
     * {@inheritDoc}
     * @param xPDOQuery $c
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $c = parent::prepareQueryBeforeCount($c);

        if ($this->dbadmin->database->needsUpdate()) {
            $this->dbadmin->database->synchronize();
        }
        $this->tables = $this->dbadmin->database->getTablesStatus();
        return $c;
    }

    /**
     * @param xPDOObject $object
     *
     * @return array
     */
    public function prepareRow(xPDOObject $object)
    {
        $row = $object->toArray();
        $row = array_merge($row, $this->tables[$row['name']]);
        $row['actions'] = [
            // View/Edit table data
            [
                'cls' => '',
                'icon' => ($row['class']) ? 'icon icon-pencil-square-o' : 'icon icon-eye',
                'title' => ($row['class']) ? $this->modx->lexicon('dbadmin.table_edit') : $this->modx->lexicon('dbadmin.table_view'),
                'action' => 'viewTable',
                'button' => true,
                'menu' => true,
            ],
            // Update table
            [
                'cls' => '',
                'icon' => 'icon icon-wrench',
                'title' => $this->modx->lexicon('dbadmin.table_properties'),
                'action' => 'updateTable',
                'button' => true,
                'menu' => true,
            ],
            // Export table data
            [
                'cls' => '',
                'icon' => 'icon icon-download',
                'title' => $this->modx->lexicon('dbadmin.table_export'),
                'action' => 'exportSelected',
                'button' => true,
                'menu' => true,
            ],
            // Truncate table
            [
                'cls' => '',
                'icon' => 'icon icon-eraser',
                'title' => $this->modx->lexicon('dbadmin.table_truncate'),
                'action' => 'truncateSelected',
                'button' => true,
                'menu' => true,
            ],
            // Select query table
            [
                'cls' => '',
                'icon' => 'icon icon-file-code-o ',
                'title' => 'Select from',
                'action' => 'selectQuery',
                'button' => true,
                'menu' => true,
            ],
            // Remove table
            [
                'cls' => '',
                'icon' => 'icon icon-trash-o action-red',
                'title' => $this->modx->lexicon('dbadmin.table_remove'),
                'action' => 'removeTable',
                'button' => true,
                'menu' => true,
            ]
        ];

        return $row;
    }
}

return 'dbAdminTableGetListProcessor';
