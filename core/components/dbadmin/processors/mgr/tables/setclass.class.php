<?php
/**
 * Set the class name for a table
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\ObjectUpdateProcessor;

/**
 * Class dbAdminSetClassProcessor
 */
class dbAdminSetClassProcessor extends ObjectUpdateProcessor
{
    public $objectType = 'dbadmin.table';
    public $classKey = 'dbAdminTable';
    public $primaryKeyField = 'name';
    public $permission = 'table_save';

    /**
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize()
    {
        $initialized = parent::initialize();
        if ($initialized) {
            $name = str_replace($this->modx->config['table_prefix'], '', $this->object->get('name'));
            $package = $this->getProperty('package');
            if (empty($package)) {
                /** @var modNamespace[] $namespaces */
                $namespaces = $this->modx->getIterator('modNamespace');
                foreach ($namespaces as $namespace) {
                    $package = $namespace->get('name');
                    try {
                        $class = $this->dbadmin->database->getPackageClass($package, $name);
                        if ($class) {
                            break;
                        }
                    } catch (Exception $e) {
                    }
                }
                if (!$class) {
                    foreach (['modx', 'modx.sources', 'modx.registry.db', 'modx.transport'] as $package) {
                        try {
                            $class = $this->dbadmin->database->getPackageClass($package, $name);
                            if ($class) {
                                break;
                            }
                        } catch (Exception $e) {
                        }
                    }
                    if (!$class) {
                        $package = '';
                        $class = '';
                    }
                }
            } else {
                try {
                    $class = $this->dbadmin->database->getPackageClass($package, $name);
                } catch (Exception $e) {
                    return $e->getMessage();
                }
            }
            if ($class) {
                $this->setProperty('package', $package);
                $this->setProperty('class', $class);
            }
        }

        return $initialized;
    }
}

return 'dbAdminSetClassProcessor';
