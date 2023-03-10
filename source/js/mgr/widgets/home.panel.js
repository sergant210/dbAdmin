dbAdmin.panel.Home = function (config) {
    config = config || {};
    Ext.apply(config, {
        cls: 'container home-panel' + ((dbAdmin.config.debug) ? ' debug' : '') + ' modx' + dbAdmin.config.modxversion,
        defaults: {
            collapsible: false,
            autoHeight: true
        },
        items: [{
            html: '<h2>' + _('dbadmin') + '</h2>' + ((dbAdmin.config.debug) ? '<div class="ribbon top-right"><span>' + _('dbadmin.debug_mode') + '</span></div>' : ''),
            border: false,
            cls: 'modx-page-header'
        }, {
            defaults: {
                autoHeight: true
            },
            border: true,
            cls: 'dbadmin-panel',
            items: [{
                xtype: 'dbadmin-panel-overview'
            }]
        }, {
            cls: "treehillstudio_about",
            html: '<img width="146" height="40" src="' + dbAdmin.config.assetsUrl + 'img/mgr/treehill-studio-small.png"' + ' srcset="' + dbAdmin.config.assetsUrl + 'img/mgr/treehill-studio-small@2x.png 2x" alt="Treehill Studio">',
            listeners: {
                afterrender: function () {
                    this.getEl().select('img').on('click', function () {
                        var msg = '<span style="display: inline-block; text-align: center">' +
                            '&copy; 2018-2022 by Sergey Shlokov <a href="https://github.com/sergant210" target="_blank">github.com/sergant210</a><br>' +
                            '<img src="' + dbAdmin.config.assetsUrl + 'img/mgr/treehill-studio.png" srcset="' + dbAdmin.config.assetsUrl + 'img/mgr/treehill-studio@2x.png 2x" alt="Treehill Studio" style="margin-top: 10px"><br>' +
                            '&copy; 2023 by <a href="https://treehillstudio.com" target="_blank">treehillstudio.com</a></span>';
                        Ext.Msg.show({
                            title: _('dbadmin') + ' ' + dbAdmin.config.version,
                            msg: msg,
                            buttons: Ext.Msg.OK,
                            cls: 'treehillstudio_window',
                            width: 358
                        });
                    });
                }
            }
        }]
    });
    dbAdmin.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.panel.Home, MODx.Panel);
Ext.reg('dbadmin-panel-home', dbAdmin.panel.Home);

dbAdmin.panel.HomeTab = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'dbadmin-panel-' + config.tabtype,
        title: config.title,
        items: [{
            html: '<p>' + config.description + '</p>',
            border: false,
            cls: 'panel-desc'
        }, {
            layout: 'form',
            cls: 'x-form-label-left main-wrapper',
            defaults: {
                autoHeight: true
            },
            border: true,
            items: [{
                id: 'dbadmin-panel-' + config.tabtype + '-grid',
                xtype: 'dbadmin-grid-' + config.tabtype,
                preventRender: true
            }]
        }]
    });
    dbAdmin.panel.HomeTab.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.panel.HomeTab, MODx.Panel);
Ext.reg('dbadmin-panel-hometab', dbAdmin.panel.HomeTab);

dbAdmin.panel.Overview = function (config) {
    config = config || {};
    this.ident = 'dbadmin-overview-' + Ext.id();
    this.panelOverviewTabs = [{
        xtype: 'dbadmin-panel-hometab',
        title: _('dbadmin.tables'),
        description: _('dbadmin.tables_desc'),
        tabtype: 'tables'
    }, {
        xtype: 'dbadmin-panel-sql'
    }];
    Ext.applyIf(config, {
        items: [{
            id: 'dbadmin-tabpanel',
            xtype: 'modx-tabs',
            border: true,
            stateful: true,
            stateId: 'dbadmin-panel-overview',
            stateEvents: ['tabchange'],
            getState: function () {
                return {
                    activeTab: this.items.indexOf(this.getActiveTab())
                };
            },
            autoScroll: true,
            deferredRender: true,
            forceLayout: false,
            defaults: {
                layout: 'form',
                autoHeight: true,
                hideMode: 'offsets'
            },
            items: this.panelOverviewTabs,
            listeners: {
                tabchange: function (o, t) {
                    if (t.xtype === 'dbadmin-panel-hometab') {
                        if (Ext.getCmp('dbadmin-panel-' + t.tabtype + '-grid')) {
                            Ext.getCmp('dbadmin-panel-' + t.tabtype + '-grid').getStore().reload();
                        }
                    }
                }
            }
        }]
    });
    dbAdmin.panel.Overview.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.panel.Overview, MODx.Panel);
Ext.reg('dbadmin-panel-overview', dbAdmin.panel.Overview);
