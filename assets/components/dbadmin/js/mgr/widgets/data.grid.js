dbAdmin.window.Data = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'dbadmin-table-data-window';
	}
	Ext.applyIf(config, {
		title: _('dbadmin_table')+' `'+ config.table + '`',
		closeAction: 'close',
		width: 1000,
		maxHeight: 800,
		autoHeight: true,
		//stateful: false,
		//modal: true,
		items: [{
			xtype: 'dbadmin-grid-table-data',
			fields: config.gridFields,
			columns: config.gridColumns,
			class: config.class,
			package: config.package,
			baseParams: {
				action: 'mgr/table/getdata',
				table: config.table,
				class: config.class,
				package: config.package
			}
		}],
		buttons: [{
			text: _('dbadmin_close'),
			id: 'dbadmin-table-data-window-close-btn',
			handler: function(){config.closeAction !== 'close' ? this.hide() : this.close();},
			scope: this
		}]
	});
	dbAdmin.window.Data.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.window.Data, MODx.Window);
Ext.reg('dbadmin-table-data-window', dbAdmin.window.Data);

/**************************************************************/

dbAdmin.grid.Data = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'dbadmin-grid-table-data';
	}
	Ext.applyIf(config, {
		url: dbAdmin.config.connector_url,
		viewConfig: {
			forceFit: true,
			enableRowBody: true,
			autoFill: true,
			showPreview: true,
			scrollOffset: 0
		},
		//autosave: config.class != '',
		autosave: false,
		paging: true,
		pageSize: 10,
		remoteSort: true
	});
	dbAdmin.grid.Data.superclass.constructor.call(this, config);
	if (config.autosave) {
		this.on('afteredit',this.saveRecord,this);
	}
};
Ext.extend(dbAdmin.grid.Data, MODx.grid.Grid, {
	saveRecord: function(e) {
		var oldV = e.originalValue,
			newV = e.value.replace(/^\s+/g, '');
		if (!newV || oldV==newV) {
			e.record.reject();
			return false;
		}
		MODx.Ajax.request({
			url: this.config.url,
			params: {
				action: 'mgr/table/updatefromgrid',
				old: oldV,
				new: newV,
				class: this.config.class,
				package: this.config.package
			},
			listeners: {
				success: {
					fn: function(r) {
						e.record.commit();
						this.refresh();
						this.fireEvent('afterAutoSave',r);
					}
					,scope: this
				}
				,failure: {
					fn: function(r) {
						e.record.reject();
						this.fireEvent('afterAutoSave', r);
					}
					,scope: this
				}
			}
		});
	}
});
Ext.reg('dbadmin-grid-table-data', dbAdmin.grid.Data);