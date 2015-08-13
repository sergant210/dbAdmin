<?php

/**
 * Get a list of Tables
 */
class dbAdminTablesGetListProcessor extends modObjectProcessor {
    public $languageTopics = array('dbadmin');
    public $permission = 'tables_list';
    public $tables = array();
    public $total = 0;


    /**
     * {@inheritDoc}
     * @return mixed
     */
    public function process() {
        /** @var dbAdmin $dbAdmin */
        $dbAdmin = $this->modx->getService('dbadmin', 'dbAdmin', $this->modx->getOption('dbadmin_core_path', null, $this->modx->getOption('core_path') . 'components/dbadmin/') . 'model/dbadmin/');
        if ($this->needToUpdate()) $this->updateTableList();
        $this->tables = $dbAdmin->getTables();
        $tables = $this->getData();
        $list = array();
        foreach ($tables as &$table) {
            $list[] = $this->prepareRow($table);
        }
        return $this->outputArray($list,$this->total);
    }

    /**
     * Проверяет, нужно ли обновлять список таблиц со своей таблицей
     * @return bool
     */
    protected function needToUpdate() {
        return true;
    }

    protected function updateTableList(){

    }
    /**
     * Get the data of the query
     * @return array
     */
    public function getData() {
        $limit = intval($this->getProperty('limit'));
        $start = intval($this->getProperty('start'));
        $tables = array();
        $q = "SHOW TABLE STATUS";
        $query = trim($this->getProperty('query',''));
        if (!empty($query)) $q .= " LIKE '%{$query}%'";
        $result = $this->modx->query($q);
        if (is_object($result)) {
            $tables = $result->fetchAll(PDO::FETCH_ASSOC);
            $this->total = count($tables);
        }

        if ($sortDir = $this->getProperty('dir')) {
            if ($sortDir == 'ASC') {
                ksort($tables);
            } else {
                krsort($tables);
            }
        }
        if ($limit > 0) {
            $tables = array_slice($tables,$start,$limit);
        }
        return $tables;
    }

    /**
     * @param $data
     * @return array
     * @internal param xPDOObject $object
     *
     */
	public function prepareRow(&$data) {
        //$query = 'SELECT * FROM '.$this->modx->config['table_prefix'].'dbadmin_tables';
        //$tableList = '';
//$this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->getOption(xPDO::OPT_TABLE_PREFIX, null, 'No'));

        $row['name'] =  $data['Name'];
        $row['class'] =  $this->tables[$data['name']]['class'];
        $row['package'] =  $this->tables[$data['name']]['package'];
        $row['type'] = $data['Engine'];
        $row['collation'] = $data['Collation'];
        $row['rows'] = $data['Rows'];
        $row['size'] = ($data['Data_length']+$data['Index_length'])/1024;
        $row['size'] = round($row['size'],1);
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

return 'dbAdminTablesGetListProcessor';