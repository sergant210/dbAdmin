<?php
/**
 * Remove selected tables
 *
 * @package dbadmin
 * @subpackage processors
 */

use Sergant210\dbAdmin\Processors\Processor;

/**
 * Class dbAdminTableMultiRemoveProcessor
 */
class dbAdminTableMultiRemoveProcessor extends Processor
{
    public $objectType = 'dbadmin.table';
    public $classKey = 'dbAdminTable';
    public $permission = 'table_remove';

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $tables = $this->getProperty('tables', '');
        if (empty($tables)) {
            return $this->failure($this->modx->lexicon('dbadmin.table_err_ns'));
        }
        $processorProps = ['processors_path' => dirname(dirname(__FILE__)) . '/table/'];
        foreach (explode(',', $tables) as $name) {
            $response = $this->modx->runProcessor('remove', ['name' => $name], $processorProps);
            if ($response->isError()) {
                return $response->response;
            }
        }
        return $this->success();
    }
}

return 'dbAdminTableMultiRemoveProcessor';
