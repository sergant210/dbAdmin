<?php

/**
 * Export selected tables
 */
class dbAdminExportTablesProcessor extends modObjectProcessor {
	public $languageTopics = array('dbadmin');
	public $permission = 'table_export';

    /**
     * {@inheritdoc}
     * @return array|string
     */
    public function process() {
        $filename = 'db_backup.sql';
        $path = $this->modx->getOption('dbadmin_assets_path', NULL, $this->modx->getOption('assets_path') . 'components/dbadmin/').'export/';
        $f = $path.$filename;
        if (!file_exists($f)) {
            return $this->failure();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($f));
        header('Content-Disposition: attachment; filename='.basename($filename));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        ob_get_level() && @ob_end_flush();
        @readfile($f);
        die();
    }
}

return 'dbAdminExportTablesProcessor';