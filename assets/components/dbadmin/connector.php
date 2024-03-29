<?php
/**
 * dbAdmin connector
 *
 * @package dbadmin
 * @subpackage connector
 *
 * @var modX $modx
 */

require_once dirname(__FILE__, 4) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption('dbadmin.core_path', null, $modx->getOption('core_path') . 'components/dbadmin/');
/** @var dbAdmin $dbadmin */
$dbadmin = $modx->getService('dbadmin', 'dbAdmin', $corePath . 'model/dbadmin/', [
    'core_path' => $corePath
]);

// Handle request
$modx->request->handleRequest([
    'processors_path' => $dbadmin->getOption('processorsPath'),
    'location' => ''
]);
