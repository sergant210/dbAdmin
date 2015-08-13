<?php

/**
 * The home manager controller for dbAdmin.
 *
 */
class dbAdminHomeManagerController extends dbAdminMainController {

    /**
     * @param array $scriptProperties
     */
    public function process(array $scriptProperties = array()) {
    }


    /**
     * @return null|string
     */
    public function getPageTitle() {
        return $this->modx->lexicon('dbadmin');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs() {
//$this->modx->log(modX::LOG_LEVEL_ERROR, print_r($this->config,1));
        $this->addCss($this->dbAdmin->config['cssUrl'] . 'mgr/main.css');
        //$this->addCss($this->$this->dbAdmin['cssUrl'] . 'mgr/bootstrap.buttons.css');
        $this->addJavascript($this->dbAdmin->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->dbAdmin->config['jsUrl'] . 'mgr/widgets/table.window.js');
        $this->addJavascript($this->dbAdmin->config['jsUrl'] . 'mgr/widgets/tables.grid.js');
        $this->addJavascript($this->dbAdmin->config['jsUrl'] . 'mgr/widgets/data.grid.js');
        $this->addJavascript($this->dbAdmin->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->dbAdmin->config['jsUrl'] . 'mgr/sections/home.js');
        $this->addHtml('<script type="text/javascript">
		Ext.onReady(function() {
			MODx.load({ xtype: "dbadmin-page-home"});
		});
		</script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile() {
        return  $this->dbAdmin->config['templatesPath'] . 'home.tpl';
    }
}