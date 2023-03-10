<?php
/**
 * Resolve init table values
 *
 * @package dbadmin
 * @subpackage build
 *
 * @var array $options
 * @var xPDOObject $object
 */

/**
 * @param modX $modx
 * @param array $policy
 * @param array $template
 * @param string $permission
 * @return bool
 */
if ($object->xpdo) {
    /** @var modX $modx */
    $modx =& $object->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            $corePath = $modx->getOption('dbadmin.core_path', null, $modx->getOption('core_path') . 'components/dbadmin/');
            /** @var dbAdmin $dbadmin */
            $dbadmin = $modx->getService('dbadmin', 'dbAdmin', $corePath . 'model/dbadmin/', [
                'core_path' => $corePath
            ]);
            // Fill dbAdminTable
            if (!$modx->getCount('dbAdminTable')) {
                $tables = array_merge($dbadmin->database->getDbTables(), $dbadmin->database->getSystemTables());
                foreach ($tables as $name => $info) {
                    $obj = $modx->newObject('dbAdminTable');
                    $obj->set('name', $name);
                    $obj->set('class', $info['class']);
                    $obj->set('package', $info['package']);
                    $obj->save();
                }
            }
            break;
        case xPDOTransport::ACTION_UPGRADE:
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;
