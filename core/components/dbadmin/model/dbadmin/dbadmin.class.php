<?php

/**
 * The base class for dbAdmin.
 */
class dbAdmin {
    /* @var modX $modx */
    public $modx;

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = array()) {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('dbadmin_core_path', $config, $this->modx->getOption('core_path') . 'components/dbadmin/');
        $assetsUrl = $this->modx->getOption('dbadmin_assets_url', $config, $this->modx->getOption('assets_url') . 'components/dbadmin/');
        $connectorUrl = $assetsUrl . 'connector.php';

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/'
        ), $config);

        $this->modx->addPackage('dbadmin', $this->config['modelPath']);
        $this->modx->lexicon->load('dbadmin:default');
    }

    /**
     * Форматированный массив карт таблиц из таблицы dbadmin_tables_map
     * @return array
     */
    public function getTables(){
        $query = $this->modx->newQuery('dbAdminTable');
        $query->select($this->modx->getSelectColumns('dbAdminTable'));
        $tables = array();
        $tstart = microtime(true);
        if ($query->prepare() && $query->stmt->execute()) {
            $this->modx->queryTime += microtime(true) - $tstart;
            $this->modx->executedQueries++;
            $res = $query->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (!empty($res)) {
            foreach ($res as $table) {
                $tables[$table['name']] = array_slice($table,1,2);
            }
        }
        return $tables;
    }
    /**
     * Форматированный массив таблиц рабочей базы данных
     * @return array
     */
    public function getDbTables(){
        $tableList = array();
        $q = "SHOW TABLES";
        $result = $this->modx->query($q);
        if (is_object($result)) {
            $tables = $result->fetchAll(PDO::FETCH_NUM);

            for ($i = 0; $i<count($tables); $i++) {
                $tableList[$tables[$i][0]] = array('class' => '', 'package' => '');
            }
        }
        return $tableList;
    }

    /**
     * Форматированный массив таблиц рабочей базы данных с дополнительными данными
     * @return array
     */
    public function getTablesStatus(){
        $tables = $tableList = array();
        $q = "SHOW TABLE STATUS";
        $result = $this->modx->query($q);
        if (is_object($result)) {
            $tables = $result->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach ($tables as &$table) {
            $tableList[$table['Name']] = array(
                'type'=>$table['Engine'],
                'rows'=>$table['Rows'],
                'collation'=>$table['Collation'],
                'size'=> round( ($table['Data_length'] + $table['Index_length'])/1024,2)
            );
        }
        unset($tables);
        return $tableList;
    }
    /**
     * Проверяет, нужно ли обновить карту таблиц
     * @return bool
     */
    public function checkNeedUpdate(){
        $tables = array_keys($this->getTables());
        $dbTables = array_keys($this->getDbTables());
        if (array_diff($dbTables,$tables) || array_diff($tables,$dbTables)) return true;
        return false;
    }

    /**
     * Карта системных таблиц MODX. Используется при установке.
     * @return array
     */
    public function getSystemTablesFromArray(){
        return Array
        (
            $this->modx->config['table_prefix'].'access_actiondom' => Array('class' => 'modAccessActionDom','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_actions' => Array('class' => 'modAccessAction','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_category' => Array('class' => 'modAccessCategory','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_context' => Array('class' => 'modAccessContext','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_elements' => Array('class' => 'modAccessElement','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_media_source' => Array('class' => 'modAccessMediaSource','package' => 'modx.sources'),
            $this->modx->config['table_prefix'].'access_menus' => Array('class' => 'modAccessMenu','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_permissions' => Array('class' => 'modAccessPermission','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_policies' => Array('class' => 'modAccessPolicy','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_policy_template_groups' => Array('class' => 'modAccessPolicyTemplateGroup','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_policy_templates' => Array('class' => 'modAccessPolicyTemplate','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_resource_groups' => Array('class' => 'modAccessResourceGroup','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_resources' => Array('class' => 'modAccessResource','package' => 'modx'),
            $this->modx->config['table_prefix'].'access_templatevars' => Array('class' => 'modAccessTemplateVar','package' => 'modx'),
            $this->modx->config['table_prefix'].'actiondom' => Array('class' => 'modActionDom','package' => 'modx'),
            $this->modx->config['table_prefix'].'actions' => Array('class' => 'modAction','package' => 'modx'),
            $this->modx->config['table_prefix'].'actions_fields' => Array('class' => 'modActionField','package' => 'modx'),
            $this->modx->config['table_prefix'].'active_users' => Array('class' => 'modActiveUser','package' => 'modx'),
            $this->modx->config['table_prefix'].'categories' => Array('class' => 'modCategory','package' => 'modx'),
            $this->modx->config['table_prefix'].'categories_closure' => Array('class' => 'modCategoryClosure','package' => 'modx'),
            $this->modx->config['table_prefix'].'class_map' => Array('class' => 'modClassMap','package' => 'modx'),
            $this->modx->config['table_prefix'].'content_type' => Array('class' => 'modContentType','package' => 'modx'),
            $this->modx->config['table_prefix'].'context' => Array('class' => 'modContext','package' => 'modx'),
            $this->modx->config['table_prefix'].'context_resource' => Array('class' => 'modContextResource','package' => 'modx'),
            $this->modx->config['table_prefix'].'context_setting' => Array('class' => 'modContextSetting','package' => 'modx'),
            $this->modx->config['table_prefix'].'dashboard' => Array('class' => 'modDashboard','package' => 'modx'),
            $this->modx->config['table_prefix'].'dashboard_widget' => Array('class' => 'modDashboardWidget','package' => 'modx'),
            $this->modx->config['table_prefix'].'dashboard_widget_placement' => Array('class' => 'modDashboardWidgetPlacement','package' => 'modx'),
            $this->modx->config['table_prefix'].'document_groups' => Array('class' => 'modResourceGroupResource','package' => 'modx'),
            $this->modx->config['table_prefix'].'documentgroup_names' => Array('class' => 'modResourceGroup','package' => 'modx'),
            $this->modx->config['table_prefix'].'element_property_sets' => Array('class' => 'modElementPropertySet','package' => 'modx'),
            $this->modx->config['table_prefix'].'extension_packages' => Array('class' => 'modExtensionPackage','package' => 'modx'),
            $this->modx->config['table_prefix'].'fc_profiles' => Array('class' => 'modFormCustomizationProfile','package' => 'modx'),
            $this->modx->config['table_prefix'].'fc_profiles_usergroups' => Array('class' => 'modFormCustomizationProfileUserGroup','package' => 'modx'),
            $this->modx->config['table_prefix'].'fc_sets' => Array('class' => 'modFormCustomizationSet','package' => 'modx'),
            $this->modx->config['table_prefix'].'lexicon_entries' => Array('class' => 'modLexiconEntry','package' => 'modx'),
            $this->modx->config['table_prefix'].'manager_log' => Array('class' => 'modManagerLog','package' => 'modx'),
            $this->modx->config['table_prefix'].'media_sources' => Array('class' => 'modMediaSource','package' => 'modx.sources'),
            $this->modx->config['table_prefix'].'media_sources_contexts' => Array('class' => 'modMediaSourceContext','package' => 'modx.sources'),
            $this->modx->config['table_prefix'].'media_sources_elements' => Array('class' => 'modMediaSourceElement','package' => 'modx.sources'),
            $this->modx->config['table_prefix'].'member_groups' => Array('class' => 'modUserGroupMember','package' => 'modx'),
            $this->modx->config['table_prefix'].'membergroup_names' => Array('class' => 'modUserGroup','package' => 'modx'),
            $this->modx->config['table_prefix'].'menus' => Array('class' => 'modMenu','package' => 'modx'),
            $this->modx->config['table_prefix'].'namespaces' => Array('class' => 'modNamespace','package' => 'modx'),
            $this->modx->config['table_prefix'].'property_set' => Array('class' => 'modPropertySet','package' => 'modx'),
            $this->modx->config['table_prefix'].'register_messages' => Array('class' => 'modDbRegisterMessage','package' => 'modx.registry.db'),
            $this->modx->config['table_prefix'].'register_queues' => Array('class' => 'modDbRegisterQueue','package' => 'modx.registry.db'),
            $this->modx->config['table_prefix'].'register_topics' => Array('class' => 'modDbRegisterTopic','package' => 'modx.registry.db'),
            $this->modx->config['table_prefix'].'session' => Array('class' => 'modSession','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_content' => Array('class' => 'modResource','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_htmlsnippets' => Array('class' => 'modChunk','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_plugin_events' => Array('class' => 'modPluginEvent','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_plugins' => Array('class' => 'modPlugin','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_snippets' => Array('class' => 'modSnippet','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_templates' => Array('class' => 'modTemplate','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_tmplvar_access' => Array('class' => 'modTemplateVarResourceGroup','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_tmplvar_contentvalues' => Array('class' => 'modTemplateVarResource','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_tmplvar_templates' => Array('class' => 'modTemplateVarTemplate','package' => 'modx'),
            $this->modx->config['table_prefix'].'site_tmplvars' => Array('class' => 'modTemplateVar','package' => 'modx'),
            $this->modx->config['table_prefix'].'system_eventnames' => Array('class' => 'modEvent','package' => 'modx'),
            $this->modx->config['table_prefix'].'system_settings' => Array('class' => 'modSystemSetting','package' => 'modx'),
            $this->modx->config['table_prefix'].'transport_packages' => Array('class' => 'modTransportPackage','package' => 'modx.transport'),
            $this->modx->config['table_prefix'].'transport_providers' => Array('class' => 'modTransportProvider','package' => 'modx.transport'),
            $this->modx->config['table_prefix'].'user_attributes' => Array('class' => 'modUserProfile','package' => 'modx'),
            $this->modx->config['table_prefix'].'user_group_roles' => Array('class' => 'modUserGroupRole','package' => 'modx'),
            $this->modx->config['table_prefix'].'user_group_settings' => Array('class' => 'modUserGroupSetting','package' => 'modx'),
            $this->modx->config['table_prefix'].'user_messages' => Array('class' => 'modUserMessage','package' => 'modx'),
            $this->modx->config['table_prefix'].'user_settings' => Array('class' => 'modUserSetting','package' => 'modx'),
            $this->modx->config['table_prefix'].'users' => Array('class' => 'modUser','package' => 'modx'),
            $this->modx->config['table_prefix'].'workspaces' => Array('class' => 'modWorkspace','package' => 'modx')
        );
    }
}