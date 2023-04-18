<?php
/**
 * Resolve access permissions
 *
 * @package dbadmin
 * @subpackage build
 *
 * @var array $options
 * @var xPDOObject $object
 */

/**
 * @param modX $modx
 * @param array $policy
 * @param array $template
 * @param string $permission
 * @return bool
 */
function createAccessPermission(&$modx, $policy, $template, $permission)
{
    /** @var modAccessPolicyTemplate $accessPolicyTemplate */
    if (!$accessPolicyTemplate = $modx->getObject('modAccessPolicyTemplate', [
        'name' => $template['name']
    ])
    ) {
        $accessPolicyTemplate = $modx->newObject('modAccessPolicyTemplate');
        $accessPolicyTemplate->fromArray([
            'name' => $template['name'],
            'description' => $template['description'],
            'lexicon' => $template['lexicon'],
            'template_group' => $template['template_group']
        ]);
        $accessPolicyTemplate->save();
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Access Policy Template "' . $template['name'] . '" created.');
    }

    /** @var modAccessPolicy $accessPolicy */
    if (!$accessPolicy = $modx->getObject('modAccessPolicy', [
        'name' => $policy['name']
    ])
    ) {
        $accessPolicy = $modx->newObject('modAccessPolicy');
        $accessPolicy->fromArray([
            'name' => $policy['name'],
            'description' => $policy['description'],
            'data' => [$permission => true],
            'lexicon' => $policy['lexicon']
        ]);
        $accessPolicy->addOne($accessPolicyTemplate, 'Template');
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Access Policy "' . $policy['name'] . '" created.');
    } else {
        $data = $accessPolicy->get('data');
        $data = ($data) ? array_merge($data, [$permission => true]) : [$permission => true];
        $accessPolicy->set('data', $data);
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Access Policy "' . $policy['name'] . '" updated.');
    }
    $accessPolicy->save();

    if (!$accessPermission = $modx->getObject('modAccessPermission', [
        'name' => $permission
    ])) {
        /** @var modAccessPermission $accessPermission */
        $accessPermission = $modx->newObject('modAccessPermission');
        $accessPermission->fromArray([
            'name' => $permission,
            'description' => 'dbadmin.permission.' . $permission . '_desc',
            'value' => '1'
        ]);
        $accessPermission->addOne($accessPolicyTemplate, 'Template');
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Access Permission "' . $permission . '" created.');
    } else {
        $accessPermission->set('description', 'dbadmin.permission.' . $permission . '_desc');
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Access Permission "' . $permission . '" updated.');
    }
    $accessPermission->save();
    return true;
}

/**
 * @param modX $modx
 * @param array $policy
 * @param array $template
 * @param string $permission
 * @return bool
 */
function removeAccessPermission(&$modx, $policy, $template, $permission)
{
    /** @var modAccessPermission $accessPermission */
    if ($accessPolicy = $modx->getObject('modAccessPolicy', ['name' => $policy['name']])) {
        $accessPolicy->remove();
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Access Policy "' . $policy['name'] . '" removed.');
    }
    /** @var modAccessPolicyTemplate $accessPolicyTemplate */
    if ($accessPolicyTemplate = $modx->getObject('modAccessPolicyTemplate', ['name' => $template['name']])) {
        $accessPolicyTemplate->remove();
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Access Policy Template "' . $template['name'] . '" removed.');
    }
    /** @var modAccessPermission $accessPermission */
    if ($accessPermission = $modx->getObject('modAccessPermission', ['name' => $permission])) {
        $accessPermission->remove();
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Access Permission "' . $permission . '" removed.');
    }
    return true;
}

$accessPolicies = [
    [
        'policy' => [
            'name' => 'dbAdministrator',
            'description' => 'dbAdministrator Access Policy with all attributes.',
            'lexicon' => $options['namespace'] . ':permissions'
        ],
        'template' => [
            'name' => 'dbAdministratorTemplate',
            'description' => 'dbAdministrator Policy Template with all attributes.',
            'lexicon' => $options['namespace'] . ':permissions',
            'template_group' => '1'
        ],
        'permissions' => [
            'tables_list', // modx->lexicon('dbadmin.permission.tables_list_desc');
            'table_view', // modx->lexicon('dbadmin.permission.table_view_desc');
            'table_save', // modx->lexicon('dbadmin.permission.table_save_desc');
            'table_truncate', // modx->lexicon('dbadmin.permission.table_truncate_desc');
            'table_remove', // modx->lexicon('dbadmin.permission.table_remove_desc');
            'table_export', // modx->lexicon('dbadmin.permission.table_export_desc');
            'sql_query_execute', // modx->lexicon('dbadmin.permission.sql_query_execute_desc');
        ]
    ]
];

$success = true;
if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            /** @var modX $modx */
            $modx = &$object->xpdo;
            foreach ($accessPolicies as $accessPolicy) {
                foreach ($accessPolicy['permissions'] as $accessPermission) {
                    $result = createAccessPermission($modx, $accessPolicy['policy'], $accessPolicy['template'], $accessPermission);
                    $success = $success && $result;
                }
            }

            break;
        case xPDOTransport::ACTION_UNINSTALL:
            foreach ($accessPolicies as $accessPolicy) {
                foreach ($accessPolicy['permissions'] as $accessPermission) {
                    $result = removeAccessPermission($modx, $accessPolicy['policy'], $accessPolicy['template'], $accessPermission);
                    $success = $success && $result;
                }
            }
            break;
    }
}
return $success;
