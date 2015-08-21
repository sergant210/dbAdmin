<?php

/**
 * Get a list of Tables
 */
class dbAdminTableGetListProcessor extends modObjectGetListProcessor {
    public $objectType = 'dbadmin_table';
    public $classKey = 'dbAdminTable';
    public $defaultSortField = 'name';
    public $defaultSortDirection = 'ASC';
    public $permission = 'tables_list';
    public $tables = array();
    public $total = 0;


    /**
     * * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return boolean|string
     */
    public function beforeQuery() {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }
        $this->setDefaultProperties(array('mustUpdate'=>false));
        return true;
    }


    /**
     * @param xPDOQuery $c
     *
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c) {
        $query = trim($this->getProperty('query'));
        if ($query) {
            $c->where(array(
                'name:LIKE' => "%{$query}%",
                'OR:class:LIKE' => "%{$query}%",
            ));
        }
        /** @var dbAdmin $dbAdmin */
        $dbAdmin = $this->modx->getService('dbadmin', 'dbAdmin', $this->modx->getOption('dbadmin_core_path', null, $this->modx->getOption('core_path') . 'components/dbadmin/') . 'model/dbadmin/');
        if ($dbAdmin->checkNeedUpdate()) $dbAdmin->synchronize();
        $this->tables = $dbAdmin->getTablesStatus();
        return $c;
    }

    /**
     * @param xPDOObject $object
     *
     * @return array
     */
    public function prepareRow(xPDOObject $object) {
        $row = $object->toArray();
        $row['actions'] = array();
        $table = $row['name'];
        $row = array_merge($row,$this->tables[$table]);
        $row['actions'] = array();

        // get table data
        $row['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-table',
            'title' => $this->modx->lexicon('dbadmin_table_data'),
            'action' => 'viewTable',
            'button' => true,
            'menu' => false,
        );
        // Export
        $row['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-floppy-o',
            'title' => $this->modx->lexicon('dbadmin_table_export'),
            //'multiple' => $this->modx->lexicon('dbadmin_tables_export'),
            'action' => 'exportSelected',
            'button' => true,
            'menu' => false,
        );
        // truncate
        $row['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-file-o',
            'title' => $this->modx->lexicon('dbadmin_table_truncate'),
            'action' => 'truncateSelected',
            'button' => true,
            'menu' => false,
        );
        // Remove
        $row['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-trash-o action-red',
            'title' => $this->modx->lexicon('dbadmin_table_remove'),
            //'multiple' => $this->modx->lexicon('dbadmin_tables_remove'),
            'action' => 'removeSelected',
            'button' => true,
            'menu' => false,
        );

        return $row;
    }
}

return 'dbAdminTableGetListProcessor';