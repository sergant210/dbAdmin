<?php

if ($object->xpdo) {
	/** @var modX $modx */
	$modx =& $object->xpdo;

	switch ($options[xPDOTransport::PACKAGE_ACTION]) {
		case xPDOTransport::ACTION_INSTALL:
            $modelPath = $modx->getOption('dbadmin_core_path', null, $modx->getOption('core_path') . 'components/dbadmin/') . 'model/';
            $modx->addPackage('dbadmin', $modelPath);

            $manager = $modx->getManager();
            $manager->createObjectContainer('dbAdminTable');
			break;
        case xPDOTransport::ACTION_UPGRADE:
            break;
		case xPDOTransport::ACTION_UNINSTALL:
			break;
	}
}
return true;
