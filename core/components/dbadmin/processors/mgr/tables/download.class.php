<?php
/**
 * Download exported file
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\Processor;

/**
 * Class dbAdminExportTablesProcessor
 */
class dbAdminDownloadTablesProcessor extends Processor
{
    public $permission = 'table_export';

    /**
     * {@inheritDoc}
     * @return array|string
     */
    public function process()
    {
        $filename = $this->getProperty('name', 'db_backup.sql');
        $path = $this->modx->getOption('dbadmin.assets_path', NULL, $this->modx->getOption('assets_path') . 'components/dbadmin/') . 'export/';
        $f = $path . $filename;
        if (!file_exists($f)) {
            return $this->failure();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($f));
        header('Content-Disposition: attachment; filename=' . basename($filename));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        ob_get_level() && @ob_end_flush();
        @readfile($f);
        die();
    }
}

return 'dbAdminDownloadTablesProcessor';
