var dbAdmin = function (config) {
	config = config || {};
	dbAdmin.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin, Ext.Component, {
	page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('dbadmin', dbAdmin);

dbAdmin = new dbAdmin();