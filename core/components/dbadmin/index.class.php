<?php

/**
 * Class dbAdminMainController
 */
abstract class dbAdminMainController extends modExtraManagerController {
	/** @var dbAdmin $dbAdmin */
	public $dbAdmin;


	/**
	 * @return void
	 */
	public function initialize() {
		$corePath = $this->modx->getOption('dbadmin_core_path', null, $this->modx->getOption('core_path') . 'components/dbadmin/');
		require_once $corePath . 'model/dbadmin/dbadmin.class.php';

		$this->dbAdmin = new dbAdmin($this->modx);
        //$connectorUrl = $this->modx->getOption('dbadmin_assets_url', NULL, $this->modx->getOption('assets_url') . 'components/dbadmin/').'connector.php';
		$this->addJavascript($this->dbAdmin->config['jsUrl'] . 'mgr/dbadmin.js');
		$this->addHtml('
		<script type="text/javascript">
			dbAdmin.config = ' . $this->modx->toJSON($this->dbAdmin->config) . ';
			dbAdmin.config.connector_url = "' . $this->dbAdmin->config['connectorUrl'] . '";
		</script>
		');

		parent::initialize();
	}


	/**
	 * @return array
	 */
	public function getLanguageTopics() {
		return array('dbadmin:default');
	}


	/**
	 * @return bool
	 */
	public function checkPermissions() {
        // TODO-sergant Проверять права на загрузку
		return true;
	}
}


/**
 * Class IndexManagerController
 */
class IndexManagerController extends dbAdminMainController {

	/**
	 * @return string
	 */
	public static function getDefaultController() {
		return 'home';
	}
}