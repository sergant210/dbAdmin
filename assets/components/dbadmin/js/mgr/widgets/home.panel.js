dbAdmin.panel.Home = function (config) {
	config = config || {};
	Ext.apply(config, {
		baseCls: 'modx-formpanel',
		layout: 'anchor',
		id: 'dbadmin-panel',
		hideMode: 'offsets',
		items: [{
			html: '<h2>' + _('dbadmin_menu_desc') + '</h2>',
			cls: '',
			style: {margin: '15px 0'}
		}, {
			xtype: 'modx-tabs',
			id: 'dbadmin-tabpanel',
			defaults: {border: false, autoHeight: true},
			border: true,
			hideMode: 'offsets',
			items: [{
				title: _('dbadmin_tables'),
				layout: 'form',
				items: [{
					html: _('dbadmin_intro_msg'),
					id: 'dbadmin-intro-msg',
					cls: 'panel-desc',
					style: {margin: '15px 15px 0'}
				}, {
					xtype: 'dbadmin-grid-tables',
					cls: 'main-wrapper'
				}]
			}, {
				title: _('dbadmin_sql'),
				layout: 'anchor',
				//style: {height:'700px'},
				id: 'dbadmin-sql-tab',
				items: [{
					html: _('dbadmin_enter_sql_query'),
					cls: 'panel-desc',
					style: {margin: '15px', padding: '5px !important'}
				}, {
					xtype: Ext.ComponentMgr.types['modx-texteditor'] ? 'modx-texteditor' : 'textarea',
					hideLabel: true,
					name: 'query',
					style: {margin: '0 15px'},
					id: 'dbadmin-sql-query',
					height: 100,
					width: 'auto',
					minHeight: 100
				},{
					xtype: 'button',
					id: 'dbadmin-execute-query-btn',
					text: _('dbadmin_execute'),
					tooltip: 'Ctrl+Enter',
					tooltipType: 'title',
					style: {margin: '15px 5px 15px 15px'},
					listeners: {
						click: function() {
							var panel = Ext.getCmp('dbadmin-panel'),
								query = Ext.getCmp('dbadmin-sql-query'),
								queryVal = query.getValue().replace(/^\s+/g, '');
							if (!queryVal) return false;
							panel.el.mask(_('working'));
							MODx.Ajax.request({
								url: dbAdmin.config.connector_url,
								params: {
									action: "mgr/sql/execute",
									query: query.getValue()
								},
								listeners: {
									success: {
										fn: function (r) {
											panel.el.unmask();
											var val;
											if (r.select) {
												if (r.number == 0) {
													val = _('dbadmin_rows_number') + "0";
												} else {
													val = _('dbadmin_rows_number') + r.number + "\n\n" + r.data;
												}
											} else {
												val = _('dbadmin_sql_executed_success');
											}

											Ext.getCmp('dbadmin-sql-query-result').setValue(val);
										}
									},
									failure: {
										fn: function (r) {
											panel.el.unmask();
											MODx.msg.alert(_('error'), 'Error');
											Ext.getCmp('dbadmin-sql-query-result').setValue(r.message);
										}
									}
								}
							});
						}
					}
				},{
					xtype: 'button',
					id: 'dbadmin-clear-btn',
					text: _('dbadmin_clear'),
					style: {marginTop: '15px'},
					listeners: {
						click: function () {
							Ext.getCmp('dbadmin-sql-query').setValue("");
							Ext.getCmp('dbadmin-sql-query-result').setValue("");
						}
					}
				},{
					html: _('dbadmin_query_result'),
					cls: 'panel-desc',
					style: {margin: '0 15px'}
				}, {
					xtype: Ext.ComponentMgr.types['modx-texteditor'] ? 'modx-texteditor' : 'textarea',
					hideLabel: true,
					name: 'query_result',
					style: {margin: '10px 15px'},
					id: 'dbadmin-sql-query-result',
					height: 300,
					width: 'auto',
					minHeight: 200,
					readOnly: true
				}]
			}]
		}],
		keys: [{
			key: Ext.EventObject.ENTER,
			ctrl: true,
			scope: this,
			fn: function () {
				Ext.getCmp('dbadmin-execute-query-btn').fireEvent('click');
			}
		}],
		listeners: {
			render: function (p) {
				var msg = Ext.getCmp('dbadmin-intro-msg').html;
				MODx.Ajax.request({
					url: dbAdmin.config.connector_url,
					params: {
						action: 'mgr/package/checkupdate'
					},
					listeners: {
						success: {
							fn: function (r) {
								msg +=  _('dbadmin_package_update');
								Ext.getCmp('dbadmin-intro-msg').update(msg);
							}, scope: this
						}
					}
				});
			}
		}
	});
	dbAdmin.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.panel.Home, MODx.Panel);
Ext.reg('dbadmin-panel-home', dbAdmin.panel.Home);
