<?php
/**
 * Set the class names for all tables
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\ObjectGetListProcessor;

/**
 * Class dbAdminSetClassesProcessor
 */
class dbAdminSetClassesProcessor extends ObjectGetListProcessor
{
    public $objectType = 'dbadmin.table';
    public $classKey = 'dbAdminTable';
    public $primaryKeyField = 'name';
    public $permission = 'table_save';

    /**
     * {@inheritDoc}
     * @return bool
     */
    public function beforeQuery()
    {
        $this->setProperty('limit', 0);

        return parent::beforeQuery();
    }

    /**
     * Search a class, that can be loaded and belongs to the table, first in
     * the package of the table, then in all namespaces. A stored class, that
     * doesn't belong to the table (i.e. after a rename in an older version),
     * is removed, if no class is found.
     *
     * @param xPDOObject $object
     * @return array
     */
    public function prepareRow(xPDOObject $object)
    {
        $database = $this->dbadmin->database;
        $tableName = (string)$object->get('name');
        $name = $database->stripTablePrefix($tableName);
        $package = (string)$object->get('package');
        $storedClass = (string)$object->get('class');
        $class = '';
        if ($package !== '') {
            try {
                $class = $database->getPackageClass($package, $name);
            } catch (Exception $e) {
            }
            if ($class && !$database->loadTableClass($class, strtolower($package), $tableName)) {
                $class = '';
            }
        }
        if (!$class) {
            /** @var dbAdminTable $object */
            list($class, $package) = $database->setTableClass($object);
        }
        if ($class) {
            $object->set('package', $package);
            $object->set('class', $class);
            $object->save();

            $this->modx->log(xPDO::LOG_LEVEL_INFO, $this->modx->lexicon('dbadmin.set_class_message', [
                'table' => $name,
                'package' => $package,
                'class' => $class,
            ]));
        } elseif ($storedClass !== '' && !$database->loadTableClass($storedClass, strtolower((string)$object->get('package')), $tableName)) {
            $object->set('package', '');
            $object->set('class', '');
            $object->save();

            $this->modx->log(xPDO::LOG_LEVEL_WARN, $this->modx->lexicon('dbadmin.class_removed_message', [
                'table' => $name,
                'class' => $storedClass,
            ]));
        }

        return $object->toArray();
    }

    /**
     * {@inheritDoc}
     * @return string
     */
    public function outputArray(array $array, $count = false)
    {
        $this->modx->log(xPDO::LOG_LEVEL_INFO, 'COMPLETED');

        return '';
    }
}

return 'dbAdminSetClassesProcessor';
