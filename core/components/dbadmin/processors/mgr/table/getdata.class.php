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
        $this->classKey = trim((string)$this->getProperty('class', ''));
        $package = (string)$this->getProperty('package', '');
        if ($package !== '' && !$this->dbadmin->database->isValidPackageName($package)) {
            return $this->modx->lexicon('dbadmin.package_err_name');
        }
        return true;
    }

    /**
     * Get the data of the query
     * @return array
     */
    public function getData()
    {
        $limit = intval($this->getProperty('limit'));
        $start = max(0, intval($this->getProperty('start')));
        $package = strtolower((string)$this->getProperty('package', ''));
        $foundClass = $this->dbadmin->database->loadTableClass((string)$this->classKey, $package, (string)$this->getProperty('table', ''));
        if ($foundClass) {
            $data = $this->fetchClassData($limit, $start);
        } else {
            $data = $this->fetchSqlData($limit, $start);
        }
        $canChange = $foundClass && $this->modx->hasPermission('table_save') && $this->dbadmin->database->canChangeClassTable($this->classKey);
        // The values are returned unescaped, the grid encodes them for the output
        foreach ($data['results'] as &$row) {
            foreach ($row as &$value) {
                // Binary values can't be JSON encoded, show them as hex
                if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                    $value = '0x' . bin2hex($value);
                }
            }
            unset($value);
            $row['actions'] = [];
            if ($canChange) {
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
        $sortKey = $this->modx->getSelectColumns($this->classKey, $this->getProperty('sortAlias'), '', $sortKey);
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
        if ($this->dbadmin->database->tableExists($table)) {
            $table = $this->dbadmin->database->quoteIdentifier($table);
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
            $sql = 'SELECT * FROM ' . $table . $this->getSqlOrderBy($table);
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
     * Get the ORDER BY clause of the grid sorting, if the sort column exists in the table
     * @param string $table The quoted table name
     * @return string
     */
    private function getSqlOrderBy(string $table): string
    {
        $sort = (string)$this->getProperty('sort', '');
        if ($sort === '') {
            return '';
        }
        try {
            $stmt = $this->modx->query('SHOW COLUMNS FROM ' . $table);
            $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        } catch (PDOException $e) {
            return '';
        }
        if (!in_array($sort, $columns, true)) {
            return '';
        }
        $dir = (strtoupper((string)$this->getProperty('dir', '')) === 'DESC') ? 'DESC' : 'ASC';
        return ' ORDER BY ' . $this->dbadmin->database->quoteIdentifier($sort) . ' ' . $dir;
    }
}

return 'dbAdminTableDataGetListProcessor';
