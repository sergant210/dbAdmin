<?php
/**
 * Find the class name for a table
 *
 * The class is only returned and not saved, it is saved with the table
 * properties window.
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\Processor;

/**
 * Class dbAdminSetClassProcessor
 */
class dbAdminSetClassProcessor extends Processor
{
    public $objectType = 'dbadmin.table';
    public $classKey = 'dbAdminTable';
    public $permission = 'table_save';

    /**
     * {@inheritDoc}
     * @return array|string
     */
    public function process()
    {
        $database = $this->dbadmin->database;
        $name = trim((string)$this->getProperty('name', ''));
        $package = trim((string)$this->getProperty('package', ''));
        if ($name === '') {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_ns'));
        }
        if ($package !== '' && !$database->isValidPackageName($package)) {
            return $this->failure($this->modx->lexicon('dbadmin.package_err_name'));
        }

        if ($package === '') {
            // Search the class in all namespaces, with a temporary record
            /** @var dbAdminTable $table */
            $table = $this->modx->newObject($this->classKey);
            $table->set('name', $name);
            list($class, $package) = $database->setTableClass($table);
        } else {
            try {
                $class = $database->getPackageClass($package, $database->stripTablePrefix($name));
                if ($class && !$database->loadTableClass($class, strtolower($package), $name)) {
                    $class = '';
                }
            } catch (Exception $e) {
                return $this->failure($e->getMessage());
            }
        }
        if (!$class) {
            return $this->failure($this->modx->lexicon('dbadmin.class_err_nf'));
        }

        return $this->success('', [
            'class' => $class,
            'package' => $package,
        ]);
    }
}

return 'dbAdminSetClassProcessor';
