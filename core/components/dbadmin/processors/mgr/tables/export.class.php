<?php
/**
 * Export selected tables
 *
 * @package dbadmin
 * @subpackage processors
 */

use Ifsnop\Mysqldump\Mysqldump;
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
        $database = $this->dbadmin->database;
        $allTables = array_keys($database->getDbTables());
        $exportDb = ($this->getProperty('export_db') == 'true');
        $tables = $this->getProperty('tables', '');
        if ($exportDb) {
            $selected = $allTables;
            $prefix = preg_replace('/[^\w.-]/', '_', (string)$this->modx->getOption('dbname'));
        } elseif (!empty($tables)) {
            $selected = array_map('trim', explode(',', $tables));
            foreach ($selected as $table) {
                if (!$database->tableExists($table)) {
                    return $this->failure($this->modx->lexicon('dbadmin.table_err_nf'));
                }
            }
            $prefix = 'custom';
        } else {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_ns'));
        }
        if (empty($selected)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_ns'));
        }
        // Add a random token, so that the file name can't be guessed
        $fileName = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.sql';
        $path = $database->getExportPath();
        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_path'));
        }

        $config = $this->modx->connection->config;
        $dumpSettings = [
            // Exclude instead of include, because empty include lists mean all tables or views
            'exclude-tables' => array_values(array_diff($allTables, $selected)),
            'add-drop-table' => true,
            'default-character-set' => $this->getCharset($config['dsn']),
            // The single transaction creates a consistent snapshot without the LOCK TABLES privilege
            'lock-tables' => false,
            // Make the dump importable with a different database user
            'skip-definer' => true,
            // The triggers of all tables are exported, so only for the whole database
            'skip-triggers' => !$exportDb,
        ];

        @set_time_limit(0);
        try {
            $dump = new Mysqldump($config['dsn'], $config['username'], $config['password'], $dumpSettings);
            $dump->start($path . $fileName);
            $this->replaceHeader($path . $fileName);
        } catch (Exception $e) {
            @unlink($path . $fileName);
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdminExportTablesProcessor');
            return $this->failure($this->modx->lexicon('dbadmin.export_err', ['message' => $e->getMessage()]));
        }

        return $this->success('', ['name' => $fileName]);
    }

    /**
     * Mention dbAdmin in the first line of the dump. The line is replaced in
     * place with the same length, so the (maybe large) file is not copied.
     * @param string $file
     * @return void
     */
    protected function replaceHeader(string $file)
    {
        $original = '-- mysqldump-php https://github.com/ifsnop/mysqldump-php';
        $header = '-- dbAdmin ' . $this->dbadmin->version . ' (MODX) with mysqldump-php';
        if (strlen($header) > strlen($original)) {
            return;
        }
        $handle = @fopen($file, 'r+b');
        if (!$handle) {
            return;
        }
        // Only replace the expected line, i.e. not after a changed library header
        if (fread($handle, strlen($original)) === $original) {
            fseek($handle, 0);
            fwrite($handle, str_pad($header, strlen($original)));
        }
        fclose($handle);
    }

    /**
     * Get the connection charset from the DSN
     * @param string $dsn
     * @return string
     */
    protected function getCharset(string $dsn): string
    {
        if (preg_match('/[;:]charset=(\w+)/i', $dsn, $match) && strtolower($match[1]) === 'utf8') {
            return Mysqldump::UTF8;
        }
        return Mysqldump::UTF8MB4;
    }
}

return 'dbAdminExportTablesProcessor';
