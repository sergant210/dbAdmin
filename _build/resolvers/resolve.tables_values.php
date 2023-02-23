<?php
/**
 * Resolve table values
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
            require_once $corePath . 'model/dbadmin/dbadmin.class.php';
            /** @var dbAdmin $dbAdmin */
            $dbAdmin = new dbAdmin($modx);
			// Fill the table dbadmin_tables_map
			if (!$modx->getCount('dbAdminTable')) {
                $tables = array_merge($dbAdmin->getDbTables(),$dbAdmin->getSystemTablesFromArray());
                 foreach ($tables as $name=>$info) {
                     $obj = $modx->newObject('dbAdminTable');
                     $obj->set('name',$name);
                     $obj->set('class',$info['class']);
                     $obj->set('package',$info['package']);
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
