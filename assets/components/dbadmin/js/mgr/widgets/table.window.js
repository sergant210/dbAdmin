dbAdmin.window.UpdateTable = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'dbadmin-table-window-update';
	}
	Ext.applyIf(config, {
		title: _('dbadmin_table_properties'),
		width: 400,
		autoHeight: false,
		url: dbAdmin.config.connector_url,
		action: 'mgr/tables/update',
		resizable: false,
		maximizable: false,
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
			id: config.id + 'dbadmin-table-name',
			anchor: '100%',
			allowBlank: false
		}, {
			xtype: 'hidden',
			name: 'oldName'
		}, {
			xtype: 'textfield',
			fieldLabel: _('dbadmin_package'),
			name: 'package',
			id: config.id + 'dbadmin-table-package',
			anchor: '100%'
		}, {
			layout: 'column'
			,border: false
			,style: {marginTop:'10px'}
			,anchor: '100%'
			,items: [{
				columnWidth: .85
				,layout: 'form'
				,defaults: { msgTarget: 'under' }
				,border:false
				,items: [{
					xtype: 'textfield',
					fieldLabel: _('dbadmin_class'),
					name: 'class',
					id: config.id + 'dbadmin-table-class',
					anchor: '100%'
				}]
			},{
				columnWidth: .15
				,layout: 'form'
				,defaults: { msgTarget: 'qtip' }
				,style: {marginTop:'5px'}
				,border:false
				,items: [{
					xtype: 'button',
					text: '<i class="icon icon-magic"></i>',
					tooltip: _('dbadmin_table_set_class'),
					id: config.id + '-getclass-button',
					cls: 'get-class-button',
					style: {marginTop: '18px',padding:'7px 15px'},
					scope: this,
					anchor: '100%',
					handler: function(){this.getClass(this.config);}
				}]
			}]
		}];
	},
	getClass: function (config) {
		var name = Ext.getCmp(config.id + 'dbadmin-table-name').getValue('name'),
			pkg = Ext.getCmp(config.id + 'dbadmin-table-package').getValue('package');
		MODx.Ajax.request({
			url: dbAdmin.config.connector_url,
			params: {
				action: 'mgr/tables/setclass',
				name: name,
				package: pkg
			},
			listeners: {
				success: {
					fn: function (r) {
						var className = r.object.class || '';
						Ext.getCmp(config.id + 'dbadmin-table-class').setValue(className);
					}, scope: this
				},
				failure: {
					fn: function (r) {}, scope: this
				}
			}
		});
	},

});
Ext.reg('dbadmin-table-window-update', dbAdmin.window.UpdateTable);