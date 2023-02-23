<?php
/**
 * dbAdmin
 *
 * Copyright 2025-2023 by Sergey Shlokov <sergant210@bk.ru>
 *
 * @package dbadmin
 * @subpackage classfile
 */


/**
 * The base class for dbAdmin.
 */
class dbAdmin
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'dbadmin';

    /**
     * The package name
     * @var string $packageName
     */
    public $packageName = 'dbAdmin';

    /**
     * The version
     * @var string $version
     */
    public $version = '1.2.0';

    /**
     * The class options
     * @var array $options
     */
    public $options = [];

    /**
     * dbAdmin constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    public function __construct(modX &$modx, $options = [])
    {
        $this->modx =& $modx;
        $this->namespace = $this->getOption('namespace', $options, $this->namespace);

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/' . $this->namespace . '/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/' . $this->namespace . '/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/' . $this->namespace . '/');
        $modxversion = $this->modx->getVersionData();

        // Load some default paths for easier management
        $this->options = array_merge([
            'namespace' => $this->namespace,
            'version' => $this->version,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'vendor/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'pagesPath' => $corePath . 'elements/pages/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'pluginsPath' => $corePath . 'elements/plugins/',
            'controllersPath' => $corePath . 'controllers/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $assetsUrl . 'connector.php'
        ], $options);

        // Add default options
        $this->options = array_merge($this->options, [
            'debug' => (bool)$this->getOption('debug', $options, false),
            'modxversion' => $modxversion['version'],
            'chunkSuffix' => '.chunk.tpl',
        ]);

        $this->modx->addPackage($this->namespace, $this->getOption('modelPath'));

        $lexicon = $this->modx->getService('lexicon', 'modLexicon');
        $lexicon->load($this->namespace . ':default');
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = [], $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("$this->namespace.$key", $this->modx->config)) {
                $option = $this->modx->getOption("$this->namespace.$key");
            }
        }
        return $option;
    }

    /**
     * Formatted array of table maps from dbadmin_tables_map table.
     * @return array
     */
    public function getTables()
    {
        $query = $this->modx->newQuery('dbAdminTable');
        $query->select($this->modx->getSelectColumns('dbAdminTable'));
        $tables = [];
        $tstart = microtime(true);
        if ($query->prepare() && $query->stmt->execute()) {
            $this->modx->queryTime += microtime(true) - $tstart;
            $this->modx->executedQueries++;
            $res = $query->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (!empty($res)) {
            foreach ($res as $table) {
                $tables[$table['name']] = array_slice($table, 1, 2);
            }
        }
        return $tables;
    }

    /**
     * Formatted array of working database tables.
     * @return array
     */
    public function getDbTables()
    {
        $tableList = [];
        $q = "SHOW TABLES";
        $result = $this->modx->query($q);
        if (is_object($result)) {
            $tables = $result->fetchAll(PDO::FETCH_NUM);

            for ($i = 0; $i < count($tables); $i++) {
                $tableList[$tables[$i][0]] = ['class' => '', 'package' => ''];
            }
        }
        return $tableList;
    }

    /**
     * Formatted array of work database tables with additional data.
     * @return array
     */
    public function getTablesStatus()
    {
        $tables = $tableList = [];
        $q = "SHOW TABLE STATUS";
        $result = $this->modx->query($q);
        if (is_object($result)) {
            $tables = $result->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach ($tables as &$table) {
            $tableList[$table['Name']] = [
                'type' => $table['Engine'],
                'rows' => $table['Rows'],
                'collation' => $table['Collation'],
                'size' => round(($table['Data_length'] + $table['Index_length']) / 1024, 2)
            ];
        }
        unset($tables);
        return $tableList;
    }

    /**
     * Checks if the dbAdmin table should be updated.
     * @return bool
     */
    public function checkNeedUpdate()
    {
        $tables = array_keys($this->getTables());
        $dbTables = array_keys($this->getDbTables());
        return (array_diff($dbTables, $tables) || array_diff($tables, $dbTables)) ? true : false;
    }

    /**
     * Synchronizes the dbAdmin table with the list of MySQL tables.
     * @return array|bool
     */
    public function synchronize()
    {
        $tablesList = $this->getTables();
        $dbTablesList = $this->getDbTables();
        $tables = array_keys($tablesList);
        $dbTables = array_keys($dbTablesList);
        // Удаляем лишние таблицы
        $diff = array_diff($tables, $dbTables);
        if (!empty($diff)) {
            $query = $this->modx->newQuery('dbAdminTable');
            $query->command('delete');
            $query->where([
                'name:IN' => $diff,
            ]);
            $query->prepare();
            if (!$query->stmt->execute()) {
                return $this->error($this->modx->lexicon('dbadmin.sync'));
            }
        }
        // Добавляем новые таблицы
        $diff = array_diff($dbTables, $tables);
        foreach ($diff as $table) {
            $obj = $this->modx->newObject('dbAdminTable');
            $obj->set('name', $table);
            $obj->save();
        }
        return true;
    }

    /**
     * MODX system table map. Used during installation.
     * @return array
     */
    public function getSystemTablesFromarray()
    {
        $prefix = $this->modx->config['table_prefix'];
        $modxTables =
            [
                $prefix . 'access_actiondom' => ['class' => 'modAccessActionDom', 'package' => 'modx'],
                $prefix . 'access_actions' => ['class' => 'modAccessAction', 'package' => 'modx'],
                $prefix . 'access_category' => ['class' => 'modAccessCategory', 'package' => 'modx'],
                $prefix . 'access_context' => ['class' => 'modAccessContext', 'package' => 'modx'],
                $prefix . 'access_elements' => ['class' => 'modAccessElement', 'package' => 'modx'],
                $prefix . 'access_media_source' => ['class' => 'modAccessMediaSource', 'package' => 'modx.sources'],
                $prefix . 'access_menus' => ['class' => 'modAccessMenu', 'package' => 'modx'],
                $prefix . 'access_permissions' => ['class' => 'modAccessPermission', 'package' => 'modx'],
                $prefix . 'access_policies' => ['class' => 'modAccessPolicy', 'package' => 'modx'],
                $prefix . 'access_policy_template_groups' => ['class' => 'modAccessPolicyTemplateGroup', 'package' => 'modx'],
                $prefix . 'access_policy_templates' => ['class' => 'modAccessPolicyTemplate', 'package' => 'modx'],
                $prefix . 'access_resource_groups' => ['class' => 'modAccessResourceGroup', 'package' => 'modx'],
                $prefix . 'access_resources' => ['class' => 'modAccessResource', 'package' => 'modx'],
                $prefix . 'access_templatevars' => ['class' => 'modAccessTemplateVar', 'package' => 'modx'],
                $prefix . 'actiondom' => ['class' => 'modActionDom', 'package' => 'modx'],
                $prefix . 'actions' => ['class' => 'modAction', 'package' => 'modx'],
                $prefix . 'actions_fields' => ['class' => 'modActionField', 'package' => 'modx'],
                $prefix . 'active_users' => ['class' => 'modActiveUser', 'package' => 'modx'],
                $prefix . 'categories' => ['class' => 'modCategory', 'package' => 'modx'],
                $prefix . 'categories_closure' => ['class' => 'modCategoryClosure', 'package' => 'modx'],
                $prefix . 'class_map' => ['class' => 'modClassMap', 'package' => 'modx'],
                $prefix . 'content_type' => ['class' => 'modContentType', 'package' => 'modx'],
                $prefix . 'context' => ['class' => 'modContext', 'package' => 'modx'],
                $prefix . 'context_resource' => ['class' => 'modContextResource', 'package' => 'modx'],
                $prefix . 'context_setting' => ['class' => 'modContextSetting', 'package' => 'modx'],
                $prefix . 'dashboard' => ['class' => 'modDashboard', 'package' => 'modx'],
                $prefix . 'dashboard_widget' => ['class' => 'modDashboardWidget', 'package' => 'modx'],
                $prefix . 'dashboard_widget_placement' => ['class' => 'modDashboardWidgetPlacement', 'package' => 'modx'],
                $prefix . 'document_groups' => ['class' => 'modResourceGroupResource', 'package' => 'modx'],
                $prefix . 'documentgroup_names' => ['class' => 'modResourceGroup', 'package' => 'modx'],
                $prefix . 'element_property_sets' => ['class' => 'modElementPropertySet', 'package' => 'modx'],
                $prefix . 'extension_packages' => ['class' => 'modExtensionPackage', 'package' => 'modx'],
                $prefix . 'fc_profiles' => ['class' => 'modFormCustomizationProfile', 'package' => 'modx'],
                $prefix . 'fc_profiles_usergroups' => ['class' => 'modFormCustomizationProfileUserGroup', 'package' => 'modx'],
                $prefix . 'fc_sets' => ['class' => 'modFormCustomizationSet', 'package' => 'modx'],
                $prefix . 'lexicon_entries' => ['class' => 'modLexiconEntry', 'package' => 'modx'],
                $prefix . 'manager_log' => ['class' => 'modManagerLog', 'package' => 'modx'],
                $prefix . 'media_sources' => ['class' => 'modMediaSource', 'package' => 'modx.sources'],
                $prefix . 'media_sources_contexts' => ['class' => 'modMediaSourceContext', 'package' => 'modx.sources'],
                $prefix . 'media_sources_elements' => ['class' => 'modMediaSourceElement', 'package' => 'modx.sources'],
                $prefix . 'member_groups' => ['class' => 'modUserGroupMember', 'package' => 'modx'],
                $prefix . 'membergroup_names' => ['class' => 'modUserGroup', 'package' => 'modx'],
                $prefix . 'menus' => ['class' => 'modMenu', 'package' => 'modx'],
                $prefix . 'namespaces' => ['class' => 'modNamespace', 'package' => 'modx'],
                $prefix . 'property_set' => ['class' => 'modPropertySet', 'package' => 'modx'],
                $prefix . 'register_messages' => ['class' => 'modDbRegisterMessage', 'package' => 'modx.registry.db'],
                $prefix . 'register_queues' => ['class' => 'modDbRegisterQueue', 'package' => 'modx.registry.db'],
                $prefix . 'register_topics' => ['class' => 'modDbRegisterTopic', 'package' => 'modx.registry.db'],
                $prefix . 'session' => ['class' => 'modSession', 'package' => 'modx'],
                $prefix . 'site_content' => ['class' => 'modResource', 'package' => 'modx'],
                $prefix . 'site_htmlsnippets' => ['class' => 'modChunk', 'package' => 'modx'],
                $prefix . 'site_plugin_events' => ['class' => 'modPluginEvent', 'package' => 'modx'],
                $prefix . 'site_plugins' => ['class' => 'modPlugin', 'package' => 'modx'],
                $prefix . 'site_snippets' => ['class' => 'modSnippet', 'package' => 'modx'],
                $prefix . 'site_templates' => ['class' => 'modTemplate', 'package' => 'modx'],
                $prefix . 'site_tmplvar_access' => ['class' => 'modTemplateVarResourceGroup', 'package' => 'modx'],
                $prefix . 'site_tmplvar_contentvalues' => ['class' => 'modTemplateVarResource', 'package' => 'modx'],
                $prefix . 'site_tmplvar_templates' => ['class' => 'modTemplateVarTemplate', 'package' => 'modx'],
                $prefix . 'site_tmplvars' => ['class' => 'modTemplateVar', 'package' => 'modx'],
                $prefix . 'system_eventnames' => ['class' => 'modEvent', 'package' => 'modx'],
                $prefix . 'system_settings' => ['class' => 'modSystemSetting', 'package' => 'modx'],
                $prefix . 'transport_packages' => ['class' => 'modTransportPackage', 'package' => 'modx.transport'],
                $prefix . 'transport_providers' => ['class' => 'modTransportProvider', 'package' => 'modx.transport'],
                $prefix . 'user_attributes' => ['class' => 'modUserProfile', 'package' => 'modx'],
                $prefix . 'user_group_roles' => ['class' => 'modUserGroupRole', 'package' => 'modx'],
                $prefix . 'user_group_settings' => ['class' => 'modUserGroupSetting', 'package' => 'modx'],
                $prefix . 'user_messages' => ['class' => 'modUserMessage', 'package' => 'modx'],
                $prefix . 'user_settings' => ['class' => 'modUserSetting', 'package' => 'modx'],
                $prefix . 'users' => ['class' => 'modUser', 'package' => 'modx'],
                $prefix . 'workspaces' => ['class' => 'modWorkspace', 'package' => 'modx']
            ];

        $this->modx->getVersionData();
        if ($this->modx->version && version_compare($this->modx->version['full_version'], '2.4.0', '>='))
            $modxTables[$prefix . 'access_namespace'] = ['class' => 'modAccessNamespace', 'package' => 'modx'];

        return $modxTables;
    }

    /** This method returns an error
     *
     * @param string $message Error message
     * @param mixed $data .
     * @return array
     */
    public function error($message = '', $data = '')
    {
        $response = [
            'success' => FALSE,
            'message' => $message,
            'data' => $data
        ];

        return $response;
    }
}
