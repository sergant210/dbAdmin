<?php
/**
 * Home controller
 *
 * @package dbadmin
 * @subpackage controllers
 */

/**
 * Class dbAdminHomeManagerController
 */
class dbAdminHomeManagerController extends modExtraManagerController
{
    /** @var dbAdmin $dbadmin */
    public $dbadmin;

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        $corePath = $this->modx->getOption('dbadmin.core_path', null, $this->modx->getOption('core_path') . 'components/dbadmin/');
        $this->dbadmin = $this->modx->getService('dbadmin', 'dbAdmin', $corePath . 'model/dbadmin/', [
            'core_path' => $corePath
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function loadCustomCssJs()
    {
        $jsUrl = $this->dbadmin->getOption('jsUrl') . 'mgr/';
        $cssUrl = $this->dbadmin->getOption('cssUrl') . 'mgr/';

        $this->addCss($cssUrl . 'main.css?v=v' . $this->dbadmin->version);
        $this->addJavascript($jsUrl . 'dbadmin.js?v=v' . $this->dbadmin->version);
        $this->addJavascript($jsUrl . 'misc/utils.js?v=v' . $this->dbadmin->version);
        $this->addJavascript($jsUrl . 'widgets/home.panel.js?v=v' . $this->dbadmin->version);
        $this->addJavascript($jsUrl . 'widgets/table.window.js?v=v' . $this->dbadmin->version);
        $this->addJavascript($jsUrl . 'widgets/tables.grid.js?v=v' . $this->dbadmin->version);
        $this->addJavascript($jsUrl . 'widgets/data.grid.js?v=v' . $this->dbadmin->version);
        $this->addJavascript($jsUrl . 'sections/home.js?v=v' . $this->dbadmin->version);
        $this->addHtml('<script type="text/javascript">
		Ext.onReady(function() {
            dbAdmin.config = ' . json_encode($this->dbadmin->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ';
			MODx.load({ xtype: "dbadmin-page-home"});
		});
		</script>');
    }

    /**
     * {@inheritDoc}
     * @return string[]
     */
    public function getLanguageTopics()
    {
        return ['dbadmin:default'];
    }

    /**
     * {@inheritDoc}
     * @param array $scriptProperties
     */
    public function process(array $scriptProperties = [])
    {
        $this->modx->invokeEvent('OnSnipFormPrerender');
    }

    /**
     * {@inheritDoc}
     * @return string|null
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('dbadmin');
    }

    /**
     * {@inheritDoc}
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->dbadmin->getOption('templatesPath') . 'home.tpl';
    }
}
