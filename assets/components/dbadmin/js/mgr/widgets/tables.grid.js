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
			fixed: true,
			width: 100
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
			fixed: true,
			width: 70
		}, {
			header: _('dbadmin_table_size'),
			dataIndex: 'size',
			sortable: false,
			menuDisabled: true,
			fixed: true,
			width: 90
		}, {
			header: _('dbadmin_table_actions'),
			dataIndex: 'actions',
			renderer: dbAdmin.utils.renderActions,
			sortable: false,
			width: 170,
			fixed: true,
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
				this.viewTable(grid, e, row);
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
			// export the entire database
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
	viewTable: function (grid, e, row) {
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
								editable: record.class != '',
								editor:	{xtype: 'textfield'},
								width: 70
							}
						});
						for (var i = 0; i < fields.length; i++) {
							colModel.columns[i] = {
								header: fields[i]['name'],
								dataIndex: fields[i]['name']
							};
							switch (fields[i]['type']) {
								case 'string':
									colModel.columns[i].editor = {xtype: 'textarea'};
									colModel.columns[i].width = 100;
									break;
								case 'number':
									colModel.columns[i].width = 30;
									break;
								case 'actions':
									colModel.columns[i].header = '<i class="icon icon-cog"></i>';
									colModel.columns[i].id = 'actions';
									colModel.columns[i].sortable = false;
									colModel.columns[i].width = 50;
									colModel.columns[i].renderer = dbAdmin.utils.renderActions;
									colModel.columns[i].fixed = true;
									colModel.columns[i].editable = false;
									break;
							}
							if (fields[i]['name'] == 'id') {
								colModel.columns[i].width = 40;
								colModel.columns[i].fixed = true;
							}
						}
						colModel.columns[0].menuDisabled = false;
						if (this.dataGridTable) this.dataGridTable.close();
						this.dataGridTable = MODx.load({
							xtype: 'dbadmin-table-data-window',
							table: record.name,
							class: record.class,
							package: record.package,
							gridFields: fields,
							gridColumns: colModel
						});
						this.dataGridTable.show(Ext.EventObject.target);
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
	updateTable: function (g,e) {
		var row = this.getSelectionModel().getSelected();
		if (typeof(row) != 'undefined') {
			this.menu.record = row.data;
		}
		else if (!this.menu.record) {
			return false;
		}
		row.data.oldName = row.data.name;
		if (!this.updateWindow) {
			this.updateWindow = MODx.load({
				xtype: 'dbadmin-table-window-update',
				//id: Ext.id(),
				listeners: {
					success: {
						fn: function () {
							this.refresh();
						}, scope: this
					}
				}
			});
		}
		this.updateWindow.reset();
		this.updateWindow.setValues(row.data);
		this.updateWindow.show(e.target);
	},
	selectQuery: function () {
		var row = this.getSelectionModel().getSelected();
		if (typeof(row) != 'undefined') {
			this.menu.record = row.data;
		}
		else if (!this.menu.record) {
			return false;
		}
		MODx.Ajax.request({
			url: dbAdmin.config.connector_url,
			params: {
				action: 'mgr/table/getfields',
				table: row.data.name,
				forselect: 1
			},
			listeners: {
				success: {
					fn: function (r) {
						var fields = r.fields || '*',
							query = 'SELECT ' + fields + ' FROM `' + row.data.name+'`';
						//Ext.getCmp('dbadmin-sql-query').setValue('');
						Ext.getCmp('dbadmin-sql-query').setValue(query);
						Ext.getCmp('dbadmin-tabpanel').setActiveTab('dbadmin-sql-tab');

					}, scope: this
				},
				failure: {
					fn: function (r) {}, scope: this
				}
			}
		});
		return true;
	},
	removeTable: function () {
		var row = this.getSelectionModel().getSelected();
		if (typeof(row) != 'undefined') {
			this.menu.record = row.data;
		}
		else if (!this.menu.record) {
			return false;
		}
		var name = row.data.name;
		MODx.msg.confirm({
			title: _('dbadmin_table_remove'),
			text: _('dbadmin_table_remove_confirm'),
			url: this.config.url,
			params: {
				action: 'mgr/table/remove',
				name: name
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
	removeSelected: function () {
		var tables = this.getSelectedAsList();
		if (tables === false) return false;
		MODx.msg.confirm({
			title: tables.split(',').length > 1
				? _('dbadmin_tables_remove')
				: _('dbadmin_table_remove'),
			text: tables.split(',').length > 1
				? _('dbadmin_tables_remove_confirm')
				: _('dbadmin_table_remove_confirm'),
			url: this.config.url,
			params: {
				action: 'mgr/tables/remove',
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
			title: tables.split(',').length > 1
				? _('dbadmin_tables_truncate')
				: _('dbadmin_table_truncate'),
			text: tables.split(',').length > 1
				? _('dbadmin_tables_truncate_confirm')
				: _('dbadmin_table_truncate_confirm'),
			url: this.config.url,
			params: {
				action: 'mgr/tables/truncate',
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
	getTopBar: function (config) {
		return [{
			text: _('dbadmin_db_export'),
			id: 'dbadmin-db-export',
			handler: this.exportSelected,
			scope: this
		}, {
			text: _('bulk_actions'),
			menu: [{
				text: '<i class="icon icon-download"></i> ' + _('dbadmin_selected_export'),
				id: 'dbadmin-menu-selected-export',
				handler: this.exportSelected,
				style: {padding: '3px 10px !important;'},
				scope: this
			}, {
				text: '<i class="icon icon-eraser"></i> ' + _('dbadmin_selected_truncate'),
				id: 'dbadmin-menu-selected-truncate',
				handler: this.truncateSelected,
				scope: this
			}, {
				text: '<i class="icon icon-trash-o"></i> ' + _('dbadmin_selected_remove'),
				id: 'dbadmin-menu-selected-remove',
				handler: this.removeSelected,
				scope: this
			}]
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