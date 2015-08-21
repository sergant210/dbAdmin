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
     * Проверяет, нужно ли обновить таблицу dbAdmin
     * @return bool
     */
    public function checkNeedUpdate(){
        $tables = array_keys($this->getTables());
        $dbTables = array_keys($this->getDbTables());
        return (array_diff($dbTables,$tables) || array_diff($tables,$dbTables)) ? true : false;
    }

    /**
     * Синхронизирует таблицу dbAdmin со списком таблиц MySql
     * @return array|bool
     */
    public function synchronize(){
        $tablesList = $this->getTables();
        $dbTablesList = $this->getDbTables();
        $tables = array_keys($tablesList);
        $dbTables = array_keys($dbTablesList);
        // Удаляем лишние таблицы
        $diff = array_diff($tables,$dbTables);
        if (!empty($diff)) {
            $query = $this->modx->newQuery('dbAdminTable');
            $query->command('delete');
            $query->where(array(
                'name:IN' => $diff,
            ));
            $query->prepare();
            if (!$query->stmt->execute()) {
                return $this->error($this->modx->lexicon('dbadmin_sync'));
            }
        }
        // Добавляем новые таблицы
        $diff = array_diff($dbTables,$tables);
        foreach ($diff as $table) {
            $obj = $this->modx->newObject('dbAdminTable');
            $obj->set('name',$table);
            $obj->save();
        }
        return true;
    }
    /**
     * Карта системных таблиц MODX. Используется при установке.
     * @return array
     */
    public function getSystemTablesFromarray(){
        $prefix = $this->modx->config['table_prefix'];
        $modxTables = array
        (
            $prefix.'access_actiondom' => array('class' => 'modAccessActionDom','package' => 'modx'),
            $prefix.'access_actions' => array('class' => 'modAccessAction','package' => 'modx'),
            $prefix.'access_category' => array('class' => 'modAccessCategory','package' => 'modx'),
            $prefix.'access_context' => array('class' => 'modAccessContext','package' => 'modx'),
            $prefix.'access_elements' => array('class' => 'modAccessElement','package' => 'modx'),
            $prefix.'access_media_source' => array('class' => 'modAccessMediaSource','package' => 'modx.sources'),
            $prefix.'access_menus' => array('class' => 'modAccessMenu','package' => 'modx'),
            $prefix.'access_permissions' => array('class' => 'modAccessPermission','package' => 'modx'),
            $prefix.'access_policies' => array('class' => 'modAccessPolicy','package' => 'modx'),
            $prefix.'access_policy_template_groups' => array('class' => 'modAccessPolicyTemplateGroup','package' => 'modx'),
            $prefix.'access_policy_templates' => array('class' => 'modAccessPolicyTemplate','package' => 'modx'),
            $prefix.'access_resource_groups' => array('class' => 'modAccessResourceGroup','package' => 'modx'),
            $prefix.'access_resources' => array('class' => 'modAccessResource','package' => 'modx'),
            $prefix.'access_templatevars' => array('class' => 'modAccessTemplateVar','package' => 'modx'),
            $prefix.'actiondom' => array('class' => 'modActionDom','package' => 'modx'),
            $prefix.'actions' => array('class' => 'modAction','package' => 'modx'),
            $prefix.'actions_fields' => array('class' => 'modActionField','package' => 'modx'),
            $prefix.'active_users' => array('class' => 'modActiveUser','package' => 'modx'),
            $prefix.'categories' => array('class' => 'modCategory','package' => 'modx'),
            $prefix.'categories_closure' => array('class' => 'modCategoryClosure','package' => 'modx'),
            $prefix.'class_map' => array('class' => 'modClassMap','package' => 'modx'),
            $prefix.'content_type' => array('class' => 'modContentType','package' => 'modx'),
            $prefix.'context' => array('class' => 'modContext','package' => 'modx'),
            $prefix.'context_resource' => array('class' => 'modContextResource','package' => 'modx'),
            $prefix.'context_setting' => array('class' => 'modContextSetting','package' => 'modx'),
            $prefix.'dashboard' => array('class' => 'modDashboard','package' => 'modx'),
            $prefix.'dashboard_widget' => array('class' => 'modDashboardWidget','package' => 'modx'),
            $prefix.'dashboard_widget_placement' => array('class' => 'modDashboardWidgetPlacement','package' => 'modx'),
            $prefix.'document_groups' => array('class' => 'modResourceGroupResource','package' => 'modx'),
            $prefix.'documentgroup_names' => array('class' => 'modResourceGroup','package' => 'modx'),
            $prefix.'element_property_sets' => array('class' => 'modElementPropertySet','package' => 'modx'),
            $prefix.'extension_packages' => array('class' => 'modExtensionPackage','package' => 'modx'),
            $prefix.'fc_profiles' => array('class' => 'modFormCustomizationProfile','package' => 'modx'),
            $prefix.'fc_profiles_usergroups' => array('class' => 'modFormCustomizationProfileUserGroup','package' => 'modx'),
            $prefix.'fc_sets' => array('class' => 'modFormCustomizationSet','package' => 'modx'),
            $prefix.'lexicon_entries' => array('class' => 'modLexiconEntry','package' => 'modx'),
            $prefix.'manager_log' => array('class' => 'modManagerLog','package' => 'modx'),
            $prefix.'media_sources' => array('class' => 'modMediaSource','package' => 'modx.sources'),
            $prefix.'media_sources_contexts' => array('class' => 'modMediaSourceContext','package' => 'modx.sources'),
            $prefix.'media_sources_elements' => array('class' => 'modMediaSourceElement','package' => 'modx.sources'),
            $prefix.'member_groups' => array('class' => 'modUserGroupMember','package' => 'modx'),
            $prefix.'membergroup_names' => array('class' => 'modUserGroup','package' => 'modx'),
            $prefix.'menus' => array('class' => 'modMenu','package' => 'modx'),
            $prefix.'namespaces' => array('class' => 'modNamespace','package' => 'modx'),
            $prefix.'property_set' => array('class' => 'modPropertySet','package' => 'modx'),
            $prefix.'register_messages' => array('class' => 'modDbRegisterMessage','package' => 'modx.registry.db'),
            $prefix.'register_queues' => array('class' => 'modDbRegisterQueue','package' => 'modx.registry.db'),
            $prefix.'register_topics' => array('class' => 'modDbRegisterTopic','package' => 'modx.registry.db'),
            $prefix.'session' => array('class' => 'modSession','package' => 'modx'),
            $prefix.'site_content' => array('class' => 'modResource','package' => 'modx'),
            $prefix.'site_htmlsnippets' => array('class' => 'modChunk','package' => 'modx'),
            $prefix.'site_plugin_events' => array('class' => 'modPluginEvent','package' => 'modx'),
            $prefix.'site_plugins' => array('class' => 'modPlugin','package' => 'modx'),
            $prefix.'site_snippets' => array('class' => 'modSnippet','package' => 'modx'),
            $prefix.'site_templates' => array('class' => 'modTemplate','package' => 'modx'),
            $prefix.'site_tmplvar_access' => array('class' => 'modTemplateVarResourceGroup','package' => 'modx'),
            $prefix.'site_tmplvar_contentvalues' => array('class' => 'modTemplateVarResource','package' => 'modx'),
            $prefix.'site_tmplvar_templates' => array('class' => 'modTemplateVarTemplate','package' => 'modx'),
            $prefix.'site_tmplvars' => array('class' => 'modTemplateVar','package' => 'modx'),
            $prefix.'system_eventnames' => array('class' => 'modEvent','package' => 'modx'),
            $prefix.'system_settings' => array('class' => 'modSystemSetting','package' => 'modx'),
            $prefix.'transport_packages' => array('class' => 'modTransportPackage','package' => 'modx.transport'),
            $prefix.'transport_providers' => array('class' => 'modTransportProvider','package' => 'modx.transport'),
            $prefix.'user_attributes' => array('class' => 'modUserProfile','package' => 'modx'),
            $prefix.'user_group_roles' => array('class' => 'modUserGroupRole','package' => 'modx'),
            $prefix.'user_group_settings' => array('class' => 'modUserGroupSetting','package' => 'modx'),
            $prefix.'user_messages' => array('class' => 'modUserMessage','package' => 'modx'),
            $prefix.'user_settings' => array('class' => 'modUserSetting','package' => 'modx'),
            $prefix.'users' => array('class' => 'modUser','package' => 'modx'),
            $prefix.'workspaces' => array('class' => 'modWorkspace','package' => 'modx')
        );

        $this->modx->getVersionData();
        if ($this->modx->version && version_compare($this->modx->version['full_version'], '2.4.0', '>='))
            $modxTables[$prefix.'access_namespace'] = array('class' => 'modAccessNamespace','package' => 'modx');

        return $modxTables;
    }
    /** This method returns an error
     *
     * @param string $message Error message
     * @param mixed $data.
     *
     * @return array $response
     */
    public function error($message = '', $data = '') {
        $response = array(
            'success' => FALSE,
            'message' => $message,
            'data' => $data
        );

        return $response;
    }
}