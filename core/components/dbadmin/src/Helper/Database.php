<?php
/**
 * Database Helper
 *
 * @package dbadmin
 * @subpackage helper
 */

namespace Sergant210\dbAdmin\Helper;

use dbAdminTable;
use Exception;
use modNamespace;
use modX;
use PDO;
use PDOException;
use Sergant210\dbAdmin\dbAdmin;
use SimpleXMLElement;
use xPDO;
use xPDOCacheManager;

/**
 * Class Database
 */
class Database
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * A reference to the dbAdmin instance
     * @var dbAdmin $dbadmin
     */
    public $dbadmin;

    /**
     * The MODX core table names (with prefix) as keys
     * @var array|null $coreTables
     */
    protected $coreTables = null;


    /**
     * @param modX $modx A reference to the modX instance
     */
    function __construct(modX &$modx, $dbadmin)
    {
        $this->modx =& $modx;
        $this->dbadmin =& $dbadmin;
    }

    /**
     * Get a formatted array of dbAdminTable.
     * @return array
     */
    public function getTables()
    {
        $query = $this->modx->newQuery('dbAdminTable');
        $query->select($this->modx->getSelectColumns('dbAdminTable'));
        $tables = [];
        $tstart = microtime(true);
        if ($query->prepare() && $query->stmt->execute()) {
            $this->modx->queryTime += microtime(true) - $tstart;
            $this->modx->executedQueries++;
            $res = $query->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (!empty($res)) {
            foreach ($res as $table) {
                $tables[$table['name']] = array_slice($table, 1, 2);
            }
        }
        return $tables;
    }

    /**
     * Get a formatted array of database tables.
     * @return array
     */
    public function getDbTables()
    {
        $tableList = [];
        $sql = 'SHOW TABLES';
        try {
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
                $tables = $stmt->fetchAll(PDO::FETCH_NUM);

                for ($i = 0; $i < count($tables); $i++) {
                    $tableList[$tables[$i][0]] = ['class' => '', 'package' => ''];
                }
            }
        } catch (PDOException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdmin');
        }
        return $tableList;
    }

    /**
     * Check if a table exists in the database.
     * @param mixed $name
     * @return bool
     */
    public function tableExists($name): bool
    {
        return is_string($name) && $name !== '' && array_key_exists($name, $this->getDbTables());
    }

    /**
     * Check if a database table is a view.
     * @param string $name
     * @return bool
     */
    public function isView(string $name): bool
    {
        $stmt = $this->modx->query("SHOW FULL TABLES WHERE `Table_type` = 'VIEW'");
        return $stmt && in_array($name, $stmt->fetchAll(PDO::FETCH_COLUMN, 0), true);
    }

    /**
     * Remove the table prefix from the start of a table name.
     * @param string $name
     * @return string
     */
    public function stripTablePrefix(string $name): string
    {
        $prefix = (string)$this->modx->getOption('table_prefix');
        return ($prefix !== '' && strpos($name, $prefix) === 0) ? substr($name, strlen($prefix)) : $name;
    }

    /**
     * Check if a string is a safe name for a new table.
     * @param mixed $name
     * @return bool
     */
    public function isValidTableName($name): bool
    {
        return is_string($name) && preg_match('/^[A-Za-z0-9_$]{1,64}$/', $name) === 1;
    }

    /**
     * Check if a string is a safe xPDO package name (i.e. modx, modx.sources
     * or a namespace name), that can't leave the components folder.
     * @param mixed $name
     * @return bool
     */
    public function isValidPackageName($name): bool
    {
        return is_string($name) && preg_match('/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/', $name) === 1;
    }

    /**
     * Get the primary key values of a table row for an xPDO class.
     * @param string $classKey
     * @param array $row
     * @return array|null The primary key values keyed by field or null if a value is missing
     */
    public function getRowPrimaryKeys(string $classKey, array $row): ?array
    {
        $primaryKeys = [];
        foreach ((array)$this->modx->getPK($classKey) as $key) {
            if (!isset($row[$key]) || $row[$key] === '') {
                return null;
            }
            $primaryKeys[$key] = $row[$key];
        }
        return $primaryKeys ?: null;
    }

    /**
     * Add the package of a class and check, that the class can be loaded.
     * The package path uses the <package>.core_path setting like the class
     * search. MODX 3 classes with a namespace are autoloaded, their package
     * doesn't need a model folder.
     * @param string $classKey
     * @param string $package
     * @return bool
     */
    public function loadClassPackage(string $classKey, string $package): bool
    {
        // Only class names reach the autoloader
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $classKey) ||
            !$this->isValidPackageName($package)
        ) {
            return false;
        }
        if (!$this->isCorePackage($package)) {
            $path = $this->modx->getOption($package . '.core_path', null, $this->modx->getOption('core_path') . 'components/' . $package . '/') . 'model/';
            if (is_dir($path) && !$this->modx->addPackage($package, $path)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, $this->modx->lexicon('dbadmin.err_path'));
            }
        } elseif ($package !== 'modx') {
            $this->modx->addPackage($package, $this->modx->getOption('core_path') . 'model/');
        }
        return $this->modx->loadClass($classKey) && $this->modx->getTableName($classKey);
    }

    /**
     * Check if a package is a MODX core package (modx or modx.*).
     * @param string $package
     * @return bool
     */
    public function isCorePackage(string $package): bool
    {
        return $package === 'modx' || strpos($package, 'modx.') === 0;
    }

    /**
     * Get the names of the MODX core tables (with the table prefix). The names
     * are read from the core class maps of MODX 2 (model/modx) and MODX 3
     * (src/Revolution), so they don't depend on the dbAdmin table records.
     * @return string[]
     */
    public function getCoreTables(): array
    {
        if ($this->coreTables === null) {
            $this->coreTables = [];
            $prefix = (string)$this->modx->getOption('table_prefix');
            $corePath = $this->modx->getOption('core_path', null, MODX_CORE_PATH);
            foreach (['src/Revolution/', 'model/modx/'] as $root) {
                foreach (['', '*/', '*/*/'] as $depth) {
                    foreach (glob($corePath . $root . $depth . 'mysql/*.php') ?: [] as $file) {
                        if (preg_match_all("/'table'\s*=>\s*'([^']+)'/", (string)file_get_contents($file), $matches)) {
                            foreach ($matches[1] as $table) {
                                $this->coreTables[$prefix . $table] = true;
                            }
                        }
                    }
                }
            }
        }
        return array_keys($this->coreTables);
    }

    /**
     * Check if a table is a MODX core table.
     * @param string $table The table name with the table prefix
     * @return bool
     */
    public function isCoreTable(string $table): bool
    {
        $this->getCoreTables();
        return isset($this->coreTables[$table]);
    }

    /**
     * Check if the current user can change (edit, truncate, rename or remove)
     * a table. MODX core tables need the table_core_edit permission.
     * @param string $table The table name with the table prefix
     * @return bool
     */
    public function canChangeTable(string $table): bool
    {
        return !$this->isCoreTable($table) || $this->modx->hasPermission('table_core_edit');
    }

    /**
     * Check if the current user can change the table of an xPDO class.
     * @param string $classKey
     * @return bool
     */
    public function canChangeClassTable(string $classKey): bool
    {
        $table = $this->getClassTable($classKey);
        return $table === '' || $this->canChangeTable($table);
    }

    /**
     * Get the database table name of an xPDO class without the database name.
     * @param string $classKey
     * @return string
     */
    public function getClassTable(string $classKey): string
    {
        $table = str_replace('`', '', (string)$this->modx->getTableName($classKey));
        $parts = explode('.', $table);
        return (string)end($parts);
    }

    /**
     * Load the class of a table and check, that the class belongs to the
     * table. A renamed table or a wrong class in the table properties would
     * otherwise show and change the records of another table.
     * @param string $classKey
     * @param string $package
     * @param string $table
     * @return bool
     */
    public function loadTableClass(string $classKey, string $package, string $table): bool
    {
        return $table !== '' &&
            $this->loadClassPackage($classKey, $package) &&
            $this->getClassTable($classKey) === $table;
    }

    /**
     * Quote a MySQL identifier. Unlike xPDO::escape, embedded backticks are
     * doubled, so the identifier can't break out of the quotes.
     * @param string $name
     * @return string
     */
    public function quoteIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * Get the directory for the database exports (outside the web root).
     * @return string
     */
    public function getExportPath(): string
    {
        return $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'export/' . $this->dbadmin->namespace . '/';
    }

    /**
     * Analyze the InnoDB tables, that were changed since their last analysis.
     * The table statistics (rows and size) of InnoDB tables are only estimated
     * and get outdated. Tables without an update time (i.e. after a server
     * restart) are analyzed again after an hour.
     * @return void
     */
    protected function analyzeTables()
    {
        $cacheManager = $this->modx->getCacheManager();
        if (!$cacheManager) {
            return;
        }
        $cacheOptions = [
            xPDO::OPT_CACHE_KEY => $this->dbadmin->namespace
        ];
        $analyzed = $cacheManager->get('analyzed_tables', $cacheOptions);
        $analyzed = is_array($analyzed) ? $analyzed : [];
        $now = time();
        try {
            $tables = $this->modx->query("SHOW TABLE STATUS WHERE Engine = 'InnoDB'");
            $tables = $tables ? $tables->fetchAll(PDO::FETCH_ASSOC) : [];
            $names = [];
            $current = [];
            foreach ($tables as $table) {
                $name = $table['Name'];
                $updated = (string)$table['Update_time'];
                $last = $analyzed[$name] ?? null;
                if (!$last ||
                    $last['updated'] !== $updated ||
                    ($updated === '' && $now - $last['time'] > 3600)
                ) {
                    $names[] = $this->quoteIdentifier($name);
                    $current[$name] = ['updated' => $updated, 'time' => $now];
                } else {
                    $current[$name] = $last;
                }
            }
            if ($names) {
                // Analyze all changed tables in one statement
                $stmt = $this->modx->query('ANALYZE TABLE ' . implode(', ', $names));
                if ($stmt) {
                    $stmt->fetchAll();
                    $stmt->closeCursor();
                }
            }
            // Removed tables are dropped from the cache
            $cacheManager->set('analyzed_tables', $current, 0, $cacheOptions);
        } catch (PDOException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdmin');
        }
    }

    /**
     * Get a formatted array of database tables with additional data.
     * @return array
     */
    public function getTablesStatus()
    {
        $tableList = [];
        $sql = 'SHOW TABLE STATUS';

        // Use ANALYZE TABLE to get the right sizes in InnoDB tables
        $this->analyzeTables();

        try {
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
                $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($tables as &$table) {
                    $tableList[$table['Name']] = [
                        'type' => $table['Engine'],
                        // Views have no engine
                        'view' => $table['Engine'] === null,
                        'rows' => $table['Rows'],
                        'collation' => $table['Collation'],
                        'size' => round(($table['Data_length'] + $table['Index_length']) / 1024, 2)
                    ];
                }
            }
        } catch (PDOException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdmin');
        }
        return $tableList;
    }

    /**
     * Checks if the dbAdmin table should be updated.
     * @return bool
     */
    public function needsUpdate()
    {
        $tables = array_keys($this->getTables());
        $dbTables = array_keys($this->getDbTables());
        return array_diff($dbTables, $tables) || array_diff($tables, $dbTables);
    }

    /**
     * Synchronizes the dbAdmin table with the list of MySQL tables.
     * @return bool
     */
    public function synchronize()
    {
        $tables = array_keys($this->getTables());
        $dbTables = array_keys($this->getDbTables());
        // Remove unnecessary tables
        $diff = array_diff($tables, $dbTables);
        if (!empty($diff)) {
            $this->modx->removeCollection('dbAdminTable', [
                'name:IN' => $diff,
            ]);
        }
        // Add new tables
        $diff = array_diff($dbTables, $tables);
        foreach ($diff as $table) {
            /** @var dbAdminTable $object */
            $object = $this->modx->newObject('dbAdminTable');
            $object->set('name', $table);
            $this->setTableClass($object);
            $object->save();
        }
        return true;
    }

    /**
     * Set the table class of a dbAdminTable record
     * @param dbAdminTable $table
     * @return array
     */
    public function setTableClass(dbAdminTable &$table)
    {
        $name = $this->stripTablePrefix((string)$table->get('name'));
        $class = '';
        $package = '';
        /** @var modNamespace[] $namespaces */
        $namespaces = $this->modx->getIterator('modNamespace');
        foreach ($namespaces as $namespace) {
            $package = ($namespace->get('name') != 'core') ? $namespace->get('name') : 'modx';
            if ($package != '') {
                try {
                    $class = $this->getPackageClass($package, $name);
                    // Only assign a class, that can be loaded and belongs to the table
                    if ($class && !$this->loadTableClass($class, $package, $table->get('name'))) {
                        $class = '';
                    }
                    if ($class) {
                        $table->set('package', $package);
                        $table->set('class', $class);
                        break;
                    }

                } catch (Exception $e) {
                }
            }
        }
        if (!$class) {
            foreach (['modx', 'modx.sources', 'modx.registry.db', 'modx.transport'] as $package) {
                try {
                    $class = $this->getPackageClass($package, $name);
                    // Only assign a class, that can be loaded and belongs to the table
                    if ($class && !$this->loadTableClass($class, $package, $table->get('name'))) {
                        $class = '';
                    }
                    if ($class) {
                        $table->set('package', $package);
                        $table->set('class', $class);
                        break;
                    }
                } catch (Exception $e) {
                }
            }
        }
        return [
            ($class && $package) ? $class : '',
            ($class && $package) ? $package : '',
        ];
    }

    /**
     * @param $package
     * @param $name
     * @return string
     * @throws Exception
     */
    public function getPackageClass($package, $name): string
    {
        if (!$this->isValidPackageName($package)) {
            return '';
        }
        $dbtype = $this->modx->getOption('dbtype', null, 'mysql');
        $packageCorePath = $this->modx->getOption($package . '.core_path', null, $this->modx->getOption('core_path') . 'components/' . $package . '/');
        if ($this->isCorePackage($package)) {
            $schemaFile = MODX_CORE_PATH . 'model/schema/' . $package . '.' . $dbtype . '.schema.xml';
        } elseif (is_file($packageCorePath . 'model/schema/' . $package . '.' . $dbtype . '.schema.xml')) {
            $schemaFile = $packageCorePath . 'model/schema/' . $package . '.' . $dbtype . '.schema.xml';
        } elseif (is_file($packageCorePath . 'schema/' . $package . '.' . $dbtype . '.schema.xml')) {
            $schemaFile = $packageCorePath . 'schema/' . $package . '.' . $dbtype . '.schema.xml';
        } else {
            $schemaFile = $packageCorePath . 'model/' . $package . '/' . $package . '.' . $dbtype . '.schema.xml';
        }
        if (is_file($schemaFile)) {
            try {
                $schema = new SimpleXMLElement($schemaFile, 0, true);
            } catch (Exception $e) {
                throw new Exception($this->modx->lexicon('dbadmin.schema_err_path'));
            }
            if (isset($schema->object)) {
                foreach ($schema->object as $object) {
                    if ($table = (string)$object['table']) {
                        if ($table != $name) {
                            continue;
                        }
                        if ($this->modx->getVersionData()['version'] < 3) {
                            return (string)$object['class'];
                        } else {
                            return ((string)$schema['package'] !== $package) ? rtrim($schema['package'], '\\') . '\\' . $object['class'] : $object['class'];
                        }
                    }
                }
            }
            return '';
        } else {
            throw new Exception($this->modx->lexicon('dbadmin.table_err_path'));
        }
    }
}
