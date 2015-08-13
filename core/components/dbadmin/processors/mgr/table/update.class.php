<?php

/**
 * Update a Table
 */
class dbAdminTableUpdateProcessor extends modObjectUpdateProcessor {
	public $objectType = 'dbadmin_table';
	public $classKey = 'dbAdminTable';
    public $primaryKeyField = 'name';
	public $languageTopics = array('dbadmin');
	public $permission = 'table_save';


	/**
	 * We doing special check of permission
	 * because of our objects is not an instances of modAccessibleObject
	 *
	 * @return bool|string
	 */
	public function beforeSave() {
		if (!$this->checkPermissions()) {
			return $this->modx->lexicon('access_denied');
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function beforeSet() {
		$name = trim($this->getProperty('name'));
		$oldName = trim($this->getProperty('oldName'));
        $rename = true;
        if ($name == $oldName) $rename = false;
		if (empty($name)) {
            return $this->modx->lexicon('dbadmin_table_err_ns');
		}
		elseif ($rename && $this->modx->getCount($this->classKey, array('name' => $name))) {
			$this->modx->error->addField('name', $this->modx->lexicon('dbadmin_table_err_ae'));
		}

		return parent::beforeSet();
	}
}

return 'dbAdminTableUpdateProcessor';
