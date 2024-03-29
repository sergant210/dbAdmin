dbAdmin.utils.renderBoolean = function (value) {
    return value
        ? String.format('<span class="green">{0}</span>', _('yes'))
        : String.format('<span class="red">{0}</span>', _('no'));
};
dbAdmin.utils.getMenu = function (actions, grid, selected) {
    var menu = [];
    var cls, icon, title, action = '';
    for (var i in actions) {
        if (!actions.hasOwnProperty(i)) {
            continue;
        }
        var a = actions[i];
        if (!a['menu']) {
            if (a === '-') {
                menu.push('-');
            }
            continue;
        } else if (menu.length > 0 && /^remove/i.test(a['action'])) {
            menu.push('-');
        }
        if (selected.length > 1) {
            if (!a['multiple']) {
                continue;
            } else if (typeof (a['multiple']) == 'string') {
                a['title'] = a['multiple'];
            }
        }
        cls = a['cls'] ? a['cls'] : '';
        icon = a['icon'] ? a['icon'] : '';
        title = a['title'] ? a['title'] : a['title'];
        action = a['action'] ? grid[a['action']] : '';
        menu.push({
            handler: action,
            text: String.format(
                '<span class="{0}"><i class="x-menu-item-icon {1}"></i>{2}</span>',
                cls, icon, title
            ),
        });
    }
    return menu;
};
dbAdmin.utils.renderActions = function (value, props, row) {
    var res = [];
    var cls, icon, title, action, item = '';
    for (var i in row.data.actions) {
        if (!row.data.actions.hasOwnProperty(i)) {
            continue;
        }
        var a = row.data.actions[i];
        if (!a['button']) {
            continue;
        }
        cls = a['cls'] ? a['cls'] : '';
        icon = a['icon'] ? a['icon'] : '';
        action = a['action'] ? a['action'] : '';
        title = a['title'] ? a['title'] : '';
        item = String.format(
            '<li class="{0}"><span class="btn btn-default {1}" data-action="{2}" title="{3}"></span></li>',
            cls, icon, action, title
        );
        res.push(item);
    }
    return String.format(
        '<ul class="dbadmin-row-actions">{0}</ul>',
        res.join('')
    );
};
dbAdmin.combo.Types = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        hiddenName: 'outputType',
        emptyText: _('dbadmin.output_type'),
        triggerAction: 'all',
        mode: 'local',
        hideMode: 'offsets',
        autoScroll: true,
        maxHeight: 200,
        store: [['var_export', 'var_export'], ['print_r', 'print_r']],
        editable: false
    });
    dbAdmin.combo.Types.superclass.constructor.call(this, config);
};
Ext.extend(dbAdmin.combo.Types, MODx.combo.ComboBox);
Ext.reg('dbadmin-output-types', dbAdmin.combo.Types);
