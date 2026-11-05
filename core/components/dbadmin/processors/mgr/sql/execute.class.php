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
        $query = trim((string)$this->getProperty('query', ''));
        if ($query === '') {
            return $this->failure($this->modx->lexicon('dbadmin.sql_err_empty'));
        }

        // Replace class names with table names, i.e. {modUser} or {MODX\Revolution\modUser}
        $query = $this->replaceClasses($query);
        if (is_array($query)) {
            return $this->failure($query['error']);
        }

        $res = [];
        $hex = false;
        $select = false;
        $affected = 0;
        $errMode = $this->modx->pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $start = microtime(true);
            $stmt = $this->modx->prepare($query);
            if (!$stmt) {
                throw new PDOException($this->modx->lexicon('dbadmin.sql_executed_failed'));
            }
            $stmt->execute();
            // Statements that return a result set (SELECT, SHOW, DESCRIBE, EXPLAIN, WITH …) have columns
            if ($stmt->columnCount() > 0) {
                $select = true;
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Binary values can't be JSON encoded, show them as hex like the table data grid
                array_walk_recursive($res, function (&$value) use (&$hex) {
                    if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                        $value = '0x' . bin2hex($value);
                        $hex = true;
                    }
                });
            } else {
                $affected = $stmt->rowCount();
            }
            $time = round((microtime(true) - $start) * 1000, 1);
        } catch (PDOException $e) {
            return $this->failure($this->formatError($e));
        } finally {
            $this->modx->pdo->setAttribute(PDO::ATTR_ERRMODE, $errMode);
        }

        if ($select) {
            if (count($res)) {
                $message = $this->modx->lexicon('dbadmin.sql_result_rows', [
                    'count' => count($res),
                    'time' => $time
                ]);
            } else {
                $message = $this->modx->lexicon('dbadmin.sql_result_empty', [
                    'time' => $time
                ]);
            }
            if ($hex) {
                $message .= ' ' . $this->modx->lexicon('dbadmin.sql_result_hex');
            }
        } else {
            $message = $this->modx->lexicon('dbadmin.sql_result_affected', [
                'count' => $affected,
                'time' => $time
            ]);
        }

        switch ($this->getProperty('outputType')) {
            case 'print_r':
                $data = print_r($res, true);
                break;
            default:
                $data = var_export($res, true) . ';';
        }
        return $this->modx->toJSON([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'select' => $select,
            'number' => count($res)
        ]);
    }

    /**
     * Replace the class names in curly braces with the quoted table names.
     * Curly braces in strings, quoted identifiers and comments are kept.
     *
     * @param string $query
     * @return string|array The query or ['error' => message]
     */
    private function replaceClasses(string $query)
    {
        $pattern = <<<'REGEX'
/'(?:[^'\\]++|\\.)*+'|"(?:[^"\\]++|\\.)*+"|`(?:[^`]++|``)*+`|--\s[^\n]*+|#[^\n]*+|\/\*.*?\*\/|\{(\\?\w+(?:\\\w+)*)\}/s
REGEX;
        $error = '';
        $tables = [];
        $result = preg_replace_callback($pattern, function ($match) use (&$error, &$tables) {
            if (!isset($match[1]) || $match[1] === '' || $error !== '') {
                // A string, quoted identifier or comment
                return $match[0];
            }
            $class = $match[1];
            if (!isset($tables[$class])) {
                $tableNames = $this->getClassTables($class);
                if (count($tableNames) === 0) {
                    $error = $this->modx->lexicon('dbadmin.sql_err_class_nf', [
                        'class' => $class
                    ]);
                    return $match[0];
                }
                if (count($tableNames) > 1) {
                    $error = $this->modx->lexicon('dbadmin.sql_err_class_ambiguous', [
                        'class' => $class,
                        'classes' => implode(', ', array_unique($tableNames))
                    ]);
                    return $match[0];
                }
                $tables[$class] = $this->dbadmin->database->quoteIdentifier((string)key($tableNames));
            }
            return $tables[$class];
        }, $query);
        if ($error !== '') {
            return ['error' => $error];
        }
        return ($result === null) ? $query : $result;
    }

    /**
     * Get the tables assigned to a class. A class name without a namespace
     * (i.e. modUser) also matches the namespaced classes of MODX 3
     * (i.e. MODX\Revolution\modUser), if there is no exact match.
     *
     * @param string $class
     * @return array Class names keyed by table name
     */
    private function getClassTables(string $class): array
    {
        $class = ltrim($class, '\\');
        $tables = $this->queryClassTables(['class' => $class]);
        if (!$tables && strpos($class, '\\') === false) {
            // Escape the LIKE wildcards and the backslash (the LIKE escape character)
            $like = '%\\\\' . addcslashes($class, '\\%_');
            $tables = $this->queryClassTables(['class:LIKE' => $like]);
        }
        return $tables;
    }

    /**
     * Only tables with a class, that can be loaded and belongs to the table,
     * are used. Tables with the same class are not merged, so they are
     * reported as ambiguous.
     *
     * @param array $where
     * @return array Class names keyed by table name
     */
    private function queryClassTables(array $where): array
    {
        $tables = [];
        $q = $this->modx->newQuery('dbAdminTable');
        $q->where($where);
        $q->select($this->modx->getSelectColumns('dbAdminTable', 'dbAdminTable', '', ['name', 'class', 'package']));
        if ($q->prepare() && $q->stmt->execute()) {
            foreach ($q->stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($this->dbadmin->database->loadTableClass((string)$row['class'], strtolower((string)$row['package']), (string)$row['name'])) {
                    $tables[$row['name']] = $row['class'];
                }
            }
        }
        return $tables;
    }

    /**
     * Format a PDO exception without the redundant SQLSTATE prefix.
     *
     * @param PDOException $e
     * @return string
     */
    private function formatError(PDOException $e): string
    {
        $info = $e->errorInfo;
        if (is_array($info) && !empty($info[2])) {
            return $this->modx->lexicon('dbadmin.sql_err_query', [
                'message' => $info[2],
                'code' => $info[1] ?? '',
                'sqlstate' => $info[0] ?? '',
            ]);
        }
        return $this->modx->lexicon('dbadmin.sql_err_query_plain', [
            'message' => $e->getMessage(),
        ]);
    }
}

return 'dbAdminExecuteQueryProcessor';
