<?php

$policies = array();

$tmp = array(
	'dbAdministrator' => array(
		'description' => 'A policy for database administrator.',
		'data' => array(
            'tables_list' => true,
            'table_view' => true,
			'table_save' => true,
			'table_truncate' => true,
			'table_remove' => true,
			'table_export' => true,
			'sql_query_execute' => true,
		),
	),
);

foreach ($tmp as $k => $v) {
	if (isset($v['data'])) {
		$v['data'] = $modx->toJSON($v['data']);
	}

	/* @var $policy modAccessPolicy */
	$policy = $modx->newObject('modAccessPolicy');
	$policy->fromArray(array_merge(array(
		'name' => $k,
		'lexicon' => PKG_NAME_LOWER.':permissions',
	), $v)
	,'', true, true);

	$policies[] = $policy;
}

return $policies;