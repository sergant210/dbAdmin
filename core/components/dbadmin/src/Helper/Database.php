<?php
/**
 * Abstract processor
 *
 * @package dbadmin
 * @subpackage processors
 */

namespace Sergant210\dbAdmin\Helper;

use dbAdminTable;
use Exception;
use modX;
use PDO;
use PDOException;
use Sergant210\dbAdmin\dbAdmin;
use SimpleXMLElement;
use xPDO;

/**
 * Class Database
 */
class Database
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public $modx;

    /**
     * A reference to the dbAdmin instance
     * @var dbAdmin $dbadmin
     */
    public $dbadmin;


    /**
     * @param modX $modx A reference to the modX instance
     */
    function __construct(modX &$modx, $dbadmin)
    {
        $this->modx =& $modx;
        $this->dbadmin =& $dbadmin;
    }

    /**
     * Get a formatted array of dbAdminTable.
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
     * Get a formatted array of database tables.
     * @return array
     */
    public function getDbTables()
    {
        $tableList = [];
        $sql = 'SHOW TABLES';
        try {
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
                $tables = $stmt->fetchAll(PDO::FETCH_NUM);

                for ($i = 0; $i < count($tables); $i++) {
                    $tableList[$tables[$i][0]] = ['class' => '', 'package' => ''];
                }
            }
        } catch (PDOException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdmin');
        }
        return $tableList;
    }

    /**
     * Get a formatted array of database tables with additional data.
     * @return array
     */
    public function getTablesStatus()
    {
        $tableList = [];
        $sql = 'SHOW TABLE STATUS';
        try {
            if ($stmt = $this->modx->prepare($sql)) {
                $stmt->execute();
                $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($tables as &$table) {
                    $tableList[$table['Name']] = [
                        'type' => $table['Engine'],
                        'rows' => $table['Rows'],
                        'collation' => $table['Collation'],
                        'size' => round(($table['Data_length'] + $table['Index_length']) / 1024, 2)
                    ];
                }
            }
        } catch (PDOException $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', 'dbAdmin');
        }
        return $tableList;
    }

    /**
     * Checks if the dbAdmin table should be updated.
     * @return bool
     */
    public function needsUpdate()
    {
        $tables = array_keys($this->getTables());
        $dbTables = array_keys($this->getDbTables());
        return array_diff($dbTables, $tables) || array_diff($tables, $dbTables);
    }

    /**
     * Synchronizes the dbAdmin table with the list of MySQL tables.
     * @return bool
     */
    public function synchronize()
    {
        $tables = array_keys($this->getTables());
        $dbTables = array_keys($this->getDbTables());
        // Remove unnecessary tables
        $diff = array_diff($tables, $dbTables);
        if (!empty($diff)) {
            $this->modx->removeCollection('dbAdminTable', [
                'name:IN' => $diff,
            ]);
        }
        // Add new tables
        $diff = array_diff($dbTables, $tables);
        foreach ($diff as $table) {
            /** @var dbAdminTable $obj */
            $obj = $this->modx->newObject('dbAdminTable');
            $obj->set('name', $table);
            $this->setTableClass($obj);
            $obj->save();
        }
        return true;
    }

    /**
     * Set the table class of a dbAdminTable record
     * @param dbAdminTable $table
     */
    public function setTableClass (dbAdminTable &$table) {
        $name = str_replace($this->modx->config['table_prefix'], '', $table->get('name'));
        $namespaces = $this->modx->getIterator('modNamespace');
        foreach ($namespaces as $namespace) {
            $package = ($namespace->get('name') != 'core') ? $namespace->get('name') : 'modx';
            if ($package != '' && $table->get('class') == '') {
                try {
                    $class = $this->getPackageClass($package, $name);
                    if ($class) {
                        $table->set('package', $package);
                        $table->set('class', $class);
                        break;
                    }

                } catch (Exception $e) {
                }
            }
        }
        if (!$class) {
            foreach (['modx', 'modx.sources', 'modx.registry.db', 'modx.transport'] as $package) {
                try {
                    $class = $this->getPackageClass($package, $name);
                    if ($class) {
                        $table->set('package', $package);
                        $table->set('class', $class);
                        break;
                    }
                } catch (Exception $e) {
                }
            }
        }
    }

    /**
     * MODX system table map. Used during installation.
     * @return array
     */
    public function getSystemTables()
    {
        $prefix = $this->modx->config['table_prefix'];
        $modxTables = [
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
        if ($this->modx->version && version_compare($this->modx->version['full_version'], '2.4.0', '>=')) {
            $modxTables[$prefix . 'access_namespace'] = ['class' => 'modAccessNamespace', 'package' => 'modx'];
        }

        return $modxTables;
    }

    /**
     * @param $package
     * @param $name
     * @return string
     * @throws Exception
     */
    public function getPackageClass($package, $name): string
    {
        $dbtype = $this->modx->getOption('dbtype', null, 'mysql');
        $packageCorePath = $this->modx->getOption($package . '.core_path', null, $this->modx->getOption('core_path') . 'components/' . $package . '/');
        if (strpos($package, 'modx') !== false) {
            $schemaFile = MODX_CORE_PATH . "model/schema/$package.$dbtype.schema.xml";
        } else {
            $schemaFile = $packageCorePath . "model/schema/$package.$dbtype.schema.xml";
        }
        if (!is_file($schemaFile)) {
            $schemaFile = $packageCorePath . "model/$package/$package.$dbtype.schema.xml";
        }
        if (is_file($schemaFile)) {
            try {
                $schema = new SimpleXMLElement($schemaFile, 0, true);
            } catch (Exception $e) {
                throw new Exception($this->modx->lexicon('dbadmin.schema_err_path'));
            }
            if (isset($schema->object)) {
                foreach ($schema->object as $object) {
                    if ($table = (string)$object['table']) {
                        if ($table != $name) {
                            continue;
                        }
                        return (string)$object['class'];
                    }
                }
            }
            return '';
        } else {
            throw new Exception($this->modx->lexicon('dbadmin.table_err_path'));
        }
    }
}
