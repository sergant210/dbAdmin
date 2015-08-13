dbAdmin.window.UpdateTable = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'dbadmin-table-window-update';
	}
	Ext.applyIf(config, {
		title: _('dbadmin_table_update'),
		width: 400,
		autoHeight: false,
		url: dbAdmin.config.connector_url,
		action: 'mgr/table/update',
		fields: this.getFields(config),
		keys: [{
			key: Ext.EventObject.ENTER, shift: true, fn: function () {
				this.submit()
			}, scope: this
		}]
	});
	dbAdmin.window.UpdateTable.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.window.UpdateTable, MODx.Window, {

	getFields: function (config) {
		return [{
			xtype: 'textfield',
			fieldLabel: _('dbadmin_table'),
			name: 'name',
			id: config.id + '-name',
			anchor: '99%',
			allowBlank: false
		}, {
			xtype: 'hidden',
			name: 'oldName'
		}, {
			xtype: 'textfield',
			fieldLabel: _('dbadmin_class'),
			name: 'class',
			id: config.id + '-class',
			anchor: '99%'
		}, {
			xtype: 'textfield',
			fieldLabel: _('dbadmin_package'),
			name: 'package',
			id: config.id + '-package',
			anchor: '99%'
		}];
	}

});
Ext.reg('dbadmin-table-window-update', dbAdmin.window.UpdateTable);