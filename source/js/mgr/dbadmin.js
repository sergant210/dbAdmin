var dbadmin = function (config) {
    config = config || {};
    dbadmin.superclass.constructor.call(this, config);
};
Ext.extend(dbadmin, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('dbadmin', dbadmin);

var dbAdmin = new dbadmin();
