dbAdmin.grid.Tables = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'dbadmin-grid-tables';
    }
    this.sm = new Ext.grid.CheckboxSelectionModel();
    Ext.applyIf(config, {
        url: dbAdmin.config.connectorUrl,
        primaryKey: 'name',
        sm: this.sm,
        fields: ['name', 'class', 'package', 'type', 'rows', 'collation', 'size', 'editable', 'actions'],
        columns: [this.sm, {
            header: _('dbadmin.table'),
            dataIndex: 'name',
            renderer: dbAdmin.utils.renderText,
            sortable: true,
            editable: true,
            width: 300
        }, {
            header: _('dbadmin.class'),
            dataIndex: 'class',
            renderer: dbAdmin.utils.renderText,
            sortable: true,
            width: 150
        }, {
            header: _('dbadmin.package'),
            dataIndex: 'package',
            renderer: dbAdmin.utils.renderText,
            sortable: false,
            hidden: true,
            width: 100
        }, {
            header: _('dbadmin.table_type'),
            dataIndex: 'type',
            renderer: dbAdmin.utils.renderText,
            sortable: false,
            fixed: true,
            width: 100
        }, {
            header: _('dbadmin.table_collation'),
            dataIndex: 'collation',
            renderer: dbAdmin.utils.renderText,
            sortable: false,
            width: 100
        }, {
            header: _('dbadmin.table_rows'),
            dataIndex: 'rows',
            sortable: false,
            menuDisabled: true,
            fixed: true,
            width: 70
        }, {
            header: _('dbadmin.table_size'),
            dataIndex: 'size',
            sortable: false,
            menuDisabled: true,
            fixed: true,
            width: 90
        }, {
            header: _('dbadmin.table_actions'),
            dataIndex: 'actions',
            renderer: dbAdmin.utils.renderActions,
            sortable: false,
            width: 130,
            fixed: true
        }],
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
            autoFill: true,
            enableRowBody: true,
            forceFit: true,
            scrollOffset: 0
        },
        paging: true,
        pageSize: 25,
        remoteSort: true,
        autoHeight: true,
        showActionsColumn: false
    });
    dbAdmin.grid.Tables.superclass.constructor.call(this, config);
    if (config.autosave) {
        this.on('afteredit', this.saveRecord, this);
    }
    // Clear selection on grid refresh
    this.store.on('load', function () {
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
        var menu = dbAdmin.utils.getMenu(row.data.actions, this, ids);
        this.addContextMenuItem(menu);
    },
    exportSelected: function (b) {
        var export_db, tables = '';
        if (b.id === 'dbadmin-db-export') {
            // Export the entire database
            export_db = true;
        } else {
            // Export selected tables
            export_db = false;
            tables = this.getSelectedAsList();
            if (tables === false) return false;
        }
        var panel = Ext.getCmp('dbadmin-panel-tables');
        panel.el.mask(_('working'));
        MODx.Ajax.request({
            url: dbAdmin.config.connectorUrl,
            params: {
                action: 'mgr/tables/export',
                tables: tables,
                export_db: export_db
            },
            listeners: {
                success: {
                    fn: function (r) {
                        panel.el.unmask();
                        location.href = dbAdmin.config.connectorUrl + '?action=mgr/tables/download&name=' + encodeURIComponent(r.object.name) + '&HTTP_MODAUTH=' + MODx.siteId;
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
        // A double click opens the table too
        if (!dbAdmin.utils.hasPermission('table_view')) {
            return false;
        }
        var record = typeof (row) != 'undefined'
            ? row.data
            : this.menu.record;
        // MODX core tables can only be edited with the table_core_edit permission
        var canEdit = !!record.class && record.editable !== false && dbAdmin.utils.hasPermission('table_save');
        MODx.Ajax.request({
            url: dbAdmin.config.connectorUrl,
            params: {
                action: 'mgr/table/getfields',
                table: record.name,
                class: record.class,
                package: record.package
            },
            listeners: {
                success: {
                    fn: function (r) {
                        var fields = r.fields,
                            // The class has to be loadable and has to belong to the table
                            editable = canEdit && !!r.classLoaded,
                            columns = [],
                            column;
                        for (var i = 0; i < fields.length; i++) {
                            column = {
                                header: Ext.util.Format.htmlEncode(fields[i]['name']),
                                dataIndex: fields[i]['name'],
                                renderer: dbAdmin.utils.renderValue
                            };
                            switch (fields[i]['type']) {
                                case 'string':
                                    column.editor = {xtype: 'textarea'};
                                    column.width = 300;
                                    break;
                                case 'number':
                                    column.width = 100;
                                    break;
                                case 'actions':
                                    column.header = '<i class="icon icon-cog"></i>';
                                    column.sortable = false;
                                    column.width = 50;
                                    column.renderer = dbAdmin.utils.renderActions;
                                    column.fixed = true;
                                    column.editable = false;
                                    break;
                            }
                            if (fields[i]['name'] === 'id') {
                                column.width = 50;
                                column.fixed = true;
                            }
                            // The rows of tables without a class have no actions
                            if (fields[i]['type'] !== 'actions' || editable) {
                                columns.push(column);
                            }
                        }
                        // Create the column model with all columns, so that the defaults and editors are applied
                        var colModel = new Ext.grid.ColumnModel({
                            columns: columns,
                            defaults: {
                                sortable: true,
                                menuDisabled: true,
                                editable: editable,
                                editor: {xtype: 'textfield'},
                                width: 150
                            }
                        });
                        colModel.config[0].menuDisabled = false;
                        if (this.dataGridTable) this.dataGridTable.destroy();
                        this.dataGridTable = MODx.load({
                            xtype: 'dbadmin-table-data-window',
                            table: record.name,
                            class: record.class,
                            editable: editable,
                            core: record.editable === false,
                            classError: !!record.class && !r.classLoaded,
                            noPermission: !!record.class && record.editable !== false && !dbAdmin.utils.hasPermission('table_save'),
                            package: record.package,
                            gridFields: fields,
                            primaryKeys: fields.filter(function (field) {
                                return field.primary;
                            }).map(function (field) {
                                return field.name;
                            }),
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
    updateTable: function (g, e) {
        // The record of the clicked row, set by the context menu or the row button
        var record = this.menu.record;
        if (!record) {
            return false;
        }
        if (!this.updateWindow) {
            this.updateWindow = MODx.load({
                xtype: 'dbadmin-table-window-update',
                cls: 'modx' + dbAdmin.config.modxversion,
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
        this.updateWindow.setValues(Ext.apply({}, {oldName: record.name}, record));
        this.updateWindow.show(e ? e.target : null);
    },
    selectQuery: function () {
        // The record of the clicked row, set by the context menu or the row button
        var record = this.menu.record;
        if (!record) {
            return false;
        }
        MODx.Ajax.request({
            url: dbAdmin.config.connectorUrl,
            params: {
                action: 'mgr/table/getfields',
                table: record.name,
                forselect: 1
            },
            listeners: {
                success: {
                    fn: function (r) {
                        var fields = r.fields || '*',
                            query = 'SELECT ' + fields + ' FROM `' + record.name.replace(/`/g, '``') + '`';
                        Ext.getCmp('dbadmin-sql-query').setValue(query);
                        Ext.getCmp('dbadmin-tabpanel').setActiveTab('dbadmin-sql-tab');
                    }, scope: this
                },
                failure: {
                    fn: function (r) {
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
                ? _('dbadmin.tables_remove')
                : _('dbadmin.table_remove'),
            text: tables.split(',').length > 1
                ? _('dbadmin.tables_remove_confirm')
                : _('dbadmin.table_remove_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/tables/remove',
                tables: tables
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
    truncateSelected: function () {
        var tables = this.getSelectedAsList();
        if (tables === false) return false;
        MODx.msg.confirm({
            title: tables.split(',').length > 1
                ? _('dbadmin.tables_truncate')
                : _('dbadmin.table_truncate'),
            text: tables.split(',').length > 1
                ? _('dbadmin.tables_truncate_confirm')
                : _('dbadmin.table_truncate_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/tables/truncate',
                tables: tables
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
    setClasses: function () {
        var register = 'mgr';
        var topic = '/dbadmin-setclasses/';
        var console = MODx.load({
            xtype: 'modx-console',
            register: register,
            topic: topic,
            show_filename: false,
            clear: true,
            listeners: {
                complete: {
                    fn: function () {
                        MODx.msg.status({
                            title: _('success'),
                            message: _('dbadmin.setclasses_finished')
                        });
                    },
                    scope: this
                }
            }
        });
        console.show(Ext.getBody());

        MODx.Ajax.request({
            url: dbAdmin.config.connectorUrl,
            params: {
                action: 'mgr/tables/setclasses',
                register: register,
                topic: topic,
            },
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    },
                    scope: this
                },
                failure: {
                    fn: function () {
                        this.refresh();
                    },
                    scope: this
                }
            }
        });

        return true;
    },
    getTopBar: function (config) {
        return [{
            text: _('dbadmin.db_export'),
            id: 'dbadmin-db-export',
            hidden: !dbAdmin.utils.hasPermission('table_export'),
            handler: this.exportSelected,
            scope: this
        }, {
            text: _('bulk_actions'),
            hidden: !dbAdmin.utils.hasPermission('table_export') && !dbAdmin.utils.hasPermission('table_truncate') && !dbAdmin.utils.hasPermission('table_remove'),
            menu: [{
                text: '<i class="icon icon-download"></i> ' + _('dbadmin.selected_export'),
                id: 'dbadmin-menu-selected-export',
                hidden: !dbAdmin.utils.hasPermission('table_export'),
                handler: this.exportSelected,
                style: {padding: '3px 10px !important;'},
                scope: this
            }, {
                text: '<i class="icon icon-eraser"></i> ' + _('dbadmin.selected_truncate'),
                id: 'dbadmin-menu-selected-truncate',
                hidden: !dbAdmin.utils.hasPermission('table_truncate'),
                handler: this.truncateSelected,
                scope: this
            }, {
                text: '<i class="icon icon-trash-o"></i> ' + _('dbadmin.selected_remove'),
                id: 'dbadmin-menu-selected-remove',
                hidden: !dbAdmin.utils.hasPermission('table_remove'),
                handler: this.removeSelected,
                scope: this
            }]
        }, {
            text: '<i class="icon icon-magic"></i>',
            tooltip: _('dbadmin.table_set_classes'),
            id: 'dbadmin-table-set-classes',
            hidden: !dbAdmin.utils.hasPermission('table_save'),
            handler: this.setClasses,
            scope: this
        }, '->', {
            xtype: 'textfield',
            name: 'query',
            width: 200,
            id: config.id + '-search-field',
            emptyText: _('dbadmin.grid_search'),
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
            tooltip: _('dbadmin.grid_search_clear'),
            listeners: {
                click: {fn: this.clearSearch, scope: this}
            }
        }];
    },
    onClick: function (e) {
        var elem = e.getTarget();
        if (elem.nodeName === 'SPAN' && elem.dataset.action) {
            // Use the row of the clicked button, not the first selected row
            var ri = this.getView().findRowIndex(elem);
            var record = (ri !== false) ? this.getStore().getAt(ri) : null;
            if (record) {
                var action = elem.dataset.action;
                if (action === 'showMenu') {
                    return this._showMenu(this, ri, e);
                } else if (typeof this[action] === 'function') {
                    this.getSelectionModel().selectRow(ri);
                    this.menu.record = record.data;
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
            ids.push(selected[i].data.name);
        }
        return ids;
    },
    search: function (tf) {
        this.getStore().baseParams.query = tf.getValue();
        this.getBottomToolbar().changePage(1);
        this.refresh();
        return true;
    },
    clearSearch: function () {
        this.getStore().baseParams.query = '';
        Ext.getCmp(this.config.id + '-search-field').setValue('');
        this.getBottomToolbar().changePage(1);
        this.refresh();
        return true;
    }
});
Ext.reg('dbadmin-grid-tables', dbAdmin.grid.Tables);
