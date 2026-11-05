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
     * @return bool
     */
    public function checkPermissions()
    {
        return $this->modx->hasPermission('tables_list');
    }

    /**
     * {@inheritDoc}
     */
    public function loadCustomCssJs()
    {
        $assetsUrl = $this->dbadmin->getOption('assetsUrl');
        $jsUrl = $this->dbadmin->getOption('jsUrl') . 'mgr/';
        $jsSourceUrl = $assetsUrl . '../../../source/js/mgr/';
        $cssUrl = $this->dbadmin->getOption('cssUrl') . 'mgr/';
        $cssSourceUrl = $assetsUrl . '../../../source/css/mgr/';

        if ($this->dbadmin->getOption('debug') && ($assetsUrl != MODX_ASSETS_URL . 'components/dbadmin/')) {
            $this->addCss($cssSourceUrl . 'dbadmin.css?v=v' . $this->dbadmin->version);
            $this->addJavascript($jsSourceUrl . 'dbadmin.js?v=v' . $this->dbadmin->version);
            $this->addJavascript($jsSourceUrl . 'misc/utils.js?v=v' . $this->dbadmin->version);
            $this->addJavascript($jsSourceUrl . 'widgets/home.panel.js?v=v' . $this->dbadmin->version);
            $this->addJavascript($jsSourceUrl . 'widgets/table.window.js?v=v' . $this->dbadmin->version);
            $this->addJavascript($jsSourceUrl . 'widgets/tables.grid.js?v=v' . $this->dbadmin->version);
            $this->addJavascript($jsSourceUrl . 'widgets/sql.panel.js?v=v' . $this->dbadmin->version);
            $this->addJavascript($jsSourceUrl . 'widgets/data.grid.js?v=v' . $this->dbadmin->version);
            $this->addJavascript($jsSourceUrl . 'sections/home.js?v=v' . $this->dbadmin->version);
        } else {
            $this->addCss($cssUrl . 'dbadmin.min.css?v=v' . $this->dbadmin->version);
            $this->addLastJavascript($jsUrl . 'dbadmin.min.js?v=v' . $this->dbadmin->version);
        }
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
