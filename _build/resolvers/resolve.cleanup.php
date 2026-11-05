<?php
/**
 * Resolve cleanup of database exports
 *
 * Older versions saved the database exports in the web accessible folder
 * assets/components/dbadmin/export/. These files are removed during install
 * and upgrade. During uninstall, the current export folder is removed too.
 *
 * @package dbadmin
 * @subpackage build
 *
 * @var array $options
 * @var xPDOTransport $transport
 */

if (!function_exists('dbAdminRemoveExports')) {
    /**
     * Remove all SQL exports in a folder and the folder itself, if it is empty afterwards.
     *
     * @param modX $modx
     * @param string $path
     */
    function dbAdminRemoveExports($modx, $path)
    {
        if (!is_dir($path)) {
            return;
        }
        $count = 0;
        foreach (glob(rtrim($path, '/') . '/*.sql') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $count++;
            } else {
                $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not remove the database export ' . $file);
            }
        }
        if ($count) {
            $modx->log(xPDO::LOG_LEVEL_INFO, 'Removed ' . $count . ' database export(s) in ' . $path);
        }
        // Remove the folder, if it contains nothing else
        if (!array_diff(scandir($path), ['.', '..']) && @rmdir($path)) {
            $modx->log(xPDO::LOG_LEVEL_INFO, 'Removed the folder ' . $path);
        }
    }
}

/** @var modX $modx */
$modx = $transport->xpdo;

$success = true;

$legacyPath = $modx->getOption('dbadmin.assets_path', null, $modx->getOption('assets_path') . 'components/dbadmin/') . 'export/';
$exportPath = $modx->getOption('core_path', null, MODX_CORE_PATH) . 'export/' . $options['namespace'] . '/';

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        dbAdminRemoveExports($modx, $legacyPath);
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        dbAdminRemoveExports($modx, $legacyPath);
        dbAdminRemoveExports($modx, $exportPath);
        break;
}
return $success;
