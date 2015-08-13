<?php

if ($object->xpdo) {
    /** @var modX $modx */
	$modx =& $object->xpdo;

	switch ($options[xPDOTransport::PACKAGE_ACTION]) {
		case xPDOTransport::ACTION_INSTALL:
		case xPDOTransport::ACTION_UPGRADE:

			/* assign policy to template */
			if ($policy = $modx->getObject('modAccessPolicy', array('name' => 'dbAdministrator'))) {
				if ($template = $modx->getObject('modAccessPolicyTemplate', array('name' => 'dbAdministratorPolicyTemplate'))) {
					$policy->set('template', $template->get('id'));
					$policy->save();
				}
				else {
					$modx->log(xPDO::LOG_LEVEL_ERROR, '[dbAdmin] Could not find dbAdminPolicyTemplate Access Policy Template!');
				}
			}
			else {
				$modx->log(xPDO::LOG_LEVEL_ERROR, '[dbAdmin] Could not find dbAdministratorPolicy Access Policy!');
			}

			break;
	}
}
return true;