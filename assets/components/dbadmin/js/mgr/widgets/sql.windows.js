/**
 *  @deprecated Удалить
 */
dbAdmin.window.Sql = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'dbadmin-sql-window';
	}
	Ext.applyIf(config, {
		title: _('dbadmin_execute_sql'),
		width: 800,
		height: 600,
		autoHeight: false,
		url: dbAdmin.config.connector_url,
		action: 'mgr/tables/executesql',
		items: [{
			xtype: Ext.ComponentMgr.types['modx-texteditor'] ? 'modx-texteditor' : 'textarea',
			hideLabel: true,
			name: 'query',
			id: config.id + '-query',
			width: '99%',
			height: '100%',
			minHeight: 200
		}],
		buttons: [{
			text: _('dbadmin_execute'),
			id: config.id + '-execute-btn',
			handler: function(){
				var query = Ext.getCmp(config.id + '-query');
					MODx.Ajax.request({
						url: config.url
						,params: {
							action: "mgr/tables/executesql",
							query: query.getValue()
						}
						,listeners: {
							success: {fn:function(r) {
								MODx.msg.alert(_('info'), _('dbadmin_sql_executed_success'));
							}},
							failure: {fn:function(r) {
								MODx.msg.alert(_('error'), r.message);
							}}
						}
					});
			},
			scope: this
		}, {
			text: _('dbadmin_close'),
			id: config.id + '-close-btn',
			handler: function(){this.close();},
			scope: this
		}]
	});
	dbAdmin.window.Sql.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.window.Sql, MODx.Window, {});
Ext.reg('dbadmin-sql-window', dbAdmin.window.Sql);