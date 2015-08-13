dbAdmin.page.Home = function (config) {
	config = config || {};
	Ext.applyIf(config, {
		components: [{
			xtype: 'dbadmin-panel-home', renderTo: 'dbadmin-panel-home-div'
		}]
	});
	dbAdmin.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.page.Home, MODx.Component);
Ext.reg('dbadmin-page-home', dbAdmin.page.Home);