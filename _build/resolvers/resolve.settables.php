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
        $tables = $modx->getIterator('dbAdminTable', ['class:=' => '']);
        foreach ($tables as $table) {
            $name = str_replace($modx->config['table_prefix'], '', $table->get('name'));
            $namespaces = $modx->getIterator('modNamespace');
            foreach ($namespaces as $namespace) {
                $package = ($namespace->get('name') != 'core') ? $namespace->get('name') : 'modx';
                if ($package != '' && $table->get('class') == '') {
                    try {
                        $class = $dbadmin->database->getPackageClass($package, $name);
                        if ($class) {
                            $table->set('package', $package);
                            $table->set('class', $class);
                            $table->save();
                            $modx->log(xPDO::LOG_LEVEL_INFO, 'Set class to \'' . $class . '\' for table \'' . $name . '\'');
                            break;
                        }

                    } catch (Exception $e) {
                    }
                }
            }
            if (!$class) {
                foreach (['modx', 'modx.sources', 'modx.registry.db', 'modx.transport'] as $package) {
                    try {
                        $class = $dbadmin->database->getPackageClass($package, $name);
                        if ($class) {
                            $table->set('package', $package);
                            $table->set('class', $class);
                            $table->save();
                            $modx->log(xPDO::LOG_LEVEL_INFO, 'Set class to \'' . $class . '\' for table \'' . $name . '\'');
                            break;
                        }
                    } catch (Exception $e) {
                    }
                }
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
