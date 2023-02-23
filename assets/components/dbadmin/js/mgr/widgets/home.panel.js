dbAdmin.panel.Home = function (config) {
	config = config || {};
	Ext.apply(config, {
		cls: 'container home-panel' + ' modx' + dbAdmin.config.modxversion,
		id: 'dbadmin-panel',
		defaults: {
			collapsible: false,
			autoHeight: true
		},
		items: [{
			html: '<h2>' + _('dbadmin.menu') + '</h2>',
			border: false,
			cls: 'modx-page-header'
		}, {
			xtype: 'modx-tabs',
			id: 'dbadmin-tabpanel',
			defaults: {border: false, autoHeight: true},
			border: true,
			hideMode: 'offsets',
			stateful: true,
			stateId: 'dbadmin-panel-home',
			stateEvents: ['tabchange'],
			getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
			items: [{
				title: _('dbadmin.tables'),
				layout: 'anchor',
				items: [{
					html: _('dbadmin.intro_msg'),
					id: 'dbadmin-intro-msg',
					cls: 'panel-desc',
					style: {margin: '15px 15px 0'}
				}, {
					xtype: 'dbadmin-grid-tables',
					cls: 'main-wrapper'
				}]
			}, {
				title: _('dbadmin.sql'),
				layout: 'form',
				id: 'dbadmin-sql-tab',
				items: [{
					html: _('dbadmin.enter_sql_query'),
					cls: 'panel-desc',
					style: {margin: '15px', padding: '5px !important'}
				}, {
					xtype: Ext.ComponentMgr.types['modx-texteditor'] ? 'modx-texteditor' : 'textarea',
					mimeType: 'text/x-sql',
					hideLabel: true,
					name: 'query',
					style: {margin: '0 15px', resize: 'vertical'},
					id: 'dbadmin-sql-query',
					height: 180,
					width: 'auto',
					minHeight: 100
				},{
					xtype: 'toolbar',
					style: { backgroundColor: 'transparent',borderColor: 'transparent', margin:'10px 15px 10px 12px'},
					items: [{
						xtype: 'button',
						id: 'dbadmin-execute-query-btn',
						text: _('dbadmin.execute'),
						tooltip: 'Ctrl+Enter',
						tooltipType: 'title',
						listeners: {
							click: function () {
								var panel = Ext.getCmp('dbadmin-panel'),
									query = Ext.getCmp('dbadmin-sql-query'),
									queryVal = query.getValue().replace(/^\s+/g, '');
								if (!queryVal) return false;
								var outputType = Ext.getCmp('dbadmin-outputtype').getValue();
								panel.el.mask(_('working'));
								MODx.Ajax.request({
									url: dbAdmin.config.connectorUrl,
									params: {
										action: "mgr/sql/execute",
										outputType: outputType,
										query: query.getValue()
									},
									listeners: {
										success: {
											fn: function (r) {
												panel.el.unmask();
												var val = outputType === 'var_export' ? "<?php\n//" : '';
												if (r.select) {
													if (r.number === 0) {
														val += _('dbadmin.rows_number') + "0";
													} else {
														val += _('dbadmin.rows_number') + r.number + "\n\n" + r.data;
													}
												} else {
													val = _('dbadmin.sql_executed_success');
												}

												Ext.getCmp('dbadmin-sql-query-result').setValue(val);
											}
										},
										failure: {
											fn: function (r) {
												panel.el.unmask();
												MODx.form.Handler.showError =  function() {
													return false;
												};
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
						text: _('dbadmin.clear'),
						listeners: {
							click: function () {
								Ext.getCmp('dbadmin-sql-query').setValue("");
								Ext.getCmp('dbadmin-sql-query-result').setValue("");
							}
						}
					},'->', {
						xtype: 'displayfield',
						value: _('dbadmin.output_type'),
						style: {fontSize: '13px'}
					}, {
						xtype: 'dbadmin-output-types',
						fieldLabel: _('dbadmin.output_type'),
						name: 'outputType',
						id: 'dbadmin-outputtype',
						value: 'var_export'
					}]
				},{
					xtype: Ext.ComponentMgr.types['modx-texteditor'] ? 'modx-texteditor' : 'textarea',
					mimeType: 'application/x-php',
					hideLabel: true,
					name: 'query_result',
					style: {margin: '10px 15px', resize: 'vertical'},
					id: 'dbadmin-sql-query-result',
					height: 360,
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
			render: function () {
				var msg = Ext.getCmp('dbadmin-intro-msg').html;
				MODx.Ajax.request({
					url: dbAdmin.config.connectorUrl,
					params: {
						action: 'mgr/package/checkupdate'
					},
					listeners: {
						success: {
							fn: function () {
								msg +=  _('dbadmin.package_update');
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
