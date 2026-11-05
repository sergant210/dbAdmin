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
        $filename = basename((string)$this->getProperty('name', ''));
        if (!preg_match('/^[\w.-]+\.sql$/', $filename)) {
            return $this->failure();
        }
        $path = realpath($this->dbadmin->database->getExportPath());
        $f = realpath($path . DIRECTORY_SEPARATOR . $filename);
        if (!$path || !$f || strpos($f, $path . DIRECTORY_SEPARATOR) !== 0 || !is_file($f)) {
            return $this->failure();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($f));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        ob_get_level() && @ob_end_flush();
        @readfile($f);
        @unlink($f);
        die();
    }
}

return 'dbAdminDownloadTablesProcessor';
