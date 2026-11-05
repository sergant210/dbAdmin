<?php
/**
 * Resolve set tables
 *
 * @package dbadmin
 * @subpackage build
 *
 * @var array $options
 * @var xPDOObject $object
 */

if (!function_exists('setTables')) {
    /**
     * @param modX $modx
     * @param dbAdmin $dbadmin
     */
    function setTables($modx, $dbadmin)
    {
        /** @var dbAdminTable[] $tables */
        $tables = $modx->getIterator('dbAdminTable', [
            'class:=' => '',
            'OR:class:IS' => null
        ]);
        foreach ($tables as $table) {
            // Only a class, that can be loaded and belongs to the table, is set
            list($class) = $dbadmin->database->setTableClass($table);
            if ($class) {
                $table->save();
                $modx->log(xPDO::LOG_LEVEL_INFO, 'Set class to \'' . $class . '\' for table \'' . $table->get('name') . '\'');
            }
        }
    }
}

if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            /** @var modX $modx */
            $modx = &$object->xpdo;

            $corePath = $modx->getOption('dbadmin.core_path', null, $modx->getOption('core_path') . 'components/dbadmin/');
            /** @var dbAdmin $dbadmin */
            $dbadmin = $modx->getService('dbadmin', 'dbAdmin', $corePath . 'model/dbadmin/', [
                'core_path' => $corePath
            ]);

            setTables($modx, $dbadmin);
            break;
    }
}
return true;
