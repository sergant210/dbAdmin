<?php
/**
 * Get table data
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\ObjectGetListProcessor;

/**
 * Class dbAdminTableDataGetListProcessor
 */
class dbAdminTableDataGetListProcessor extends ObjectGetListProcessor
{
    public $objectType = 'dbadmin.table';
    public $classKey = '';
    public $defaultSortField = '';
    public $defaultSortDirection = 'ASC';
    public $permission = 'table_view';

    /**
     * {@inheritDoc}
     * @return mixed
     */
    public function process()
    {
        $beforeQuery = $this->beforeQuery();
        if ($beforeQuery !== true) {
            return $this->failure($beforeQuery);
        }
        $data = $this->getData();
        return $this->outputArray($data['results'], $data['total']);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeQuery()
    {
        $this->classKey = $this->getProperty('class');
        return true;
    }

    /**
     * Get the data of the query
     * @return array
     */
    public function getData()
    {
        $limit = intval($this->getProperty('limit'));
        $start = intval($this->getProperty('start'));
        $package = strtolower($this->getProperty('package', ''));
        $path = $this->modx->getOption($package . '.core_path', null, $this->modx->getOption('core_path') . 'components/' . $package . '/') . 'model/';
        $foundClass = $this->isClass($package, $path);
        if ($foundClass) {
            $data = $this->fetchClassData($limit, $start);
        } else {
            $data = $this->fetchSqlData($limit, $start);
        }
        foreach ($data['results'] as &$row) {
            $row = array_map('htmlspecialchars', $row);
            $row['actions'] = [];
            if ($foundClass) {
                // Remove row
                $row['actions'][] = [
                    'cls' => '',
                    'icon' => 'icon icon-trash-o action-red',
                    'title' => $this->modx->lexicon('dbadmin.row_remove'),
                    'action' => 'removeRow',
                    'button' => true,
                    'menu' => true,
                ];
            }
        }

        return $data;
    }

    /**
     * @param int $limit
     * @param int $start
     * @return array
     */
    private function fetchClassData(int $limit, int $start): array
    {
        $data = [
            'results' => []
        ];
        $c = $this->modx->newQuery($this->classKey);
        $c->select($this->modx->getSelectColumns($this->classKey));
        $c = $this->prepareQueryBeforeCount($c);
        $data['total'] = $this->modx->getCount($this->classKey, $c);
        $c = $this->prepareQueryAfterCount($c);

        $sortKey = $this->getProperty('sort', '') == '' ? $this->modx->getPK($this->classKey) : $this->getProperty('sort');
        if (!is_array($sortKey)) {
            $sortKey = [$sortKey];
        }
        $sortKey = $this->modx->getSelectColumns($this->classKey, $this->getProperty('sortAlias', $this->classKey), '', $sortKey);
        $c->sortby($sortKey, $this->getProperty('dir'));
        if ($limit > 0) {
            $c->limit($limit, $start);
        }
        if ($c->prepare() && $c->stmt->execute()) {
            $data['results'] = $c->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $data;
    }

    /**
     * @param int $limit
     * @param int $start
     * @return array
     */
    private function fetchSqlData(int $limit, int $start)
    {
        $data = [
            'results' => [],
            'total' => 0
        ];
        $table = $this->getProperty('table');
        if (!empty($table)) {
            $table = $this->modx->escape($table);
            $sql = 'SELECT COUNT(*) FROM ' . $table;
            try {
                if ($stmt = $this->modx->prepare($sql)) {
                    $stmt->execute();
                    $data['total'] = intval($stmt->fetchColumn());
                }
            } catch (PDOException $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdminTableDataGetListProcessor');
                return $data;
            }
            $sql = 'SELECT * FROM ' . $table;
            if ($limit > 0) {
                $sql .= " LIMIT $start, $limit";
            }
            try {
                if ($stmt = $this->modx->prepare($sql)) {
                    $stmt->execute();
                    $data['results'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdminTableDataGetListProcessor');
                return $data;
            }
        }
        return $data;
    }

    /**
     * Check if the package and the path reference an xPDO package and add that xPDO package
     *
     * @param string $package
     * @param string $path
     * @return bool
     */
    private function isClass(string $package, string $path): bool
    {
        $foundClass = true;
        if (empty($this->classKey)) {
            $foundClass = false;
        }
        if (!preg_match('/^modx/', $package)) {
            if (is_dir($path)) {
                if (!$this->modx->addPackage($package, $path)) {
                    $foundClass = false;
                }
            } else {
                $foundClass = false;
            }
        } elseif ($package != 'modx') {
            if (!$this->modx->addPackage($package, $this->modx->getOption('core_path') . 'model/')) {
                $foundClass = false;
            }
        }
        return $foundClass;
    }
}

return 'dbAdminTableDataGetListProcessor';
