<?php
/**
 * Execute sql query
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\Processor;

/**
 * Class dbAdminExecuteQueryProcessor
 */
class dbAdminExecuteQueryProcessor extends Processor
{
    public $permission = 'sql_query_execute';

    /**
     * @return mixed
     */
    public function process()
    {
        $query = trim(ltrim($this->getProperty('query'), '\n'));
        $select = preg_match('/^(select|show)/i', $query);
        // Replace class names with table names
        if (preg_match_all('/\{(\w+)\}/', $query, $match)) {
            for ($i = 0; $i < count($match[0]); $i++) {
                $q = $this->modx->newQuery('dbAdminTable');
                $q->where([
                    'class:LIKE' => $match[1][$i],
                ]);
                $q->select('name');
                $tableName = $this->modx->getValue($q->prepare());
                $query = str_replace($match[0][$i], $tableName, $query);
            }
        }
        $res = [];
        if (!empty($query)) {
            try {
                if ($stmt = $this->modx->prepare($query)) {
                    $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    if (!$stmt->execute()) {
                        throw new PDOException();
                    }
                    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                return $this->failure($e->getMessage());
            }
        }
        $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $type = $this->getProperty('outputType');
        switch ($type) {
            case 'print_r':
                $data = print_r($res, 1);
                break;
            default:
                $data = var_export($res, 1) . ';';
        }
        return $this->modx->toJSON([
            'success' => true,
            'message' => '',
            'data' => $data,
            'select' => $select,
            'number' => count($res)
        ]);
    }
}

return 'dbAdminExecuteQueryProcessor';
