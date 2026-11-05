dbAdmin.window.Data = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'dbadmin-table-data-window';
    }
    // The description of the editing possibilities of the table
    var description;
    if (config.editable) {
        description = _('dbadmin.table_data_desc');
    } else if (config.core) {
        description = _('dbadmin.table_data_desc_core');
    } else if (config.noPermission) {
        description = _('dbadmin.table_data_desc_permission');
    } else if (config.classError) {
        description = _('dbadmin.table_data_desc_class');
    } else {
        description = _('dbadmin.table_data_desc_readonly');
    }
    Ext.applyIf(config, {
        title: _('dbadmin.table') + ' `' + Ext.util.Format.htmlEncode(config.table) + '`',
        width: 1200,
        autoHeight: true,
        // The grid rows scroll inside the grid (see dbadmin.scss), so the paging toolbar stays visible
        autoScroll: false,
        stateful: false,
        items: [{
            html: '<p>' + description + '</p>',
            style: {'padding-top': '15px'},
            border: false
        }, {
            xtype: 'dbadmin-grid-table-data',
            cls: 'modx-grid modx-grid-small',
            // Only the field names, a typed string field would convert NULL to an empty string
            fields: dbAdmin.utils.getFieldNames(config.gridFields),
            // MODX 3 uses only a column array or a column model in cm
            cm: config.gridColumns,
            table: config.table,
            class: config.class,
            package: config.package,
            editable: !!config.editable,
            primaryKeys: config.primaryKeys || [],
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
            handler: function () {
                this.hide();
            },
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
        autosave: !!config.editable,
        autoWidth: true,
        autoScroll: true,
        paging: true,
        pageSize: 10,
        remoteSort: true
    });
    // MODx.grid.Grid calls saveRecord after an edit, if autosave is enabled
    dbAdmin.grid.Data.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.grid.Data, MODx.grid.Grid, {
    /**
     * Show the row actions as context menu. Unlike MODx.grid.Grid, a column
     * named menu is never used as menu, if the row has no actions.
     */
    _showMenu: function (grid, rowIndex, e) {
        e.stopEvent();
        e.preventDefault();
        var record = this.getStore().getAt(rowIndex);
        // The actions are always for the clicked row only
        var menu = record ? dbAdmin.utils.getMenu(record.data.actions, this, [record]) : [];
        if (!menu.length) {
            return false;
        }
        this.menu.record = record.data;
        this.menu.recordIndex = rowIndex;
        if (!this.getSelectionModel().isSelected(rowIndex)) {
            this.getSelectionModel().selectRow(rowIndex);
        }
        this.menu.removeAll();
        this.addContextMenuItem(menu);
        this.menu.showAt(e.xy);
        return true;
    },
    saveRecord: function (e) {
        var oldValue = e.originalValue,
            newValue = (typeof e.value === 'string') ? e.value.replace(/^\s+/g, '') : e.value;
        if (oldValue === newValue) {
            e.record.reject();
            return false;
        }
        // Send the original row only to identify the record, only the edited field is saved
        var row = Ext.apply({}, e.record.data);
        row[e.field] = oldValue;
        delete row.actions;

        if (this.config.primaryKeys.indexOf(e.field) !== -1) {
            // The references to the record in other tables are not changed
            var encode = function (value) {
                return Ext.util.Format.htmlEncode(String(value)).replace(/\$/g, '$$$$');
            };
            Ext.Msg.confirm(_('dbadmin.row_pk_change'), _('dbadmin.row_pk_change_confirm', {
                field: encode(e.field),
                old: encode(oldValue),
                new: encode(newValue)
            }), function (button) {
                if (button === 'yes') {
                    this.sendRecord(e, row, newValue);
                } else {
                    e.record.reject();
                }
            }, this);
            return true;
        }
        this.sendRecord(e, row, newValue);
        return true;
    },
    sendRecord: function (e, row, newValue) {
        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/table/updatefromgrid',
                data: Ext.util.JSON.encode(row),
                field: e.field,
                value: newValue,
                table: this.config.table,
                class: this.config.class,
                package: this.config.package
            },
            listeners: {
                success: {
                    fn: function (r) {
                        e.record.commit();
                        this.refresh();
                        this.fireEvent('afterAutoSave', r);
                    },
                    scope: this
                },
                failure: {
                    fn: function (r) {
                        e.record.reject();
                        this.fireEvent('afterAutoSave', r);
                    },
                    scope: this
                }
            }
        });
    },
    removeRow: function () {
        // The record of the clicked row, set by the context menu or the row button
        if (!this.menu.record) {
            return false;
        }
        var row = Ext.apply({}, this.menu.record);
        delete row.actions;
        var data = Ext.util.JSON.encode(row);
        MODx.msg.confirm({
            title: _('dbadmin.row_remove'),
            text: _('dbadmin.row_remove_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/table/removerow',
                data: data,
                table: this.config.table,
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
    }
});
Ext.reg('dbadmin-grid-table-data', dbAdmin.grid.Data);
