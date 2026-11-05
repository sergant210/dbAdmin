dbAdmin.panel.Sql = function (config) {
    config = config || {};
    config.params = {};
    Ext.applyIf(config, {
        title: _('dbadmin.sql'),
        layout: 'form',
        id: 'dbadmin-sql-tab',
        items: [{
            xtype: 'panel',
            id: 'dbadmin-sql-panel',
            items: [{
                html: _('dbadmin.enter_sql_query'),
                cls: 'panel-desc',
                style: {
                    margin: '15px 0'
                }
            }, {
                xtype: Ext.ComponentMgr.types['modx-texteditor'] ? 'modx-texteditor' : 'textarea',
                mimeType: 'text/x-sql',
                hideLabel: true,
                name: 'query',
                style: {
                    margin: '0 15px',
                    resize: 'vertical'
                },
                id: 'dbadmin-sql-query',
                height: 180,
                width: 'auto',
                minHeight: 100
            }, {
                xtype: 'toolbar',
                style: {
                    backgroundColor: 'transparent',
                    borderColor: 'transparent',
                    margin: '10px 15px 10px 13px'
                },
                items: [{
                    xtype: 'button',
                    id: 'dbadmin-execute-query-btn',
                    text: _('dbadmin.execute'),
                    tooltip: 'Ctrl+Enter',
                    tooltipType: 'title',
                    listeners: {
                        click: function () {
                            var panel = Ext.getCmp('dbadmin-sql-panel'),
                                query = Ext.getCmp('dbadmin-sql-query'),
                                queryVal = query.getValue().replace(/^\s+/g, '');
                            if (!queryVal) {
                                return false;
                            }
                            var outputType = Ext.getCmp('dbadmin-outputtype').getValue();
                            panel.el.mask(_('working'));
                            MODx.Ajax.request({
                                url: dbAdmin.config.connectorUrl,
                                params: {
                                    action: 'mgr/sql/execute',
                                    outputType: outputType,
                                    query: query.getValue()
                                },
                                listeners: {
                                    success: {
                                        fn: function (r) {
                                            panel.el.unmask();
                                            var val = r.message;
                                            if (r.select && r.number > 0) {
                                                val = (outputType === 'var_export' ? '<?php\n// ' + val : val) + '\n\n' + r.data;
                                            }
                                            Ext.getCmp('dbadmin-sql-query-result').setValue(val);
                                        }
                                    },
                                    failure: {
                                        fn: function (r) {
                                            panel.el.unmask();
                                            // The error is shown in the result field, suppress the
                                            // following error message box only once
                                            var showError = MODx.form.Handler.showError;
                                            MODx.form.Handler.showError = function () {
                                                MODx.form.Handler.showError = showError;
                                                return false;
                                            };
                                            Ext.getCmp('dbadmin-sql-query-result').setValue(r.message);
                                        }
                                    }
                                }
                            });
                        }
                    }
                }, {
                    xtype: 'button',
                    id: 'dbadmin-clear-btn',
                    text: _('dbadmin.clear'),
                    listeners: {
                        click: function () {
                            Ext.getCmp('dbadmin-sql-query').setValue('');
                            Ext.getCmp('dbadmin-sql-query-result').setValue('');
                        }
                    }
                }, '->', {
                    xtype: 'displayfield',
                    value: _('dbadmin.output_type'),
                    style: {
                        fontSize: '13px'
                    }
                }, {
                    xtype: 'dbadmin-output-types',
                    fieldLabel: _('dbadmin.output_type'),
                    name: 'outputType',
                    id: 'dbadmin-outputtype',
                    value: 'var_export'
                }]
            }, {
                xtype: Ext.ComponentMgr.types['modx-texteditor'] ? 'modx-texteditor' : 'textarea',
                mimeType: 'application/x-php',
                hideLabel: true,
                name: 'query_result',
                style: {
                    margin: '10px 15px 15px',
                    resize: 'vertical'
                },
                id: 'dbadmin-sql-query-result',
                height: 360,
                width: 'auto',
                minHeight: 200,
                readOnly: true
            }]
        }],
        keys: [{
            key: Ext.EventObject.ENTER,
            ctrl: true,
            scope: this,
            fn: function () {
                Ext.getCmp('dbadmin-execute-query-btn').fireEvent('click');
            }
        }]
    });
    dbAdmin.panel.Sql.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.panel.Sql, MODx.Panel);
Ext.reg('dbadmin-panel-sql', dbAdmin.panel.Sql);
