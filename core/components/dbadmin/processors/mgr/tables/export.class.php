<?php
/**
 * Export selected tables
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\Processor;

/**
 * Class dbAdminExportTablesProcessor
 */
class dbAdminExportTablesProcessor extends Processor
{
    public $permission = 'table_export';

    /**
     * @return array|string
     */
    public function process()
    {
        $tables = $this->getProperty('tables', '');
        if ($this->getProperty('export_db') == 'true') {
            $tables = $this->getTables();
            $fileName = $this->modx->config['dbname'] . '_' . date('Ymd_His') . '.sql';
        } elseif (!empty($tables)) {
            $tables = array_map('trim', explode(',', $tables));
            sort($tables);
            $fileName = 'custom_' . date('Ymd_His') . '.sql';
        } else {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_ns'));
        }
        $path = $this->modx->getOption('dbadmin.assets_path', NULL, $this->modx->getOption('assets_path') . 'components/dbadmin/') . 'export/';
        if (!is_dir($path) && !mkdir($path, 0755)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_path'));
        }
        $sql = '-- ' . $this->modx->lexicon('dbadmin.createdon') . date('j M Y, H:i') . "\n\n";
        foreach ($tables as $table) {
            $sql .= $this->prepareTableCreateSql($table);
            $sql .= $this->prepareTableInsertSql($table);
            $sql .= "\n\n-- --------------------------------------------------------\n\n";
        }
        if (!empty($tables)) {
            file_put_contents($path . $fileName, $sql);
        }

        return $this->success('', ['name' => $fileName]);
    }

    /**
     * Get tables
     * @return array
     */
    protected function getTables()
    {
        $tables = [];
        $sql = 'SHOW TABLE STATUS';
        try {
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
            }
        } catch (PDOException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdminExportTablesProcessor');
        }
        return $tables;
    }

    /**
     * Prepare SQL for creating a table
     * @param $table
     * @return string
     */
    protected function prepareTableCreateSql($table)
    {
        $table = $this->modx->escape($table);
        $sql = "--\n-- " . $this->modx->lexicon('dbadmin.table_structure') . ' ' . $table . "\n--\n\n";
        try {
            if ($stmt = $this->modx->prepare('SHOW CREATE TABLE ' . $table)) {
                $stmt->execute();
                $tables = $stmt->fetch(PDO::FETCH_ASSOC);
                $sql .= 'DROP TABLE IF EXISTS ' . $table . ";\n";
                $sql .= $tables['Create Table'] . ";\n";
            }
        } catch (PDOException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdminExportTablesProcessor');
        }
        return $sql;
    }

    /**
     * Prepare SQL for inserting data into a table
     * @param $table
     * @return string
     */
    protected function prepareTableInsertSql($table)
    {
        // Get fields
        $table = $this->modx->escape($table);
        $fields = $this->getFields($table);
        if ($fields === false) {
            return '';
        }
        $_fieldList = '';
        foreach ($fields as $field) {
            if (!empty($_fieldList)) {
                $_fieldList .= ', ';
            }
            $_fieldList .= $this->modx->escape($field['name']);
        }
        // Fetch data
        try {
            $rows = [];
            if ($stmt = $this->modx->prepare('SELECT ' . $_fieldList . ' FROM ' . $table)) {
                $stmt->execute();
                if (!$rows = $stmt->fetchAll(PDO::FETCH_NUM)) {
                    return '';
                }
            }
        } catch (PDOException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdminExportTablesProcessor');
            return '';
        }
        $num_rows = count($rows);
        $num_cols = count($rows[0]);
        $sql = "\n--\n-- " . $this->modx->lexicon('dbadmin.table_dump') . " $table\n--\n\n";

        // Prepare SQL
        $sql .= 'INSERT INTO ' . $table . ' (' . $_fieldList . ') VALUES' . "\n";
        for ($i = 0; $i < $num_rows; ++$i) {
            $sql .= '(';
            for ($j = 0; $j < $num_cols; ++$j) {
                if (is_null($rows[$i][$j])) {
                    $sql .= 'NULL';
                } elseif ($fields[$j]['type'] == 'string') {
                    $val = "'" . addslashes($rows[$i][$j]) . "'";
                    $sql .= strtr($val, ["\n" => '\n', "\r" => '\r', "\t" => '\t']
                    );
                } elseif (!is_numeric($rows[$i][$j])) {
                    $sql .= '\'' . $rows[$i][$j] . '\'';
                } else {
                    $sql .= $rows[$i][$j];
                }
                if ($j != $num_cols - 1) {
                    $sql .= ', ';
                }
            }
            $sql .= ')';
            if ($i != ($num_rows - 1)) {
                $sql .= ",\n";
            }
        }
        $sql .= ';';
        return $sql;
    }

    /**
     * Get array of fields
     * @param $table
     * @return array|bool
     */
    protected function getFields($table)
    {
        $sql = 'SHOW COLUMNS FROM ' . $table;
        try {
            $data = [];
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            return false;
        }
        $fields = [];
        $i = 0;
        foreach ($data as $field) {
            $fields[$i]['name'] = $field['Field'];
            foreach (['int', 'decimal', 'float', 'double', 'real'] as $type) {
                if (stripos($field['Type'], $type) !== false) {
                    $fields[$i]['type'] = 'number';
                    break;
                } else {
                    $fields[$i]['type'] = 'string';
                }
            }
            $i++;
        }
        return $fields;
    }
}

return 'dbAdminExportTablesProcessor';
