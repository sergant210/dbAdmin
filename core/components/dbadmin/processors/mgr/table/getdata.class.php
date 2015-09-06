<?php

/**
 * Get a table data
 */
class dbAdminTableDataGetListProcessor extends modObjectGetListProcessor {
    public $objectType = 'dbadmin_table';
    public $classKey = '';
    public $defaultSortField = '';
    public $defaultSortDirection = 'ASC';
    public $languageTopics = array('dbadmin');
    public $permission = 'table_view';

    /**
     * {@inheritDoc}
     * @return mixed
     */
    public function process() {
        $beforeQuery = $this->beforeQuery();
        if ($beforeQuery !== true) {
            return $this->failure($beforeQuery);
        }
        $data = $this->getData();
        return $this->outputArray($data['results'],$data['total']);
    }
    /**
     * {@inheritdoc}
     */
    public function beforeQuery() {
        $this->classKey = $this->getProperty('class');
        return true;
    }

    /**
     * Get the data of the query
     * @return array
     */
    public function getData() {
        $data = array();
        $limit = intval($this->getProperty('limit'));
        $start = intval($this->getProperty('start'));
        $package = strtolower(trim($this->getProperty('package','')));
        $path = MODX_CORE_PATH.'components/'.$package.'/model/';
        $foundClass = true;
        if (empty($this->classKey)) {
            $foundClass = false;
        }
        if (!preg_match('/^modx/',$package)) {
            if (is_dir($path)) {
                if (!$this->modx->addPackage($package, MODX_CORE_PATH . 'components/' . $package . '/model/')) {
                    $foundClass = false;
                }
            } else {
                $foundClass = false;
            }
        }
        if ($foundClass) {
            $c = $this->modx->newQuery($this->classKey);
            $c->select($this->modx->getSelectColumns($this->classKey));
            $c = $this->prepareQueryBeforeCount($c);
            $data['total'] = $this->modx->getCount($this->classKey, $c);
            $c = $this->prepareQueryAfterCount($c);

            $sortKey = empty($this->getProperty('sort')) ? $this->modx->getPK($this->classKey) : $this->getProperty('sort');
            if (!is_array($sortKey)) $sortKey = array($sortKey);
            $sortKey = $this->modx->getSelectColumns($this->classKey, $this->getProperty('sortAlias', $this->classKey), '', $sortKey);
            $c->sortby($sortKey, $this->getProperty('dir'));
            if ($limit > 0) {
                $c->limit($limit, $start);
            }
            if ($c->prepare() && $c->stmt->execute()) {
                $data['results'] = $c->stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $data['results'] = array();
            }
        } else {
            $data['results'] = array();
            $table = $this->getProperty('table');
            if (!empty($table)) {
                $query = "SELECT * FROM {$table}";
                $result = $this->modx->query($query);
                if (is_object($result)) {
                    $data['results'] = $result->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            $data['total'] = count($data['results']);
            if ($limit > 0) {
                $data['results'] = array_slice($data['results'],$start,$limit);
            }
        }
        foreach ($data['results'] as &$row) {
            $row = array_map('htmlspecialchars',$row);
            $row['actions'] = array();
            if ($foundClass) {
                // Remove row
                $row['actions'][] = array(
                    'cls' => '',
                    'icon' => 'icon icon-trash-o action-red',
                    'title' => $this->modx->lexicon('dbadmin_row_remove'),
                    'action' => 'removeRow',
                    'button' => true,
                    'menu' => true,
                );
            }
        }

        return $data;
    }
}

return 'dbAdminTableDataGetListProcessor';