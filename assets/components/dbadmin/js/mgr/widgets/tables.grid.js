dbAdmin.grid.Tables = function (config) {
	config = config || {};
	if (!config.id) {
		config.id = 'dbadmin-grid-tables';
	}
	this.sm = new Ext.grid.CheckboxSelectionModel();
	Ext.applyIf(config, {
		url: dbAdmin.config.connector_url,
		primaryKey: 'name',
		sm: this.sm,
		fields: ['name', 'class', 'package', 'type', 'rows', 'collation', 'size' , 'actions'],
		columns: [this.sm,{
			header: _('dbadmin_table'),
			dataIndex: 'name',
			sortable: true,
			editable: true,
			//editor:	{xtype: 'textfield'},
			width: 300
		}, {
			header: _('dbadmin_class'),
			dataIndex: 'class',
			sortable: true,
			width: 150
		}, {
			header: _('dbadmin_package'),
			dataIndex: 'package',
			sortable: false,
			hidden: true,
			width: 100
		}, {
			header: _('dbadmin_table_type'),
			dataIndex: 'type',
			sortable: false,
			width: 70
		}, {
			header: _('dbadmin_table_collation'),
			dataIndex: 'collation',
			sortable: false,
			width: 100
		}, {
			header: _('dbadmin_table_rows'),
			dataIndex: 'rows',
			sortable: false,
			menuDisabled: true,
			width: 50
		}, {
			header: _('dbadmin_table_size'),
			dataIndex: 'size',
			sortable: false,
			menuDisabled: true,
			width: 60
		}, {
			header: _('dbadmin_table_actions'),
			dataIndex: 'actions',
			renderer: dbAdmin.utils.renderActions,
			sortable: false,
			width: 90,
			id: 'actions'
		}],
		//autosave: true,
		tbar: this.getTopBar(config),
		baseParams: {
			action: 'mgr/tables/getlist'
		},

		listeners: {
			rowDblClick: function (grid, rowIndex, e) {
				var row = grid.store.getAt(rowIndex);
				this.updateTable(grid, e, row);
			}
		},

		viewConfig: {
			forceFit: true,
			enableRowBody: true,
			autoFill: true,
			showPreview: true,
			scrollOffset: 0
		},
		paging: true,
		pageSize: 25,
		remoteSort: true,
		autoHeight: true
	});
	dbAdmin.grid.Tables.superclass.constructor.call(this, config);

	if (config.autosave) {
		this.on('afteredit',this.saveRecord,this);
	}
	// Clear selection on grid refresh
	this.store.on('load', function (o) {
		if(o.reader.jsonData.mustUpdate) {
			Ext.getCmp('dbadmin-db-sync-btn').setText('<i class="icon icon-exclamation-triangle red"></i> ' + _('dbadmin_db_sync'));
		}
		if (this._getSelectedIds().length) {
			this.getSelectionModel().clearSelections();
		}
	}, this);
};
Ext.extend(dbAdmin.grid.Tables, MODx.grid.Grid, {
	windows: {},

	getMenu: function (grid, rowIndex) {
		var ids = this._getSelectedIds();

		var row = grid.getStore().getAt(rowIndex);
		var menu = dbAdmin.utils.getMenu(row.data['actions'], this, ids);

		this.addContextMenuItem(menu);
	},
	exportSelected: function (b,o) {
		var export_db, tables = '';
		if (b.id == 'dbadmin-db-export') {
			// export the whole db
			export_db = true;
		} else {
			// export selected tables
			export_db = false;
			tables = this.getSelectedAsList();
			if (tables === false) return false;
		}
		var panel = Ext.getCmp('dbadmin-panel');
		panel.el.mask(_('working'));
		MODx.Ajax.request({
			url: dbAdmin.config.connector_url,
			params: {
				action: 'mgr/tables/export',
				tables: tables,
				export_db: export_db
			},
			listeners: {
				success: {
					fn: function (r) {
						panel.el.unmask();
						location.href = this.url+"?action=mgr/tables/download&HTTP_MODAUTH="+MODx.siteId;
					}, scope: this
				},
				failure: {
					fn: function (r) {
						panel.el.unmask();
						MODx.msg.alert(_('error'), r.message);
					}, scope: this
				}
			}
		});
	},
	viewTable: function (btn, e, row) {
		var record = typeof(row) != 'undefined'
			? row.data
			: this.menu.record;
		MODx.Ajax.request({
			url: dbAdmin.config.connector_url,
			params: {
				action: 'mgr/table/getfields',
				table: record.name
			},
			listeners: {
				success: {
					fn: function (r) {
						var fields = r.fields;
						var colModel = new Ext.grid.ColumnModel({
							columns: [],
							defaults: {
								sortable: true,
								menuDisabled: true,
								editable: false,
								width: 70
							}
						});
						for (var i = 0; i < fields['name'].length; i++) {
							colModel.columns[i] = { header: fields['name'][i], dataIndex: fields['name'][i]};
							switch (fields['type'][i]) {
								case 'text':
									colModel.columns[i].width = 100;
									break;
								case 'num':
									colModel.columns[i].width = 50;
									break;
							}
							if (fields['name'][i] == 'id') {
								colModel.columns[i].width = 20;
							}
						}
						colModel.columns[0].menuDisabled = false;
						var w = MODx.load({
							xtype: 'dbadmin-table-data-window',
							table: record.name,
							class: record.class,
							package: record.package,
							gridFields: fields['name'],
							gridColumns: colModel
						}).show();
					}, scope: this
				},
				failure: {
					fn: function (r) {
						MODx.msg.alert(_('error'), r.message);
					}, scope: this
				}
			}
		});
	},
	updateTable: function (btn, e, row) {
		if (typeof(row) != 'undefined') {
			this.menu.record = row.data;
		}
		else if (!this.menu.record) {
			return false;
		}
		row.data.oldName = row.data.name;

		var w = MODx.load({
			xtype: 'dbadmin-table-window-update',
			id: Ext.id(),
			listeners: {
				success: {
					fn: function () {
						this.refresh();
					}, scope: this
				}
			}
		});
		w.reset();
		w.setValues(row.data);
		w.show(e.target);

	},
	removeSelected: function () {
		var tables = this.getSelectedAsList();
		if (tables === false) return false;
		MODx.msg.confirm({
			title: _('dbadmin_tables_remove'),
			text: _('dbadmin_tables_remove_confirm'),
			url: this.config.url,
			params: {
				action: 'mgr/table/remove',
				tables: tables
			},
			listeners: {
				success: {
					fn: function (r) {
						this.refresh();
					}, scope: this
				}
			}
		});
		return true;
	},
	truncateSelected: function () {
		var tables = this.getSelectedAsList();
		if (tables === false) return false;
		MODx.msg.confirm({
			title: _('dbadmin_tables_truncate'),
			text: _('dbadmin_tables_truncate_confirm'),
			url: this.config.url,
			params: {
				action: 'mgr/table/truncate',
				tables: tables
			},
			listeners: {
				success: {
					fn: function (r) {
						this.refresh();
					}, scope: this
				}
			}
		});
		return true;
	},
	syncTablesList: function () {
		var panel = Ext.getCmp('dbadmin-panel');
		panel.el.mask(_('working'));
		MODx.Ajax.request({
			url: dbAdmin.config.connector_url,
			params: {
				action: 'mgr/tables/sync'
			},
			listeners: {
				success: {
					fn: function (r) {
						panel.el.unmask();
						Ext.getCmp('dbadmin-db-sync-btn').setText(_('dbadmin_db_sync'));
						this.refresh();
					}, scope: this
				},
				failure: {
					fn: function (r) {
						panel.el.unmask();
						MODx.msg.alert(_('error'), r.message);
					}, scope: this
				}
			}
		});
	},
	getTopBar: function (config) {
		return [{
			text: _('dbadmin_db_export'),
			id: 'dbadmin-db-export',
			handler: this.exportSelected,
			scope: this
		}, {
			text: _('bulk_actions'),
			menu: [{
				text: '<i class="icon icon-floppy-o"></i> ' + _('dbadmin_selected_export'),
				id: 'dbadmin-menu-selected-export',
				handler: this.exportSelected,
				style: {padding: '3px 10px !important;'},
				scope: this
			}, {
				text: '<i class="icon icon-file-o"></i> ' + _('dbadmin_selected_truncate'),
				id: 'dbadmin-menu-selected-truncate',
				handler: this.truncateSelected,
				scope: this
			}, {
				text: '<i class="icon icon-trash-o"></i> ' + _('dbadmin_selected_remove'),
				id: 'dbadmin-menu-selected-remove',
				handler: this.removeSelected,
				scope: this
			}]
		}, {
			text: _('dbadmin_db_sync'),
			id: 'dbadmin-db-sync-btn',
			handler: this.syncTablesList,
			scope: this
		}, '->', {
			xtype: 'textfield',
			name: 'query',
			width: 200,
			id: config.id + '-search-field',
			emptyText: _('dbadmin_grid_search'),
			listeners: {
				render: {
					fn: function (tf) {
						tf.getEl().addKeyListener(Ext.EventObject.ENTER, function () {
							this.search(tf);
						}, this);
					}, scope: this
				}
			}
		}, {
			xtype: 'button',
			id: config.id + '-search-clear',
			text: '<i class="icon icon-times"></i>',
			listeners: {
				click: {fn: this.clearSearch, scope: this}
			}
		}];
	},
	onClick: function (e) {
		var elem = e.getTarget();
		if (elem.nodeName == 'BUTTON') {
			var row = this.getSelectionModel().getSelected();
			if (typeof(row) != 'undefined') {
				var action = elem.getAttribute('action');
				if (action == 'showMenu') {
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
	},
	_getSelectedIds: function () {
		var ids = [];
		var selected = this.getSelectionModel().getSelections();

		for (var i in selected) {
			if (!selected.hasOwnProperty(i)) {
				continue;
			}
			ids.push(selected[i]['name']);
		}

		return ids;
	},
	search: function (tf, nv, ov) {
		this.getStore().baseParams.query = tf.getValue();
		this.getBottomToolbar().changePage(1);
		this.refresh();
		return true;
	},

	clearSearch: function (btn, e) {
		this.getStore().baseParams.query = '';
		Ext.getCmp(this.config.id + '-search-field').setValue('');
		this.getBottomToolbar().changePage(1);
		this.refresh();
		return true;
	}
});
Ext.reg('dbadmin-grid-tables', dbAdmin.grid.Tables);