<?php

/**
 * Remove selected tables
 */
class dbAdminTableMultiRemoveProcessor extends modProcessor {
    public $objectType = 'dbadmin_table';
    public $classKey = 'dbAdminTable';
	public $languageTopics = array('dbadmin');
	public $permission = 'table_remove';


	/**
	 * {@inheritdoc}
	 */
	public function process() {
        $tables = $this->getProperty('tables','');
        if (empty($tables)) {
            return $this->failure($this->modx->lexicon('dbadmin_table_err_ns'));
        }
        $processorProps = array('processors_path' => dirname(dirname(__FILE__)).'/table/');
        foreach (explode(',',$tables) as $name) {
            $response = $this->modx->runProcessor('remove', array('name' => $name), $processorProps);
            if ($response->isError()) {
                return $response->response;
            }
        }
		return $this->success();
	}
}

return 'dbAdminTableMultiRemoveProcessor';