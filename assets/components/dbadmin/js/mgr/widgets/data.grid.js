dbAdmin.window.Data = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'dbadmin-table-data-window';
	}
	Ext.applyIf(config, {
		title: _('dbadmin.table')+' `'+ config.table + '`',
		width: 1200,
		maxHeight: 800,
		autoHeight: true,
		autoScroll: true,
		stateful: false,
		items: [{
			xtype: 'dbadmin-grid-table-data',
			fields: config.gridFields,
			columns: config.gridColumns,
			class: config.class,
			package: config.package,
			style: {'margin-top': '15px'},
			showActionsColumn: false,
			baseParams: {
				action: 'mgr/table/getdata',
				table: config.table,
				class: config.class,
				package: config.package
			}
		}],
		buttons: [{
			text: _('close'),
			id: 'dbadmin-table-data-window-close-btn',
			handler: function(){this.hide();},
			scope: this
		}]
	});
	dbAdmin.window.Data.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.window.Data, MODx.Window);
Ext.reg('dbadmin-table-data-window', dbAdmin.window.Data);

dbAdmin.grid.Data = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'dbadmin-grid-table-data';
	}
	Ext.applyIf(config, {
		url: dbAdmin.config.connectorUrl,
		viewConfig: {
			autoFill: false,
			enableRowBody: true,
			forceFit: false,
			scrollOffset: 20
		},
		autosave: config.class !== '',
		autoWidth: true,
		autoScroll: true,
		paging: true,
		pageSize: 10,
		remoteSort: true
	});
	dbAdmin.grid.Data.superclass.constructor.call(this, config);
	if (config.autosave) {
		this.on('afteredit',this.saveRecord,this);
	}
	this.store.on('load', function () {
		if (this.getStore().getTotalCount() < 10 ) {
			var w = Ext.getCmp('dbadmin-table-data-window');
			w.autoHeight = true;
			w.setHeight(300);
		}
	}, this);
};
Ext.extend(dbAdmin.grid.Data, MODx.grid.Grid, {
	saveRecord: function(e) {
		var oldValue = e.originalValue,
			newValue = e.value.replace(/^\s+/g, '');
		if (oldValue === newValue) {
			e.record.reject();
			return false;
		}
		var data = Ext.util.JSON.encode(e.record.data);

		MODx.Ajax.request({
			url: this.config.url,
			params: {
				action: 'mgr/table/updatefromgrid',
				data: data,
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
	},
	removeRow: function () {
		var row = this.getSelectionModel().getSelected();
		if (typeof(row) != 'undefined') {
			this.menu.record = row.data;
		}
		else if (!this.menu.record) {
			return false;
		}
		var data = Ext.util.JSON.encode(this.menu.record);
		MODx.msg.confirm({
			title: _('dbadmin.row_remove'),
			text: _('dbadmin.row_remove_confirm'),
			url: this.config.url,
			params: {
				action: 'mgr/table/removerow',
				data: data,
				class: this.config.class,
				package: this.config.package
			},
			listeners: {
				success: {
					fn: function () {
						this.refresh();
					}, scope: this
				}
			}
		});
		return true;
	},
	onClick: function (e) {
		var elem = e.getTarget();
		if (elem.nodeName === 'SPAN') {
			var row = this.getSelectionModel().getSelected();
			if (typeof(row) != 'undefined') {
				var action = elem.dataset.action;
				if (action === 'showMenu') {
					var ri = this.getStore().find('id', row.id);
					return this._showMenu(this, ri, e);
				}
				else if (typeof this[action] === 'function') {
					this.menu.record = row.data;
					return this[action](this, e);
				}
			}
		}
		return this.processEvent('click', e);
	}
});
Ext.reg('dbadmin-grid-table-data', dbAdmin.grid.Data);
