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

    /** @var array|null */
    protected $actionPermissions = null;

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
        // A table dropped by another session after the synchronization has no status
        $row = array_merge($row, $this->tables[$row['name']] ?? [
            'type' => '',
            'view' => false,
            'rows' => 0,
            'collation' => '',
            'size' => 0,
        ]);
        // MODX core tables can only be changed with the table_core_edit permission
        $row['editable'] = $this->dbadmin->database->canChangeTable($row['name']);
        $editIcon = ($row['class'] && $row['editable'] && $this->modx->hasPermission('table_save'));
        $row['actions'] = [
            // View/Edit table data
            [
                'cls' => '',
                'icon' => $editIcon ? 'icon icon-pencil-square-o' : 'icon icon-eye',
                'title' => $editIcon ? $this->modx->lexicon('dbadmin.table_edit') : $this->modx->lexicon('dbadmin.table_view'),
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
                'multiple' => $this->modx->lexicon('dbadmin.selected_export'),
            ],
            // Truncate table
            [
                'cls' => '',
                'icon' => 'icon icon-eraser',
                'title' => $this->modx->lexicon('dbadmin.table_truncate'),
                'action' => 'truncateSelected',
                'button' => true,
                'menu' => true,
                'multiple' => $this->modx->lexicon('dbadmin.selected_truncate'),
            ],
            // Select query table
            [
                'cls' => '',
                'icon' => 'icon icon-file-code-o ',
                'title' => $this->modx->lexicon('dbadmin.table_select'),
                'action' => 'selectQuery',
                'button' => true,
                'menu' => true,
            ],
            // Remove table
            [
                'cls' => '',
                'icon' => 'icon icon-trash-o action-red',
                'title' => $this->modx->lexicon('dbadmin.table_remove'),
                // The selected tables, i.e. the clicked row or all selected rows in the context menu
                'action' => 'removeSelected',
                'button' => true,
                'menu' => true,
                'multiple' => $this->modx->lexicon('dbadmin.selected_remove'),
            ]
        ];
        // Views can't be truncated
        $hidden = $row['view'] ? ['truncateSelected'] : [];
        if (!$row['editable']) {
            $hidden = ['truncateSelected', 'removeSelected'];
        }
        // Actions without the permission of their processor
        foreach ($this->getActionPermissions() as $action => $allowed) {
            if (!$allowed) {
                $hidden[] = $action;
            }
        }
        if ($hidden) {
            $row['actions'] = array_values(array_filter($row['actions'], function ($action) use ($hidden) {
                return !in_array($action['action'], $hidden, true);
            }));
        }

        return $row;
    }

    /**
     * Check the permissions of the processors of the row actions once for all rows.
     * @return array If the action is allowed, keyed by action
     */
    protected function getActionPermissions(): array
    {
        if ($this->actionPermissions === null) {
            $this->actionPermissions = [];
            foreach ([
                'viewTable' => ['table_view'],
                'updateTable' => ['table_save'],
                'exportSelected' => ['table_export'],
                'truncateSelected' => ['table_truncate'],
                // The query is created with the field list of the table
                'selectQuery' => ['table_view', 'sql_query_execute'],
                'removeSelected' => ['table_remove'],
            ] as $action => $permissions) {
                $allowed = true;
                foreach ($permissions as $permission) {
                    $allowed = $allowed && $this->modx->hasPermission($permission);
                }
                $this->actionPermissions[$action] = $allowed;
            }
        }
        return $this->actionPermissions;
    }
}

return 'dbAdminTableGetListProcessor';
