<?php
/* Smarty version 4.5.5, created on 2026-02-09 17:39:21
  from '/var/www/u3409091/data/www/mircranov.ru/manager/templates/default/dashboard/updates.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.5',
  'unifunc' => 'content_6989f19935b6f1_95848596',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '15910fe3c00f2da794685906e34f2d19f8641694' => 
    array (
      0 => '/var/www/u3409091/data/www/mircranov.ru/manager/templates/default/dashboard/updates.tpl',
      1 => 1770645912,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6989f19935b6f1_95848596 (Smarty_Internal_Template $_smarty_tpl) {
?><div id="modx-grid-updates" class="updates-widget">
    <div class="table-wrapper">
        <table class="table">
            <thead>
            <tr>
                <th><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_type'];?>
</th>
                <th><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_status'];?>
</th>
                <th><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_action'];?>
</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><span class="updates-title">MODX</span></td>
                <?php if ($_smarty_tpl->tpl_vars['modx']->value['updateable']) {?>
                    <td><span class="updates-available"><?php echo $_smarty_tpl->tpl_vars['modx']->value['latest']['version'];?>
</span></td>
                    <td>
                        <a 
                            href="javascript:;"
                            data-download-id="<?php echo $_smarty_tpl->tpl_vars['modx']->value['latest']['downloadId'];?>
"
                            class="dashboard-button modx"
                            target="_blank"
                        >
                            <?php echo $_smarty_tpl->tpl_vars['_lang']->value['download'];?>

                        </a>
                    </td>
                <?php } else { ?>
                    <td><span class="updates-ok"><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_ok'];?>
</span></td>
                    <td><button class="dashboard-button modx" disabled><?php echo $_smarty_tpl->tpl_vars['_lang']->value['download'];?>
</button></td>
                <?php }?>
            </tr>
            <tr>
                <?php if ($_smarty_tpl->tpl_vars['extras']->value['updateable']) {?>
                    <td>
                        <span class="updates-title"><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_extras'];?>
</span>
                        <span class="updates-updateable">
                            <?php if ($_smarty_tpl->tpl_vars['extras']->value['updateable'] > 10) {?>10+<?php } else {
echo $_smarty_tpl->tpl_vars['extras']->value['updateable'];
}?>
                        </span>
                    </td>
                    <td><span class="updates-available"><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_available'];?>
</span></td>
                    <td>
                        <a href="<?php echo $_smarty_tpl->tpl_vars['_config']->value['manager_url'];?>
?a=workspaces"
                           class="dashboard-button package"><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_update'];?>
</a>
                    </td>
                <?php } else { ?>
                    <td><span class="updates-title"><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_extras'];?>
</span></td>
                    <td><span class="updates-ok"><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_ok'];?>
</span></td>
                    <td><button class="dashboard-button package" disabled><?php echo $_smarty_tpl->tpl_vars['_lang']->value['updates_update'];?>
</button></td>
                <?php }?>
            </tr>
            </tbody>
        </table>
    </div>
</div>


    <?php echo '<script'; ?>
>
        const
            updatesGrid = document.getElementById('modx-grid-updates'),
            modxLinks = document.querySelectorAll('.dashboard-button.modx')
        ;
        modxLinks.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const updatesMask = new Ext.LoadMask(updatesGrid, {
                    msg: _('downloading')
                });
                updatesMask.show();
                MODx.Ajax.request({
                    url: MODx.config.connector_url,
                    params: {
                        action: 'SoftwareUpdate/GetFile',
                        downloadId: link.dataset.downloadId
                    },
                    scope: this,
                    listeners: {
                        success: {
                            fn: function(response) {
                                const url = response.object?.zip;
                                if (!Ext.isEmpty(url)) {
                                    window.location.assign(url);
                                    setTimeout(() => {
                                        updatesMask.hide();
                                    }, 1000);
                                }
                            },
                            scope:this
                        },
                        failure: {
                            fn: function(response) {
                                updatesMask.hide();
                            },
                            scope:this
                        }
                    }
                });
            });
        });
    <?php echo '</script'; ?>
>
    <?php }
}
