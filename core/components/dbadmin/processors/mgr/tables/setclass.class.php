<?php

/**
 * Set the class name for a table
 */
class dbAdminSetClassProcessor extends modObjectUpdateProcessor {
    public $objectType = 'dbadmin.table';
    public $classKey = 'dbAdminTable';
    public $primaryKeyField = 'name';
    public $languageTopics = array('dbadmin');
    public $permission = 'table_save';

    /**
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize() {
        $initialized = parent::initialize();
        if ($initialized) {
            $name = str_replace($this->modx->config['table_prefix'], '', $this->object->get('name'));
            $package = $this->getProperty('package');
            if (empty($package)) {
                return $this->modx->lexicon('dbadmin.no_package');
            }
            $dbtype = $this->modx->getOption('dbtype', null, 'mysql');
            $packageCorePath = $this->modx->getOption("{$package}.core_path", null, $this->modx->getOption('core_path') . "components/{$package}/");
            if (strpos($package, 'modx') !== false) {
                $schemaFile = MODX_CORE_PATH . "model/schema/{$package}.{$dbtype}.schema.xml";
            } else {
                $schemaFile = $packageCorePath . "model/schema/{$package}.{$dbtype}.schema.xml";
            }
            if (!is_file($schemaFile)) {
                $schemaFile = $packageCorePath . "model/{$package}/{$package}.{$dbtype}.schema.xml";
            }
            if (is_file($schemaFile)) {
                $schema = new SimpleXMLElement($schemaFile, 0, true);
                if (isset($schema->object)) {
                    foreach ($schema->object as $object) {
                        if ($table = (string)$object['table']) {
                            if ($table != $name) {
                                continue;
                            }
                            $this->setProperty('class', (string)$object['class']);
                        }
                    }
                }
                unset($schema);
            } else {
                return $this->modx->lexicon('dbadmin.table_err_path');
            }
        }

        return $initialized;
    }
}

return 'dbAdminSetClassProcessor';
