<?php
// Load MODX config
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
}
else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var dbAdmin $dbAdmin */
$dbAdmin = $modx->getService('dbadmin', 'dbAdmin', $modx->getOption('dbadmin_core_path', null, $modx->getOption('core_path') . 'components/dbadmin/') . 'model/dbadmin/');
//$modx->lexicon->load('dbadmin:default');

// handle request
$corePath = $modx->getOption('dbadmin_core_path', null, $modx->getOption('core_path') . 'components/dbadmin/');
$path = $modx->getOption('processorsPath', $dbAdmin->config, $corePath . 'processors/');
$modx->request->handleRequest(array(
	'processors_path' => $path,
	'location' => '',
));